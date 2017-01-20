<?php


namespace Mapbender\PrintBundle\Utils;


use Mapbender\PrintBundle\Entities\Coordinate;
use Mapbender\PrintBundle\Entities\Extent;
use Mapbender\PrintBundle\Entities\PrintConfiguration;
use Mapbender\PrintBundle\Entities\PrintData;

/**
 * Class CoordinateUtils
 * @package Mapbender\PrintBundle\Utils
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class CoordinateUtils
{

    public static function convertRealWorldToMapCoordinates(PrintData $printData, Coordinate $realWorldCoordinate, PrintConfiguration $printConfiguration)
    {

        $quality = $printData->getQuality();
        $mapBounds = $printData->getMapBounds();

        $scaleX = self::getFraction($realWorldCoordinate->getX(), $mapBounds->getMinX(), $mapBounds->getWidth());
        $scaleY = self::getFraction($mapBounds->getMaxY(), $realWorldCoordinate->getY(), $mapBounds->getHeight());

        $x = $scaleX * $printConfiguration->getMap()->getWidth() * $quality;
        $y = $scaleY * $printConfiguration->getMap()->getHeight() * $quality;

        return UnitUtils::convert($printConfiguration->getUnit(), array($x, $y));
    }

    public static function realWorld2ovMapPos($ovWidth, $ovHeight, $rw_x, $rw_y)
    {
        $quality = $this->data['quality'];
        $centerx = $this->data['center']['x'];
        $centery = $this->data['center']['y'];
        $minX = $centerx - $ovWidth * 0.5;
        $minY = $centery - $ovHeight * 0.5;
        $maxX = $centerx + $ovWidth * 0.5;
        $maxY = $centery + $ovHeight * 0.5;
        $extentx = $maxX - $minX;
        $extenty = $maxY - $minY;
        $pixPos_x = (($rw_x - $minX) / $extentx) * round($this->conf['overview']['width'] / 25.4 * $quality);
        $pixPos_y = (($maxY - $rw_y) / $extenty) * round($this->conf['overview']['height'] / 25.4 * $quality);

        return array($pixPos_x, $pixPos_y);
    }

    public static function realWorld2rotatedMapPos($rw_x, $rw_y)
    {
        $centerx = $this->data['center']['x'];
        $centery = $this->data['center']['y'];
        $minX = $centerx - $this->neededExtentWidth * 0.5;
        $minY = $centery - $this->neededExtentHeight * 0.5;
        $maxX = $centerx + $this->neededExtentWidth * 0.5;
        $maxY = $centery + $this->neededExtentHeight * 0.5;
        $extentx = $maxX - $minX;
        $extenty = $maxY - $minY;
        $pixPos_x = (($rw_x - $minX) / $extentx) * $this->neededImageWidth;
        $pixPos_y = (($maxY - $rw_y) / $extenty) * $this->neededImageHeight;

        return array($pixPos_x, $pixPos_y);
    }

    private static function getFraction($maxA, $minA, $max)
    {
        return ($maxA - $minA) / $max;
    }

}