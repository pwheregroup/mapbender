<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Application as ApplicationComponent;
use Mapbender\CoreBundle\Component\Element as ElementComponent;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Element as ElementEntity;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
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
            if (!$application) {
                continue;
            }
            $applications[] = $application;
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
        $definition = $this->getApplicationDefinition($slug);

        if (!isset($definition['title'])) {
            $definition['title'] = "Title " . round((microtime(true) * 1000));
        }

        $definition['published'] = isset($definition['published']) ? (boolean)$definition['published'] : false;

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

        if (isset($definition['extra_assets'])) {
            $application->setExtraAssets($definition['extra_assets']);
        }

        if (isset($definition['regionProperties'])) {
            foreach ($definition['regionProperties'] as $regProps) {
                $regionProperties = new RegionProperties();
                $regionProperties->setName($regProps['name']);
                $regionProperties->setProperties($regProps['properties']);
                $application->addRegionProperties($regionProperties);
            }
        }


        $applicationComponent = new ApplicationComponent($this->container, $application, array());

        /** @var \Mapbender\CoreBundle\Component\Element $class */
        /** @var \Mapbender\CoreBundle\Component\Element $elementComponent */
        /** @var ElementComponent $elementComponentClass */

        if (!isset($definition['elements'])) {
            $definition['elements'] = array();
        }

        // Then create elements
        foreach ($definition['elements'] as $region => $elementsDefinition) {
            if (!$elementsDefinition) {
                continue;
            }

            $weight = 0;
            foreach ($elementsDefinition as $elementId => $elementDefinition) {
                /**
                 * MAP Layersets handling
                 */
                if ($elementDefinition['class'] == "Mapbender\\CoreBundle\\Element\\Map") {
                    if (!isset($elementDefinition['layersets'])) {
                        $elementDefinition['layersets'] = array();
                    }
                    if (isset($elementDefinition['layerset'])) {
                        $elementDefinition['layersets'][] = $elementDefinition['layerset'];
                    }
                }

                $elementComponentClass = $elementDefinition['class'];
                if (!class_exists($elementComponentClass)) {
                    continue;
                }

                $elementComponent = new $elementComponentClass($applicationComponent, $this->container, new ElementEntity());

                $configuration_ = $elementDefinition;
                unset($configuration_['class']);
                unset($configuration_['title']);
                if ($elementComponentClass::$merge_configurations) {
                    $configuration = ElementComponent::mergeArrays($elementComponent->getDefaultConfiguration(), $configuration_, array());
                } else {
                    $configuration = $configuration_;
                }

                $class = $elementDefinition['class'];
                $title = array_key_exists('title', $elementDefinition) ?
                    $elementDefinition['title'] :
                    $class::getClassTitle();

                $element = new Element();

                $element->setId($elementId)
                    ->setClass($elementDefinition['class'])
                    ->setTitle($title)
                    ->setConfiguration($configuration)
                    ->setRegion($region)
                    ->setWeight($weight++)
                    ->setApplication($application);

                // set Roles
                $element->setYamlRoles(array_key_exists('roles', $elementDefinition) ? $elementDefinition['roles'] : array());
                $application->addElement($element);
            }
        }

        $application->setYamlRoles(array_key_exists('roles', $definition) ? $definition['roles'] : array());

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
        // Create layersets and layers
        /** @var SourceInstanceEntityHandler $entityHandler */
        foreach ($definition['layersets'] as $layerSetId => $layerDefinitions) {
            $layerSet = new Layerset();
            $layerSet
                ->setId($layerSetId)
                ->setTitle('YAML - ' . $layerSetId)
                ->setApplication($application);

            $weight = 0;
            foreach ($layerDefinitions as $layerId => $layerDefinition) {
                $class = $layerDefinition['class'];
                unset($layerDefinition['class']);
                $entityHandler    = EntityHandler::createHandler($this->container, new $class());
                $instance         = $entityHandler->getEntity();
                $entityHandler->setParameters(array_merge($layerDefinition, array(
                    'weight'   => $weight++,
                    "id"       => $layerId,
                    "layerset" => $layerSet
                )));
                $layerSet->addInstance($instance);
            }
            $application->addLayerset($layerSet);
        }

        $application->setSource(ApplicationEntity::SOURCE_YAML);

        return $application;
    }

    /**
     * Get application definition
     *
     * @param string $slug
     * @return array mixed
     */
    public function getApplicationDefinition($slug)
    {
        $definitions = $this->container->getParameter('applications');
        return $definitions[ $slug ];
    }
}
