<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\ManagerBundle\Component\Mapper;
use Mapbender\PrintBundle\Component\OdgParser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\PrintBundle\Component\PrintService;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 *
 */
class PrintClient extends Element
{

    public static $merge_configurations = false;

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.printclient.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.printclient.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.printclient.tag.print",
            "mb.core.printclient.tag.pdf",
            "mb.core.printclient.tag.png",
            "mb.core.printclient.tag.gif",
            "mb.core.printclient.tag.jpg",
            "mb.core.printclient.tag.jpeg");
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array('js'    => array('mapbender.element.printClient.js',
                                      '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                                      '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
                     'css'   => array('@MapbenderCoreBundle/Resources/public/sass/element/printclient.scss'),
                     'trans' => array('MapbenderCoreBundle:Element:printclient.json.twig'));
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "templates" => array(
                array(
                    'template' => "a4portrait",
                    "label" => "A4 Portrait")
                ,
                array(
                    'template' => "a4landscape",
                    "label" => "A4 Landscape")
                ,
                array(
                    'template' => "a3portrait",
                    "label" => "A3 Portrait")
                ,
                array(
                    'template' => "a3landscape",
                    "label" => "A3 Landscape")
                ,
                array(
                    'template' => "a4_landscape_offical",
                    "label" => "A4 Landscape offical"),
                array(
                    'template' => "a2_landscape_offical",
                    "label" => "A2 Landscape offical")
            ),
            "scales" => array(500, 1000, 5000, 10000, 25000),
            "quality_levels" => array(array('dpi' => "72", 'label' => "Draft (72dpi)"),
                array('dpi' => "288", 'label' => "Document (288dpi)")),
            "rotatable" => true,
            "legend" => true,
            "legend_default_behaviour" => true,
            "optional_fields" => array(
                "title" => array("label" => 'Title', "options" => array("required" => false)),
                "comment1" => array("label" => 'Comment 1', "options" => array("required" => false)),
                "comment2" => array("label" => 'Comment 2', "options" => array("required" => false))),
            "replace_pattern" => null,
            "file_prefix" => 'mapbender3'
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();
        if (isset($config["templates"])) {
            $templates = array();
            foreach ($config["templates"] as $template) {
                $templates[$template['template']] = $template;
            }
            $config["templates"] = $templates;
        }
        if (isset($config["quality_levels"])) {
            $levels = array();
            foreach ($config["quality_levels"] as $level) {
                $levels[$level['dpi']] = $level['label'];
            }
            $config["quality_levels"] = $levels;
        }
        if (!isset($config["type"])) {
            $config["type"] = "dialog";
        }
        return $config;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\PrintClientAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:printclient.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbPrintClient';
    }

    /**
     * @inheritdoc
     */
    public function render($options=array())
    {
        $configuration = $this->getConfiguration();
        $templates = $configuration["templates"];
        return $this->container->get('templating')->render(
            'MapbenderCoreBundle:Element:printclient.html.twig',
            array(
                'id' => $this->getId(),
                'title' => $this->getTitle(),
                'configuration' => $configuration,
                'templates' => $templates
            )
        );
    }

    /**
     * helper for debug to php error log
     *
     * @author Jochen Schultz <jochen.schultz@wheregroup.com>
     *
     * @param $anytype
     *
     * @return boolean
     */
    public static function tempDebugToPhpErrorLog($anytype) {
        ob_start();
        var_dump($anytype);
        $out = ob_get_clean();
        trigger_error($out);
        return false;
    }

    /**
     * @param $options
     * @return bool|\stdClass
     */
    private function getTemplatesSelectFromFeatureType($options) {

        // @todo: default Templates from Configuration

        // Templates from featureType configuration in parameters.yml
        $featuretypes = $this->container->getParameter('featureTypes');
        // self::tempDebugToPhpErrorLog($featuretypes[$options["featureType"]]['print']['templates']);
        if (isset($featuretypes[$options["featureType"]])
            && isset($featuretypes[$options["featureType"]]['print'])
            && isset($featuretypes[$options["featureType"]]['print']['templates'])
            && isset($featuretypes[$options["featureType"]]['print']['templates'][0])
            && isset($featuretypes[$options["featureType"]]['print']['templates'][0]['name'])
        ) {
            $templates = new \stdClass();
            $templates->type  = 'select';
            $templates->title = 'Vorlage';
            $templates->name = 'template';
            $templates->cssClass = 'template';
            $templates->value = $featuretypes[$options["featureType"]]['print']['templates'][0]['name'];
            foreach($featuretypes[$options["featureType"]]['print']['templates'] as $option) {
                $templates->options[$option['name']] = $option['title'];
            }
            return $templates;
        } else {
            return false;
        }
    }

    private function getScalesSelectFromConfiguration() {
        $configuration = $this->getConfiguration();
        if (!isset($configuration['scales'])) {
            $configuration = self::getDefaultConfiguration();
        }
        //self::tempDebugToPhpErrorLog($configuration);
        if (isset($configuration['scales'])&&isset($configuration['scales'][0])) {
            $scales = new \stdClass();
            $scales->type  = 'select';
            $scales->title = 'Maßstab';
            $scales->name = 'scale_select';
            $scales->cssClass = 'scale';
            $scales->value = $configuration['scales'][0];
            for($i=0,$cnt=count($configuration['scales']);$i<$cnt;$i++) {
                $scales->options[$configuration['scales'][$i]] = '1:'.$configuration['scales'][$i];
            }
            return $scales;
        } else {
            return false;
        }
    }

    private function getQualitySelectFromConfiguration() {
        $configuration = $this->getConfiguration();
        if (!isset($configuration['quality_levels'])) {
            $configuration = self::getDefaultConfiguration();
        }
        //self::tempDebugToPhpErrorLog($configuration['quality_levels']);
        if (isset($configuration['quality_levels'])&&is_array($configuration['quality_levels'])) {
            $quality_levels = new \stdClass();
            $quality_levels->type  = 'select';
            $quality_levels->title = 'Qualität';
            $quality_levels->name = 'quality';
            $quality_levels->cssClass = 'quality';
            $quality_levels->value = key($configuration['quality_levels']);
            $quality_levels->options = $configuration['quality_levels'];
            return $quality_levels;
        } else {
            return false;
        }
    }

    private function createFormField($type,$title,$name,$cssclass='',$value='') {
        $input = new \stdClass();
        $input->type  = $type;
        if (!empty($cssclass)) {
            $input->cssClass = $cssclass;
        }
        $input->title = $title;
        $input->name = $name;
        $input->value = $value;
        return $input;
    }

    /**
     * suitable for formgenerator
     *
     * @param array $options
     * @return array namefields used as children of formgenerator type form -> mapbender.element.prinClient.js
     * @throws \Exception
     */
    public function getPrintFeatureDialogJson($options=array())
    {
        if (!isset($options["featureType"])) {
            throw new \Exception('FeatureType missing');
        }
        $nameFields = array();

        $templates = $this->getTemplatesSelectFromFeatureType($options);
        if (!$templates) {
            throw new \Exception('FeatureType has no print template');
        } else {
            $nameFields['templates'] = $templates;
        }

        $quality = $this->getQualitySelectFromConfiguration();
        if (!$quality) {
            throw new \Exception('FeatureType has no print quality');
        } else {
            $nameFields['quality'] = $quality;
        }

        $scales = $this->getScalesSelectFromConfiguration();
        if (!$scales) {
            throw new \Exception('FeatureType has no print scale');
        } else {
            $nameFields['scales'] = $scales;
        }

        $nameFields['rotation'] = $this->createFormField('input','Drehung','rotation','rotation',0);
        $nameFields['comment1'] = $this->createFormField('input','Kommentar 1','comment1','comment1');
        $nameFields['comment2'] = $this->createFormField('input','Kommentar 2','comment2','comment2');
        $nameFields['hiddensubmit'] = $this->createFormField('submit','abschicken','submit','hidden');

        $nameFields['printlegend'] = $this->createFormField('checkbox','Legende drucken','printLegend','printlegend');

        $response = array('nameFields' => $nameFields);
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        $request = $this->container->get('request');
        $configuration = $this->getConfiguration();
        switch ($action) {

            case 'printFeatureDialog':

                $options = ['featureId' => $_REQUEST['featureId'], 'featureType' => $_REQUEST['featureType']];

                // $featureTypeManager = $this->container->get("features");
                // $feature            = $featureTypeManager->get($options["featureType"])->getById($options['featureId']);
                // $featureData        = $feature->getAttributes();

                return new JsonResponse($this->getPrintFeatureDialogJson($options));

            case 'print':

                $data = $request->request->all();

                foreach ($data['layers'] as $idx => $layer) {
                    $data['layers'][$idx] = json_decode($layer, true);
                }

                if (isset($data['overview'])) {
                    foreach ($data['overview'] as $idx => $layer) {
                        $data['overview'][$idx] = json_decode($layer, true);
                    }
                }

                if (isset($data['features'])) {
                    foreach ($data['features'] as $idx => $value) {
                        $data['features'][$idx] = json_decode($value, true);
                    }
                }

                if (isset($configuration['replace_pattern'])) {
                    foreach ($configuration['replace_pattern'] as $idx => $value) {
                        $data['replace_pattern'][$idx] = $value;
                    }
                }

                if (isset($data['extent_feature'])) {
                    $data['extent_feature'] = json_decode($data['extent_feature'], true);
                }

                if (isset($data['legends'])) {
                    $data['legends'] = json_decode($data['legends'], true);
                }

                $printservice = new PrintService($this->container);

                $displayInline = true;
                $filename = 'mapbender_print.pdf';
                if(array_key_exists('file_prefix', $configuration)) {
                    $filename = $configuration['file_prefix'] . '_' . date("YmdHis") . '.pdf';
                }
                $response = new Response($printservice->doPrint($data), 200, array(
                    'Content-Type' => $displayInline ? 'application/pdf' : 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename=' . $filename
                ));

                return $response;

            case 'getTemplateSize':
                $template = $request->get('template');
                $odgParser = new OdgParser($this->container);
                $size = $odgParser->getMapSize($template);

                return new Response($size);
        }
    }
}
