<?php

namespace Mapbender\PrintBundle\Entities;


/**
 * Class Extent
 * @package Mapbender\PrintBundle\Entities
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Extent
{


    /**
     * @var float
     */
    private $width;
    /**
     * @var float
     */
    private $height;
    /**
     * @var Coordinate
     */
    private $offset;

    /**
     * Extent constructor.
     * @param Coordinate $offset
     * @param float $width
     * @param float $height
     */
    public function __construct($width, $height,Coordinate $offset=NULL)
    {
        $this->width = $width;
        $this->height = $height;
        $this->offset = $offset;
    }


    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param float $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param float $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }
}