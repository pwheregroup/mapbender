<?php


namespace Mapbender\PrintBundle\Entities;

/**
 * Class Coordinate
 * @package Mapbender\PrintBundle\Entities
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Coordinate
{

    /**
     * @var float
     */
    protected $x;
    
    /**
     * @var float
     */
    protected $y;

    /**
     * @var float
     */
    protected $z;

    /**
     * Coordinate constructor.
     * @param $x
     * @param $y
     */
    public function __construct($x=0, $y=0, $z=0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }


    /**
     * @return float
     */
    public function getZ()
    {
        return $this->z;
    }

    /**
     * @param float $z
     */
    public function setZ($z)
    {
        $this->z = $z;
    }


    /**
     * @return float
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @param float $x
     */
    public function setX($x)
    {
        $this->x = $x;
    }

    /**
     * @return float
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param float $y
     */
    public function setY($y)
    {
        $this->y = $y;
    }

}