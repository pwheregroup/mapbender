<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Element\Type\DimensionsHandlerAdminType;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Dimensions handler
 * @author Paul Schmidt
 */
class DimensionsHandler extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.wms.dimhandler.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.wms.dimhandler.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array("mb.wms.dimhandler.dimension", "mb.wms.dimhandler.handler");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "",
            "target" => null,
            'dimensionsets' => array()
            
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbDimensionsHandler';
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                'mapbender.wms.dimension.js',
                'mapbender.element.dimensionshandler.js',
            ),
            'css' => array(
                '@MapbenderWmsBundle/Resources/public/sass/element/dimensionshandler.scss',
                '@MapbenderCoreBundle/Resources/public/sass/element/mbslider.scss'
            ),
            'trans' => array('MapbenderWmsBundle:Element:dimensionshandler.json.twig')
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmsBundle\Element\Type\DimensionsHandlerAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmsBundle:ElementAdmin:dimensionshandler.html.twig';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderWmsBundle:Element:dimensionshandler.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        foreach ($configuration['dimensionsets'] as $key => &$value) {
            /** @var DimensionInst $x */
            /** @see DimensionsHandlerAdminType::buildForm() */
            $x = $value['dimension'];
            $value['dimension'] = $x->getConfiguration();
        }
        return $configuration;
    }

    public function updateAppConfig($config)
    {
        $dhConfig = $this->getConfiguration();
        if (empty($dhConfig['dimensionsets'])) {
            return $config;
        }
        $dimensionMap = array();
        foreach ($dhConfig['dimensionsets'] as $key => $value) {
            for ($i = 0; isset($value['group']) && count($value['group']) > $i; $i++) {
                $item = explode("-", $value['group'][$i]);
                $dimensionMap[$item[0]] = $value['dimension'];
            }
        }
        if (empty($dimensionMap)) {
            return $config;
        }

        foreach ($config['layersets'] as &$layerList) {
            foreach ($layerList as &$layerMap) {
                foreach ($layerMap as $layerId => &$layerDef) {
                    if (empty($dimensionMap[$layerId]) || empty($layerDef['configuration']['options']['dimensions'])) {
                        // layer is not controllable through DimHandler, leave its config alone
                        continue;
                    }
                    $this->updateDimensionConfig($layerDef['configuration']['options']['dimensions'], $dimensionMap[$layerId]);
                }
            }
        }
        return $config;
    }

    /**
     * Updates the $target list of dimension config arrays by reference with our own settings (from backend).
     *
     * @param mixed[] $target
     * @param mixed[] $dimensionConfig
     */
    public static function updateDimensionConfig(&$target, $dimensionConfig)
    {
        foreach ($target as &$dimensionDef) {
            if ($dimensionDef['type'] == $dimensionConfig['type']) {
                $dimensionDef['extent'] = $dimensionConfig['extent'];
                $dimensionDef['default'] = $dimensionConfig['default'];
            }
        }
    }


    /**
     * Copies Extent and Default from passed DimensionInst to any DimensionInst stored
     * in given SourceInstance->dimensions, if they match the same Type.
     *
     * @param SourceInstance|WmsInstance $instance
     * @param DimensionInst $referenceDimension
     * @deprecated was only used by DimensionsHandler::postSave, which was removed
     *             now a dangling api fulfillment for WmsInstanceEntityHandler::
     */
    public static function reconfigureDimensions(SourceInstance $instance, DimensionInst $referenceDimension)
    {
        foreach ($instance->getDimensions() as $dim) {
            if ($dim->getType() === $referenceDimension->getType()) {
                $dim->setExtent($referenceDimension->getExtent());
                $dim->setDefault($referenceDimension->getDefault());
            }
        }
        $instance->setDimensions($instance->getDimensions());
    }
}
