<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfiguration;
use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Description of WmsInstanceConfiguration
 *
 * @author Paul Schmidt
 *
 * @property WmsInstanceConfigurationOptions $options
 */
class WmsInstanceConfiguration extends InstanceConfiguration
{

    /**
     * Sets options
     * 
     * @param InstanceConfigurationOptions $options ServiceConfigurationOptions
     * @return $this
     */
    public function setOptions(InstanceConfigurationOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Returns options
     * 
     * @return WmsInstanceConfigurationOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     *
     * @param array $children
     * @return InstanceConfiguration 
     */
    public function setChildren($children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     *
     * @return array children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            "type" => $this->type,
            "title" => $this->title,
            "isBaseSource" => $this->isBaseSource,
            "options" => $this->options->toArray(),
            "children" => $this->children
        );
    }

    /**
     * @param WmsInstance $instance
     * @param bool $strict
     * @return null|static
     */
    public static function fromEntity(WmsInstance $instance, $strict = true)
    {
        $options = array(
            'type' => strtolower($instance->getType()),
            'title' => $instance->getTitle(),
            'isBaseSource' => $instance->isBaseSource(),
            'options' => WmsInstanceConfigurationOptions::fromEntity($instance),
        );
        return static::fromArray($options, $strict);
    }
}
