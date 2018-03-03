<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Element as Entity;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\ManagerBundle\Component\Mapper;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base class for all Mapbender elements.
 *
 * This class defines all base methods and required instance methods to
 * implement an Mapbender3 element.
 *
 * @author Christian Wygoda
 */
abstract class Element
{
    /**
     * Extended API. The ext_api defines, if an element can be used as a target
     * element.
     * @var boolean extended api
     */
    public static $ext_api = true;

    /**
     * Only evaluated by ApplicationYAMLMapper.
     * Used to indicate that your getDefaultConfiguration returns "difficult"
     * sub-array values that should not be merged into the intial generated
     * Element Entity configuration. This is a workaround for the triviality
     * of subarray handling in mergeArrays, which handles everything as
     * hashmaps, so flat numeric subarrays will rewrite each other
     * and duplicates can be produced.
     *
     * @todo: stop using this, override your mergeArrays to keep your subarrays unique as you require
     * @var boolean merge configurations
     * @deprecated
     */
    public static $merge_configurations = true;

    /** @var Application Application component */
    protected $application;

    /** @var ContainerInterface Symfony container */
    protected $container;

    /**  @var Entity Element configuration storage entity */
    protected $entity;

    /** @var array Class name parts */
    protected $classNameParts;

    /** @var array Element fefault configuration */
    protected static $defaultConfiguration = array();

    /** @var string Element description translation subject */
    protected static $description  = "mb.core.element.class.description";

    /** @var string Element title translation subject */
    protected static $tags = array();

    /** @var string[] Element tag translation subjects */
    protected static $title = "mb.core.element.class.title";

    /**
     * The constructor. Every element needs an application to live within and
     * the container to do useful things.
     *
     * @param Application        $application Application component
     * @param ContainerInterface $container   Container service
     * @param Entity             $entity
     */
    public function __construct(Application $application, ContainerInterface $container, Entity $entity)
    {
        $this->classNameParts = explode('\\', get_called_class());
        $this->application    = $application;
        $this->container      = $container;
        $this->entity         = $entity;
    }

    /*************************************************************************
     *                                                                       *
     *                              Class metadata                           *
     *                                                                       *
     *************************************************************************/

    /**
     * Returns the element class title
     *
     * This is primarily used in the manager backend when a list of available
     * elements is given.
     *
     * @return string
     */
    public static function getClassTitle()
    {
        return static::$title;
    }

    /**
     * Returns the element class description.
     *
     * This is primarily used in the manager backend when a list of available
     * elements is given.
     *
     * @return string
     */
    public static function getClassDescription()
    {
        return static::$description;
    }

    /**
     * Returns the element class tags.
     *
     * These tags are used in the manager backend to quickly filter the list
     * of available elements.
     *
     * @return array
     */
    public static function getClassTags()
    {
        return static::$tags;
    }

    /**
     * Returns the default element options.
     *
     * You should specify all allowed options here with their default value.
     *
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return static::$defaultConfiguration;
    }

    /*************************************************************************
     *                                                                       *
     *                    Configuration entity handling                      *
     *                                                                       *
     *************************************************************************/

    /**
     * Get a configuration value by path.
     *
     * Get the configuration value or null if the path is not defined. If you
     * ask for an path which has children, the configuration array with these
     * children will be returned.
     *
     * Configuration paths are lists of parameter keys seperated with a slash
     * like "targets/map".
     *
     * @param string $path The configuration path to retrieve.
     * @return mixed
     */
    final public function get($path)
    {
        throw new \RuntimeException('NIY get ' . $path . ' ' . get_class($this));
    }

    /**
     * Set a configuration value by path.
     *
     * @param string $path the configuration path to set
     * @param mixed $value the value to set
     */
    final public function set($path, $value)
    {
        throw new \RuntimeException('NIY set');
    }

    /**
     * Get the configuration entity.
     *
     * @return \Mapbender\CoreBundle\Entity\Element $entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /*************************************************************************
     *                                                                       *
     *             Shortcut functions for leaner Twig templates              *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the element ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get the element title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

    /**
     * Get the element description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->entity->getDescription();
    }

    /*************************************************************************
     *                                                                       *
     *                              Frontend stuff                           *
     *                                                                       *
     *************************************************************************/

    /**
     * Render the element HTML fragment.
     *
     * @return string
     */
    public function render()
    {
        $defaultTemplateVars = array(
            'element'       => $this,
            'id'            => $this->getId(),
            'entity'        => $this->entity,
            'title'         => $this->getTitle(),
            'application'   => $this->application,
        );
        $templateVars = array_replace($defaultTemplateVars, $this->getFrontendTemplateVars());
        $templatePath = $this->getFrontendTemplatePath();

        /** @var TwigEngine $templatingEngine */
        $templatingEngine = $this->container->get('templating');
        return $templatingEngine->render($templatePath, $templateVars);
    }

    /**
     * Should return the variables available for the frontend template. By default, this
     * is a single "configuration" value holding the entirety of the configuration from the element entity.
     * Override this if you want to unravel / strip / extract / otherwise prepare specific values for your
     * element template.
     *
     * NOTE: the default implementation of render automatically makes the following available:
     * * "element" (Element component instance)
     * * "application" (Application component instance)
     * * "entity" (Element entity instance)
     * * "id" (Element id, string)
     * * "title" (Element title from entity, string)
     *
     * You do not need to, and should not, produce them yourself again. If you do, your values will replace
     * the defaults!
     *
     * @return array
     */
    public function getFrontendTemplateVars()
    {
        return array(
            'configuration' => $this->getConfiguration(),
        );
    }

    /**
     * Return twig-style BundleName:section:file_name.engine.twig path to template file.
     *
     * Base implementation automatically calculates this from the class name. Override if
     * you cannot follow naming / placement conventions.
     *
     * @param string $suffix defaults to '.html.twig'
     * @return string
     */
    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return $this->getAutomaticTemplatePath($suffix);
    }

    /**
     * Should return the values available to the JavaScript client code. By default, this is the entirety
     * of the "configuration" array from the element entity, which is insecure if your element configuration
     * stores API keys, users / passwords embedded into URLs etc.
     *
     * If you have sensitive data anywhere near your element entity's configuration, you should override this
     * method and emit only the values you need for your JavaScript to work.
     *
     * @return array
     */
    public function getPublicConfiguration()
    {
        return $this->getConfiguration();
    }

    /**
     * Get the element assets.
     *
     * Returns an array of references to asset files of the given type.
     * Assets are grouped by css and javascript.
     * References can either be filenames/path which are searched for in the
     * Resources/public directory of the element's bundle or assetic references
     * indicating the bundle to search in:
     *
     * array(
     *   'foo.css'),
     *   '@MapbenderCoreBundle/Resources/public/foo.css'));
     *
     * @return array
     */
    public static function listAssets()
    {
        return array();
    }

    /**
     * Get the element assets.
     *
     * This should be a subset of the static function listAssets. Assets can be
     * removed from the overall list depending on the configuration for
     * example. By default, the same list as by listAssets is returned.
     *
     * @return array
     */
    public function getAssets()
    {
        return $this::listAssets();
    }

    /**
     * Get the configuration from the element entity.
     *
     * This should primarily be used for exporting / importing.
     *
     * You SHOULD NOT do transformations specifically for your twig template or your JavaScript here,
     * there are now dedicated methods exactly for that (see getFrontendTemplateVars and getPublicConfiguration).
     * Anything you do here in a method override needs to be verified against application export + reimport.
     *
     * The backend usually accesses the entity instance directly, so it won't be affected.
     *
     * @return array
     */
    public function getConfiguration()
    {

//        $configuration = $this->entity->getConfiguration();

        $configuration = $this->entity->getConfiguration();
//        $config = $this->entity->getConfiguration();
//        //@TODO merge recursive $this->entity->getConfiguration() and $this->getDefaultConfiguration()
//        $def_configuration = $this->getDefaultConfiguration();
//        $configuration = array();
//        foreach ($def_configuration as $key => $val) {
//            if(isset($config[$key])){
//                $configuration[$key] = $config[$key];
//            }
//        }
        return $configuration;
    }

    /**
     * Get the function name of the JavaScript widget for this element. This
     * will be called to initialize the element.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return 'mapbender.mb' . end($this->classNameParts);
    }

    /**
     * Handle element Ajax requests.
     *
     * Do your magic here.
     *
     * @param string $action The action to perform
     * @return Response
     */
    public function httpAction($action)
    {
        throw new NotFoundHttpException('This element has no Ajax handler.');
    }

    /**
     * Translate subject by key
     *
     * @param       $key
     * @param array $parameters
     * @param null  $domain
     * @param null  $locale
     * @return string
     */
    public function trans($key, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->container->get('translator')->trans($key, $parameters);
    }

    /*************************************************************************
     *                                                                       *
     *                          Backend stuff                                *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the element configuration form type. By default, this class needs to have the same name
     * as the element, suffixed with "AdminType" and located in the Element\Type sub-namespace.
     *
     * Override this method and return null to get a simple YAML entry form.
     *
     * @return string Administration type class name
     */
    public static function getType()
    {
        return static::getAutomaticAdminType(null);
    }

    /**
     * Get the form template to use.
     *
     * @return string
     */
    public static function getFormTemplate()
    {
        return static::getAutomaticTemplatePath('.html.twig', 'ElementAdmin', null);
    }

    /**
     * Get the form assets.
     *
     * @return array
     */
    public static function getFormAssets()
    {
        return array(
            'js' => array(),
            'css' => array());
    }

    /**
     *  Merges the default configuration array and the configuration array
     *
     * @param array $default the default configuration of an element
     * @param array $main the configuration of an element
     * @param array $result the result configuration
     * @return array the result configuration
     */
    public static function mergeArrays($default, $main, $result)
    {
        return ArrayUtil::mergeHashesRecursiveInto($result, $default, $main);
    }

    /**
     * Changes Element after save.
     */
    public function postSave()
    {

    }

    /**
     * Create form for given element
     *
     * @param ContainerInterface $container
     * @param Application        $application
     * @param Entity             $element
     * @param bool               $onlyAcl
     * @return array
     * @internal param string $class
     */
    public static function getElementForm($container, $application, Entity $element, $onlyAcl = false)
    {
        /** @var Element $class */
        $class = $element->getClass();

        // Create base form shared by all elements
        $formType = $container->get('form.factory')->createBuilder('form', $element, array());
        if (!$onlyAcl) {
            $formType->add('title', 'text')
                ->add('class', 'hidden')
                ->add('region', 'hidden');
        }
        $formType->add(
            'acl',
            'acl',
            array(
                'mapped' => false,
                'data' => $element,
                'create_standard_permissions' => false,
                'permissions' => array(
                    1 => 'View'
                )
            )
        );

        // Get configuration form, either basic YAML one or special form
        $configurationFormType = $class::getType();
        if ($configurationFormType === null) {
            $formType->add(
                'configuration',
                new YAMLConfigurationType(),
                array(
                    'required' => false,
                    'attr' => array(
                        'class' => 'code-yaml'
                    )
                )
            );
            $formTheme = 'MapbenderManagerBundle:Element:yaml-form.html.twig';
            $formAssets = array(
                'js' => array(
                    'bundles/mapbendermanager/codemirror/lib/codemirror.js',
                    'bundles/mapbendermanager/codemirror/mode/yaml/yaml.js',
                    'bundles/mapbendermanager/js/form-yaml.js'),
                'css' => array(
                    'bundles/mapbendermanager/codemirror/lib/codemirror.css'));
        } else {
            $type = new $configurationFormType();
            $options = array('application' => $application);
            if ($type instanceof ExtendedCollection && $element !== null && $element->getId() !== null) {
                $options['element'] = $element;
            }
            $formType->add('configuration', $type, $options);
            $formTheme = $class::getFormTemplate();
            $formAssets = $class::getFormAssets();
        }

        return array(
            'form' => $formType->getForm(),
            'theme' => $formTheme,
            'assets' => $formAssets);
    }

    /**
     * Create default element
     *
     * @param Element $class
     * @param string $region
     * @return \Mapbender\CoreBundle\Entity\Element
     */
    public static function getDefaultElement($class, $region)
    {
        $element = new Entity();
        $configuration = $class::getDefaultConfiguration();
        $element
            ->setClass($class)
            ->setRegion($region)
            ->setWeight(0)
            ->setTitle($class::getClassTitle())
            ->setConfiguration($configuration);

        return $element;
    }

    /**
     * Changes a element entity configuration while exporting.
     *
     * @param array $formConfiguration element entity configuration
     * @param array $entityConfiguration element entity configuration
     * @return array a configuration
     */
    public function normalizeConfiguration(array $formConfiguration, array $entityConfiguration = array())
    {
        return $formConfiguration;
    }

    /**
     * Changes a element entity configuration while importing.
     *
     * @param array $configuration element entity configuration
     * @param Mapper $mapper a mapper object
     * @return array a configuration
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        return $configuration;
    }

    /**
     * Converts a camel-case string to underscore-separated lower-case string
     *
     * E.g. "FantasticMethodNaming" => "fantastic_method_naming"
     *
     * @todo: naming, location
     *
     * @param $className
     * @return mixed
     * @internal
     */
    protected static function getTemplateName($className)
    {
        // insert underscores before upper case letter following lower-case
        $underscored = preg_replace('/([^A-Z])([A-Z])/', '\\1_\\2', $className);
        // lower-case the whole thing
        return strtolower($underscored);
    }

    /**
     * Hook function for embedded elements to influence the effective application config on initial load.
     * We (can) use this for BaseSourceSwitchter (deactivates layers), SuggestMap element reloading state etc.
     *
     * @param array
     * @return array
     */
    public function updateAppConfig($configIn)
    {
        return $configIn;
    }

    ##### Automagic calculation for template paths and admin type ####

    /**
     * Generates an automatic template path for the element from its class name.
     *
     * The generated path is twig engine style, i.e.
     * 'BundleClassName:section:template_name.engine-name.twig'
     *
     * The bundle name is auto-generated from the first two namespace components, e.g. "MabenderCoreBundle".
     *
     * The resource section (=subfolder in Resources/views) defaults to "Element" but can be supplied.
     *
     * The file name is the in-namespace class name lower-cased and & decamelized, plus the given $suffix,
     * which defaults to '.html.twig'. E.g. "html_element.html.twig".
     *
     * E.g. for a Mapbender\FoodBundle\Element\LemonBomb element child class this will return
     * "MapbenderFoodBundle:Element:lemon_bomb.html.twig".
     * This would correspond to this file path:
     * <mapbender-root>/src/Mapbender/FoodBundle/Resources/views/Element/lemon_bomb.html.twig
     *
     * @param string $suffix to be appended to the generated path (default: '.html.twig', try '.json.twig' etc)
     * @param string|null $resourceSection if null, will use third namespace component (i.e. first non-bundle component).
     *                    For elements in any conventional Mapbender bundle, this will be "Element".
     *                    We also use "ElementAdmin" in certain places though.
     * @param bool|null $inherit allow inheriting template names from parent class, excluding the (abstract)
     *                  Element class itself; null (default) for auto-decide (blacklist controlled)
     * @return string twig-style Bundle:Section:file_name.ext
     */
    public static function getAutomaticTemplatePath($suffix = '.html.twig', $resourceSection = null, $inherit = null)
    {
        if ($inherit === null) {
            return static::getAutomaticTemplatePath($suffix, $resourceSection, static::autoDetectInheritanceRule());
        }

        if ($inherit) {
            $cls = static::getNonAbstractBaseClassName();
        } else {
            $cls = get_called_class();
        }
        $classParts = explode('\\', $cls);
        $nameWithoutNamespace = implode('', array_slice($classParts, -1));

        $bundleName = implode('', array_slice($classParts, 0, 2));  // e.g. "Mapbender" . "CoreBundle"
        $resourceSection = $resourceSection ?: "Element"; // $classParts[2];
        $resourcePathParts = array_slice($classParts, 3, -1);   // subfolder under section, often empty
        $resourcePathParts[] = static::getTemplateName($nameWithoutNamespace);

        return "{$bundleName}:{$resourceSection}:" . implode('/', $resourcePathParts) . $suffix;
    }

    /**
     * Generates an automatic "AdminType" class name from the element class name.
     *
     * E.g. for a Mapbender\FoodBundle\Element\LemonBomb element child class this will return the string
     * "Mapbender\FoodBundle\Element\Type\LemonBombAdminType"
     *
     * @param bool|null $inherit allow inheriting admin type from parent class, excluding the (abstract)
     *                  Element class itself; null (default) for auto-decide (blacklist controlled)
     * @return string
     */
    public static function getAutomaticAdminType($inherit = null)
    {
        if ($inherit === null) {
            return static::getAutomaticAdminType(static::autoDetectInheritanceRule());
        }
        if ($inherit) {
            $cls = static::getNonAbstractBaseClassName();
        } else {
            $cls = get_called_class();
        }
        $clsInfo = explode('\\', $cls);
        $namespaceParts = array_slice($clsInfo, 0, -1);
        // convention: AdminType classes are placed into the "<bundle>\Element\Type" namespace
        $namespaceParts[] = "Type";
        $bareClassName = implode('', array_slice($clsInfo, -1));
        // convention: AdminType class name is the same as the element class name suffixed with AdminType
        return implode('\\', $namespaceParts) . '\\' . $bareClassName . 'AdminType';
    }

    /**
     * Walk up through the class hierarchy and return the name of the first-generation child class immediately
     * inheriting from the abstract Element.
     *
     * @todo: location
     *
     * @return string fully qualified class name
     */
    protected static function getNonAbstractBaseClassName()
    {
        $nonAbstractBase = get_called_class();
        $abstractRoot = __CLASS__;      // == "Mapbender\CoreBundle\Component\Element"
        while (is_subclass_of($nonAbstractBase, $abstractRoot, true)) {
            $parentClass = get_parent_class($nonAbstractBase);
            if (is_subclass_of($parentClass, $abstractRoot, true)) {
                // parent is still not abstract, good to delegate calls to
                $nonAbstractBase = $parentClass;
            } else {
                // we're down all the way, next parent is now the abstract Element
                break;
            }
        }
        return $nonAbstractBase;
    }

    /**
     * Determines if admin type / template / frontend template will be inherited if one of the getAutomatic*
     * methods is called with inherit = null.
     *
     * We do this because the intuitive default behavior (inherit everything from parent) is incompatible with
     * a certain, small set of elements. These elements are blacklisted for inheritance here. If the calling
     * class is one of them, or a child, we will not inherit admin type / templates (this method will return false).
     * Otherwise we do.
     *
     * @return bool
     */
    protected static function autodetectInheritanceRule()
    {
        $inheritanceBlacklist = array(
            'Mapbender\DataSourceBundle\Element\BaseElement',
            'Mapbender\DigitizerBundle\Element',
        );
        $cls = get_called_class();
        foreach ($inheritanceBlacklist as $noInheritClass) {
            if (is_a($cls, $noInheritClass, true)) {
                return false;
            }
        }
        return true;
    }
}
