<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 *
 * @property WmsInstance $entity
 */
class WmsInstanceEntityHandler extends SourceInstanceEntityHandler
{
    /**
     * Populates bound instance with array values. Used exclusively by
     * ApplicationYAMLMapper ..?
     *
     * @param array $configuration
     * @return WmsInstance
     */
    public function setParameters(array $configuration = array())
    {
        // backwards-compatibility HACK: ApplicationYAMLMapper uses dynamic class-name based
        // reflection to arrive here (and only here?), but now that we know for sure
        // we're dealing with a WmsInstance, we bounce the duties right back
        return ApplicationYAMLMapper::configureWmsInstance($this->entity, $configuration);
    }

    /**
     * Copies attributes from bound instance's source to the bound instance.
     * I.e. does not work for a new instance until you have called ->setSource on the WmsInstance yourself,
     * and does not achieve anything useful for an already configured instance loaded from the DB (though it's
     * expensive!).
     * If your source changed, and you want to push updates to your instance, you want to call update, not create.
     *
     * @deprecated for misleading wording, arcane usage, redundant container dependency
     */
    public function create()
    {
        $this->entity->populateFromSource($this->entity->getSource());
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if ($this->entity->getRootlayer()) {
            $rootlayerSaveHandler = new WmsInstanceLayerEntityHandler($this->container, $this->entity->getRootlayer());
            $rootlayerSaveHandler->save();
        }
        $layerSet = $this->entity->getLayerset();
        $num = 0;
        foreach ($layerSet->getInstances() as $instance) {
            /** @var WmsInstance $instance */
            $instance->setWeight($num);
            $this->container->get('doctrine')->getManager()->persist($instance);
            $num++;
        }
        $application = $layerSet->getApplication();
        $application->setUpdated(new \DateTime('now'));
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();
        $entityManager->persist($application);
        $entityManager->persist($this->entity);
    }
    

    /**
     * @inheritdoc
     */
    public function remove()
    {
        /**
         * @todo: layerHandler->remve is redundant now, but it may require an automatic
         *     doctrine:schema:update --force
         *     before it can be removed
         */
        $layerHandler = new WmsInstanceLayerEntityHandler($this->container, $this->entity->getRootlayer());
        $layerHandler->remove();

        $this->container->get('doctrine')->getManager()->persist(
            $this->entity->getLayerset()->getApplication()->setUpdated(new \DateTime('now')));
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
        $source     = $this->entity->getSource();
        $this->entity->setFormat(
            ArrayUtil::getValueFromArray($source->getGetMap()->getFormats(), $this->entity->getFormat(), 0)
        );
        $this->entity->setInfoformat(
            ArrayUtil::getValueFromArray(
                $source->getGetFeatureInfo() ? $source->getGetFeatureInfo()->getFormats() : array(),
                $this->entity->getInfoformat(),
                0
            )
        );
        $this->entity->setExceptionformat(
            ArrayUtil::getValueFromArray($source->getExceptionFormats(), $this->entity->getExceptionformat(), 0)
        );
        $layerDimensionInsts = $source->dimensionInstancesFactory();
        $dimensions = $this->updateDimension($this->entity->getDimensions(), $layerDimensionInsts);
        $this->entity->setDimensions($dimensions);

        # TODO vendorspecific for layer specific parameters
        /** @var WmsInstanceLayerEntityHandler $rootUpdateHandler */
        $rootUpdateHandler = new WmsInstanceLayerEntityHandler($this->container, $this->entity->getRootlayer());
        $rootUpdateHandler->update($this->entity, $this->entity->getSource()->getRootlayer());

        $this->container->get('doctrine')->getManager()->persist(
            $this->entity->getLayerset()->getApplication()->setUpdated(new \DateTime('now')));
        $this->container->get('doctrine')->getManager()->persist($this->entity);
    }

    /**
     * Creates DimensionInst object, copies attributes from given Dimension object
     * @param \Mapbender\WmsBundle\Component\Dimension $dim
     * @return \Mapbender\WmsBundle\Component\DimensionInst
     * @deprecated for redundant container dependency, call DimensionInst::fromDimension directly
     */
    public function createDimensionInst(Dimension $dim)
    {
        return DimensionInst::fromDimension($dim);
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(Signer $signer = null)
    {
        $configuration = WmsInstanceConfiguration::entityToArray($this->entity);

        $layerConfig = $this->getRootLayerConfig();
        if ($layerConfig) {
            $configuration['children'] = array($layerConfig);
        }
        if (!$this->isConfigurationValid($configuration)) {
            return null;
        }
        $hide = false;
        $params = array();
        foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            if ($handler->isVendorSpecificValueValid()) {
                if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE ||
                    ($vendorspec->getVstype() !== VendorSpecific::TYPE_VS_SIMPLE && !$vendorspec->getHidden())) {
                    $user = $this->container->get('security.context')->getToken()->getUser();
                    $params = array_merge($params, $handler->getKvpConfiguration($user));
                } else {
                    $hide = true;
                }
            }
        }
        if ($hide || $this->entity->getSource()->getUsername()) {
            $url = $this->getTunnel()->getPublicBaseUrl();
            $configuration['options']['url'] = UrlUtil::validateUrl($url, $params, array());
            // remove ows proxy for a tunnel connection
            $configuration['options']['tunnel'] = true;
        } elseif ($signer) {
            $configuration['options']['url'] = UrlUtil::validateUrl($configuration['options']['url'], $params, array());
            $configuration['options']['url'] = $signer->signUrl($configuration['options']['url']);
            if ($this->entity->getProxy()) {
                $this->signeUrls($signer, $configuration['children'][0]);
            }
        }
        $status = $this->entity->getSource()->getStatus();
        $configuration['status'] = $status && $status === Source::STATUS_UNREACHABLE ? 'error' : 'ok';
        return $configuration;
    }

    /**
     * Does nothing, returns nothing
     * @deprecated
     */
    public function generateConfiguration()
    {
    }

    protected function getRootLayerConfig()
    {
        $rootlayer = $this->entity->getRootlayer();
        if (!$rootlayer) {
            $message = get_class($this->entity) . "#{$this->entity->getId()} has no root layer!";
            throw new \LogicException($message);
        }
        $entityHandler = new WmsInstanceLayerEntityHandler($this->container, null);
        $rootLayerConfig = $entityHandler->generateConfiguration($rootlayer);
        return $rootLayerConfig;
    }

    /**
     * Signes urls.
     * @param Signer $signer signer
     * @param type $layer
     */
    private function signeUrls(Signer $signer, &$layer)
    {
        if (isset($layer['options']['legend'])) {
            if (isset($layer['options']['legend']['graphic'])) {
                $layer['options']['legend']['graphic'] = $signer->signUrl($layer['options']['legend']['graphic']);
            } elseif (isset($layer['options']['legend']['url'])) {
                $layer['options']['legend']['url'] = $signer->signUrl($layer['options']['legend']['url']);
            }
        }
        if (isset($layer['children'])) {
            foreach ($layer['children'] as &$child) {
                $this->signeUrls($signer, $child);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSensitiveVendorSpecific()
    {
        $vsarr = array();
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user instanceof AdvancedUserInterface) {
            foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
                $handler = new VendorSpecificHandler($vendorspec);
                if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_USER) {
                    $value = $handler->getVendorSpecificValue($user);
                    if ($value) {
                        $vsarr[$vendorspec->getParameterName()] = $value;
                    }
                } elseif ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_GROUP) {
                    $groups = array();
                    foreach ($user->getGroups() as $group) {
                        $value = $handler->getVendorSpecificValue($group);
                        if ($value) {
                            $vsarr[$vendorspec->getParameterName()] = $value;
                        }
                    }
                    if (count($groups)) {
                        $vsarr[$vendorspec->getParameterName()] = implode(',', $groups);
                    }
                }
            }
        }
        foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE) {
                $value = $handler->getVendorSpecificValue(null);
                if ($value) {
                    $vsarr[$vendorspec->getParameterName()] = $value;
                }
            }
        }
        return $vsarr;
    }

    /**
     * Copies Extent and Default from passed DimensionInst to any DimensionInst stored
     * in bound WmsInstance that match the same Type.
     *
     * @param DimensionInst $dimension
     * @deprecated
     */
    public function mergeDimension($dimension)
    {
        $this->entity->reconfigureDimensions($dimension);
    }

    /**
     * @param \Mapbender\WmsBundle\Component\DimensionInst $dimension
     * @param  DimensionInst[]                             $dimensionList
     * @return null
     */
    private function findDimension(DimensionInst $dimension, $dimensionList)
    {
        foreach ($dimensionList as $help) {
            /* check if dimensions equals (check only origextent) */
            if ($help->getOrigextent() === $dimension->getOrigextent() &&
                $help->getName() === $dimension->getName() &&
                $help->getUnits() === $dimension->getUnits()) {
                return $help;
            }
        }
        return null;
    }

    /**
     * @param array $dimensionsOld
     * @param array $dimensionsNew
     * @return array
     */
    private function updateDimension(array $dimensionsOld, array $dimensionsNew)
    {
        $dimensions = array();
        foreach ($dimensionsNew as $dimNew) {
            $dimension    = $this->findDimension($dimNew, $dimensionsOld);
            $dimension    = $dimension ? clone $dimension : clone $dimNew;
            /* replace attribute values */
            $dimension->setUnitSymbol($dimNew->getUnitSymbol());
            $dimension->setNearestValue($dimNew->getNearestValue());
            $dimension->setCurrent($dimNew->getCurrent());
            $dimension->setMultipleValues($dimNew->getMultipleValues());
            $dimensions[] = $dimension;
        }
        return $dimensions;
    }

    /**
     * Checks if a configuraiton is valid.
     * @param array $configuration configuration of an instance or a layer
     * @param boolean $isLayer if it is a layer's configurationis it a layer's configuration?
     * @return boolean true if a configuration is valid otherwise false
     */
    private function isConfigurationValid(array $configuration, $isLayer = false)
    {
        if (!$isLayer) {
            // TODO another tests for instance configuration
            /* check if root exists and has children */
            if (count($configuration['children']) !== 1 || !isset($configuration['children'][0]['children'])) {
                return false;
            } else {
                foreach ($configuration['children'][0]['children'] as $childConfig) {
                    if ($this->isConfigurationValid($childConfig, true)) {
                        return true;
                    }
                }
            }
        } else {
            if (isset($configuration['children'])) { // > 2 simple layers -> OK.
                foreach ($configuration['children'] as $childConfig) {
                    if ($this->isConfigurationValid($childConfig, true)) {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }
        return false;
    }
}
