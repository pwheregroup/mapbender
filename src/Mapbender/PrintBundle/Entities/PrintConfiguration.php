<?php

namespace Mapbender\PrintBundle\Entities;


/**
 * Class PrintConfiguration
 * @package Mapbender\PrintBundle\Entities
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class PrintConfiguration
{


    /**
     * @var Extent
     */
    private $map;

    /**
     * @var string
     */
    private $unit = "cm";

    /**
     * @return Extent
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * @param Extent $map
     */
    public function setMap($map)
    {
        $this->map = $map;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

}