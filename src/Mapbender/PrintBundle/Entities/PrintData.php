<?php

namespace Mapbender\PrintBundle\Entities;


/**
 * Class PrintData
 * @package Mapbender\PrintBundle\Entities
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class PrintData
{


    /**
     * @var float
     */
    private $quality;


    /**
     * @var Extent
     */
    private $mapExtent;

    /**
     * @var Coordinate
     */
    private $center;

    /**
     * @return float
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param float $quality
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
    }

    /**
     * @return Extent
     */
    public function getMapExtent()
    {
        return $this->mapExtent;
    }

    /**
     * @param Extent $mapExtent
     */
    public function setMapExtent($mapExtent)
    {
        $this->mapExtent = $mapExtent;
    }

    /**
     * @return Coordinate
     */
    public function getCenter()
    {
        return $this->center;
    }
    /**
     * @param Coordinate $center
     */
    public function setCenter($center)
    {
        $this->center = $center;
    }


    public function getMapBounds(){

        $mapWidth = $this->getMapExtent()->getWidth();
        $mapHeight =  $this->getMapExtent()->getHeight();

        $centerx =  $this->getCenter()->getX();
        $centery =  $this->getCenter()->getY();

        $minX = $centerx - $mapWidth * 0.5;
        $minY = $centery - $mapHeight * 0.5;
        $maxX = $centerx + $mapWidth * 0.5;
        $maxY = $centery + $mapHeight * 0.5;

        return new Bounds($minX,$maxX,$minY,$maxY);
    }
}