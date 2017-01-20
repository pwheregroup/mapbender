<?php

namespace Mapbender\PrintBundle\Entities;


/**
 * Class Bounds
 * @package Mapbender\PrintBundle\Entities
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Bounds
{

    /**
     * Bounds constructor.
     * @param float $minX
     * @param float $maxX
     * @param float $minY
     * @param float $maxY
     */
    public function __construct($minX = 0, $maxX = 0, $minY = 0, $maxY = 0)
    {
        $this->minX = $minX;
        $this->maxX = $maxX;
        $this->minY = $minY;
        $this->maxY = $maxY;
    }

    public static function from(Coordinate $center, $width, $height, $xRatio = 0.5, $yRatio = 0.5)
    {

        list($minX, $minY, $maxX, $maxY) = array($center->getX() - $width * $xRatio, $center->getY() - $height * $yRatio, $center->getX() + $width * $xRatio, $center->getY() + $height * $yRatio);

        return new Bounds($minX, $maxX, $minY, $maxY);
    }

    /**
     * @var float
     */
    private $minX;
    /**
     * @var float
     */
    private $maxX;
    /**
     * @var float
     */
    private $minY;
    /**
     * @var float
     */
    private $maxY;

    /**
     * @return float
     */
    public function getMinX()
    {
        return $this->minX;
    }

    /**
     * @param float $minX
     */
    public function setMinX($minX)
    {
        $this->minX = $minX;
    }

    /**
     * @return float
     */
    public function getMaxX()
    {
        return $this->maxX;
    }

    /**
     * @param float $maxX
     */
    public function setMaxX($maxX)
    {
        $this->maxX = $maxX;
    }

    /**
     * @return float
     */
    public function getMinY()
    {
        return $this->minY;
    }

    /**
     * @param float $minY
     */
    public function setMinY($minY)
    {
        $this->minY = $minY;
    }

    /**
     * @return float
     */
    public function getMaxY()
    {
        return $this->maxY;
    }

    /**
     * @param float $maxY
     */
    public function setMaxY($maxY)
    {
        $this->maxY = $maxY;
    }


    /**
     * @return float|int
     */
    public function getWidth()
    {
        return $this->maxX - $this->minX;
    }

    /**
     * @return float|int
     */
    public function getHeight()
    {
        return $this->maxY - $this->minY;
    }

}