<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Element as ElementComponent;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\ArrayUtilYamlQuirks;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Psr\Log\LoggerInterface;
use Mapbender\WmsBundle\Component\WmsInstanceEntityHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * YAML mapper for applications
 *
 * This class is responsible for mapping application definitions given in the
 * YAML configuration to Application configuration entities.
 *
 * @author Christian Wygoda
 */
class ApplicationYAMLMapper
{
    /** @var LoggerInterface  */
    protected $logger;
    /**
     * The service container
     * @var ContainerInterface
     */
    private $container;

    /**
     * ApplicationYAMLMapper constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get("logger");
    }

    /**
     * Get all YAML applications
     *
     * @return Application[]
     */
    public function getApplications()
    {
        $definitions  = $this->container->getParameter('applications');
        $applications = array();
        foreach ($definitions as $slug => $def) {
            $application = $this->getApplication($slug);
            if ($application !== null) {
                $applications[] = $application;
            }
        }

        return $applications;
    }

    /**
     * Get YAML application for given slug
     *
     * Will return null if no YAML application for the given slug exists.
     *
     * @param string $slug
     * @return Application
     */
    public function getApplication($slug)
    {
        $definitions = $this->container->getParameter('applications');
        if (!array_key_exists($slug, $definitions)) {
            return null;
        }
        $timestamp = round((microtime(true) * 1000));
        $definition = $definitions[$slug];
        if (!key_exists('title', $definition)) {
            $definition['title'] = "TITLE " . $timestamp;
        }

        if (!key_exists('published', $definition)) {
            $definition['published'] = false;
        } else {
            $definition['published'] = (boolean) $definition['published'];
        }

        // First, create an application entity
        $application = new ApplicationEntity();
        $application
                ->setScreenshot(key_exists("screenshot", $definition) ? $definition['screenshot'] : null)
                ->setSlug($slug)
                ->setTitle(isset($definition['title'])?$definition['title']:'')
                ->setDescription(isset($definition['description'])?$definition['description']:'')
                ->setTemplate($definition['template'])
                ->setExcludeFromList(isset($definition['excludeFromList'])?$definition['excludeFromList']:false)
                ->setPublished($definition['published']);
        if (isset($definition['custom_css'])) {
            $application->setCustomCss($definition['custom_css']);
        }

        if (isset($definition['publicOptions'])) {
            $application->setPublicOptions($definition['publicOptions']);
        }

        if (isset($definition['publicOptions'])) {
            $application->setPublicOptions($definition['publicOptions']);
        }

        if (array_key_exists('extra_assets', $definition)) {
            $application->setExtraAssets($definition['extra_assets']);
        }
        if (key_exists('regionProperties', $definition)) {
            foreach ($definition['regionProperties'] as $regProps) {
                $regionProperties = new RegionProperties();
                $regionProperties->setName($regProps['name']);
                $regionProperties->setProperties($regProps['properties']);
                $application->addRegionProperties($regionProperties);
            }
        }


        foreach ($this->makeElementEntities($application, $definition) as $element) {
            $application->addElement($element);
        }

        $application->setYamlRoles(array_key_exists('roles', $definition) ? $definition['roles'] : array());

        foreach ($this->makeLayerSets($definition) as $layerSet) {
            $layerSet->setApplication($application);
            $application->addLayerset($layerSet);
        }
        $application->setSource(ApplicationEntity::SOURCE_YAML);

        return $application;
    }

    /**
     * @param Application $application
     * @param mixed[] $definition
     * @return Element[]
     */
    public function makeElementEntities(Application $application, $definition)
    {
        $elementEntities = array();
        if (!isset($definition['elements'])) {
            $definition['elements'] = array();
        }

        foreach ($definition['elements'] as $region => $elementsDefinition) {
            $weight = 0;
            if ($elementsDefinition !== null) {
                foreach ($elementsDefinition as $id => $elementDefinition) {
                    /**
                     * MAP Layersets handling
                     * @todo: support inheritance (probably by using is_a)
                     */
                    if ($elementDefinition['class'] == "Mapbender\\CoreBundle\\Element\\Map") {
                        if (!isset($elementDefinition['layersets'])) {
                            $elementDefinition['layersets'] = array();
                        }
                        if (isset($elementDefinition['layerset'])) {
                            $elementDefinition['layersets'][] = $elementDefinition['layerset'];
                        }
                    }

                    $configuration_ = $elementDefinition;
                    unset($configuration_['class']);
                    unset($configuration_['title']);
                    $entity_class = $elementDefinition['class'];
                    $appl = new \Mapbender\CoreBundle\Component\Application($this->container, $application, array());
                    if (!class_exists($entity_class)) {
                        $this->logger->notice("Element isn't exists ", array(
                            'className'   => $entity_class,
                            'application' => array(
                                'id'    => $application->getId(),
                                'title' => $application->getTitle(),
                                'slug'  => $application->getSlug(),
                            )
                        ));
                        continue;
                    }
                    /** @var ElementComponent $elComp */
                    $elComp = new $entity_class($appl, $this->container, new \Mapbender\CoreBundle\Entity\Element());
                    /** @todo: this method is static, we don't need to fake an Application to call it */
                    $elDefaults = $elComp->getDefaultConfiguration();
                    $configuration = ArrayUtilYamlQuirks::combineRecursive($elDefaults, $configuration_);

                    $class = $elementDefinition['class'];
                    $title = array_key_exists('title', $elementDefinition) ?
                            $elementDefinition['title'] :
                            $class::getClassTitle();

                    $element = new Element();

                    $element->setId($id)
                            ->setClass($elementDefinition['class'])
                            ->setTitle($title)
                            ->setConfiguration($configuration)
                            ->setRegion($region)
                            ->setWeight($weight++)
                            ->setApplication($application);

                    // set Roles
                    $element->setYamlRoles(array_key_exists('roles', $elementDefinition) ? $elementDefinition['roles'] : array());
                    $elementEntities[] = $element;
                }
            }
        }
        return $elementEntities;
    }

    /**
     * @param mixed[] $definition
     * @return LayerSet[]
     */
    public function makeLayerSets($definition)
    {
        if (!isset($definition['layersets'])) {
            $definition['layersets'] = array();

            /**
             * @deprecated definition
             */
            if (isset($definition['layerset'])) {
                $definition['layersets'][] = $definition['layerset'];
            }
        }

        // TODO: Add roles, entity needs work first

        $layerSets = array();
        // Create layersets and layers
        /** @var SourceInstanceEntityHandler $entityHandler */
        foreach ($definition['layersets'] as $id => $layerDefinitions) {
            $layerset = new Layerset();
            $layerset
                ->setId($id)
                ->setTitle('YAML - ' . $id)
            ;

            $weight = 0;
            foreach ($layerDefinitions as $id => $layerDefinition) {
                $class = $layerDefinition['class'];
                unset($layerDefinition['class']);
                $instance = new $class();
                $entityHandler    = EntityHandler::createHandler($this->container, $instance);
                $internDefinition = array(
                    'weight'   => $weight++,
                    "id"       => $id,
                    "layerset" => $layerset
                );
                /** @var WmsInstanceEntityHandler */
                $entityHandler->setParameters(array_merge($layerDefinition, $internDefinition));
                $layerset->addInstance($instance);
            }
            $layerSets[] = $layerset;
        }
        return $layerSets;
    }

    /**
     * @param WmsInstance $instance
     * @param mixed[] $configuration
     * @return WmsInstance same as input
     */
    public static function configureWmsInstance($instance, $configuration)
    {
        /** @var WmsInstance $sourceInstance */
        if (!$instance->getSource()) {
            $instance->setSource(new WmsSource());
        }
        $source = $instance->getSource();
        $source->setId(ArrayUtil::hasSet($configuration, 'id', ""))
            ->setTitle(ArrayUtil::hasSet($configuration, 'id', ""));
        $source->setVersion(ArrayUtil::hasSet($configuration, 'version', "1.1.1"));
        $source->setOriginUrl(ArrayUtil::hasSet($configuration, 'url'));
        $source->setGetMap(new RequestInformation());
        $source->getGetMap()->addFormat(ArrayUtil::hasSet($configuration, 'format', true))
            ->setHttpGet(ArrayUtil::hasSet($configuration, 'url'));
        if (isset($configuration['info_format'])) {
            $source->setGetFeatureInfo(new RequestInformation());
            $source->getGetFeatureInfo()->addFormat(ArrayUtil::hasSet($configuration, 'info_format', true))
                ->setHttpGet(ArrayUtil::hasSet($configuration, 'url'));
        }

        $instance
            ->setId(ArrayUtil::hasSet($configuration, 'id', null))
            ->setTitle(ArrayUtil::hasSet($configuration, 'title', ""))
            ->setWeight(ArrayUtil::hasSet($configuration, 'weight', 0))
            ->setLayerset(ArrayUtil::hasSet($configuration, 'layerset'))
            ->setProxy(ArrayUtil::hasSet($configuration, 'proxy', false))
            ->setVisible(ArrayUtil::hasSet($configuration, 'visible', true))
            ->setFormat(ArrayUtil::hasSet($configuration, 'format', true))
            ->setInfoformat(ArrayUtil::hasSet($configuration, 'info_format'))
            ->setTransparency(ArrayUtil::hasSet($configuration, 'transparent', true))
            ->setOpacity(ArrayUtil::hasSet($configuration, 'opacity', 100))
            ->setTiled(ArrayUtil::hasSet($configuration, 'tiled', false))
            ->setBaseSource(ArrayUtil::hasSet($configuration, 'isBaseSource', true));

        $num  = 0;
        $layersourceroot = new WmsLayerSource();
        $layersourceroot->setPriority($num)
            ->setSource($source)
            ->setTitle($instance->getTitle())
            ->setId($source->getId() . '_' . $num);
        $scale = new MinMax(ArrayUtil::getDefault($configuration, 'minScale', null),
                            ArrayUtil::getDefault($configuration, 'maxScale', null));
        $layersourceroot->setScale($scale);
        $source->addLayer($layersourceroot);
        $rootInstLayer = new WmsInstanceLayer();
        $rootInstLayer->setTitle($instance->getTitle())
            ->setId($instance->getId() . "_" . $num)
            ->setSelected(!isset($configuration["visible"]) ? false : $configuration["visible"])
            ->setPriority($num)
            ->setSourceItem($layersourceroot)
            ->setSourceInstance($instance)
            ->setToggle(false)
            ->setAllowtoggle(true);
        $instance->addLayer($rootInstLayer);
        foreach ($configuration["layers"] as $layerDef) {
            $num++;
            $layersource = new WmsLayerSource();
            $layersource->setSource($source)
                ->setName($layerDef["name"])
                ->setTitle($layerDef['title'])
                ->setParent($layersourceroot)
                ->setId($instance->getId() . '_' . $num);
            if (isset($layerDef["legendurl"])) {
                $style          = new Style();
                $style->setName(null);
                $style->setTitle(null);
                $style->setAbstract(null);
                $legendUrl      = new LegendUrl();
                $legendUrl->setWidth(null);
                $legendUrl->setHeight(null);
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat(null);
                $onlineResource->setHref($layerDef["legendurl"]);
                $legendUrl->setOnlineResource($onlineResource);
                $style->setLegendUrl($legendUrl);
                $layersource->addStyle($style);
            }
            $scale = new MinMax(ArrayUtil::getDefault($layerDef, 'minScale', null),
                                ArrayUtil::getDefault($layerDef, 'maxScale', null));
            $layersource->setScale($scale);
            $layersourceroot->addSublayer($layersource);
            $source->addLayer($layersource);
            $layerInst       = new WmsInstanceLayer();
            $layerInst->setTitle($layerDef["title"])
                ->setId($instance->getId() . '_' . $num)
                ->setSelected(!isset($layerDef["visible"]) ? false : $layerDef["visible"])
                ->setInfo(!isset($layerDef["queryable"]) ? false : $layerDef["queryable"])
                ->setParent($rootInstLayer)
                ->setSourceItem($layersource)
                ->setSourceInstance($instance)
                ->setAllowinfo($layerInst->getInfo() !== null && $layerInst->getInfo() ? true : false);
            $rootInstLayer->addSublayer($layerInst);
            $instance->addLayer($layerInst);
        }
        return $instance;
    }
}
