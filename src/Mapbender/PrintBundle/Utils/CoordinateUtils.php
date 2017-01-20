<?php


namespace Mapbender\PrintBundle\Utils;


use Mapbender\PrintBundle\Entities\Bounds;
use Mapbender\PrintBundle\Entities\Coordinate;
use Mapbender\PrintBundle\Entities\PrintConfiguration;
use Mapbender\PrintBundle\Entities\PrintData;

/**
 * Class CoordinateUtils
 * @package Mapbender\PrintBundle\Utils
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class CoordinateUtils
{

    public static function convertRealWorldToMapCoordinates(PrintConfiguration $printConfiguration, PrintData $printData, Coordinate $realWorldCoordinate)
    {

        $quality = $printData->getQuality();
        $mapBounds = $printData->getMapBounds();

        $scaleX = self::getFraction($realWorldCoordinate->getX(), $mapBounds->getMinX(), $mapBounds->getWidth());
        $scaleY = self::getFraction($mapBounds->getMaxY(), $realWorldCoordinate->getY(), $mapBounds->getHeight());

        $x = $scaleX * $printConfiguration->getMap()->getWidth() * $quality;
        $y = $scaleY * $printConfiguration->getMap()->getHeight() * $quality;

        return self::round(UnitUtils::convert($printConfiguration->getUnit(), array($x, $y)));
    }

    public static function convertRealWorldToOverviewMapCoordinates(PrintConfiguration $printConfiguration, PrintData $printData, Coordinate $realWorldCoordinate, $ovWidth, $ovHeight)
    {

        $quality = $printData->getQuality();

        $bounds = Bounds::from($printData->getCenter(), $ovWidth, $ovHeight);

        $scaleX = self::getFraction($realWorldCoordinate->getX(), $bounds->getMinX(), $bounds->getWidth());
        $scaleY = self::getFraction($bounds->getMaxY(), $realWorldCoordinate->getY(), $bounds->getHeight());


        $x = $scaleX * $printConfiguration->getMap()->getWidth() * $quality;
        $y = $scaleY * $printConfiguration->getMap()->getHeight() * $quality;

        return self::round(UnitUtils::convert($printConfiguration->getUnit(), array($x, $y)));
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


    private static function round(array &$array)
    {
        return array_map("round", $array);
    }


    private static function getFraction($maxA, $minA, $max)
    {
        return ($maxA - $minA) / $max;
    }

}