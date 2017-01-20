<?php


namespace Mapbender\PrintBundle\Utils;


use Mapbender\PrintBundle\Entities\Bounds;
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

    public static function convertRealWorldToOverviewMapCoordinates(PrintConfiguration $printConfiguration, PrintData $printData, Coordinate $realWorldCoordinate, Bounds $overViewCoordinate)
    {

        $quality = $printData->getQuality();
        $bounds = Bounds::from($printData->getCenter(), $overViewCoordinate->getWidth(), $overViewCoordinate->getHeight());

        $scaleX = self::getFraction($realWorldCoordinate->getX(), $bounds->getMinX(), $bounds->getWidth());
        $scaleY = self::getFraction($bounds->getMaxY(), $realWorldCoordinate->getY(), $bounds->getHeight());


        $x = $scaleX * $printConfiguration->getMap()->getWidth() * $quality;
        $y = $scaleY * $printConfiguration->getMap()->getHeight() * $quality;

        return self::round(UnitUtils::convert($printConfiguration->getUnit(), array($x, $y)));
    }


    private static function getRotatedExtent(Extent $mapExtent, $rotation)
    {

        $extentWidth = $mapExtent->getWidth();
        $extentHeight = $mapExtent->getHeight();

        $calculatedExtentWidth =
            abs(sin(deg2rad($rotation)) * $extentHeight) +
            abs(cos(deg2rad($rotation)) * $extentWidth);

        $calculatedExtentHeight =
            abs(sin(deg2rad($rotation)) * $extentWidth) +
            abs(cos(deg2rad($rotation)) * $extentHeight);

        return new Extent($calculatedExtentWidth, $calculatedExtentHeight);
    }


    public static function convertRealWorldToRotatedMapCoordinates(PrintConfiguration $printConfiguration, PrintData $printData, Coordinate $realWorldCoordinate)
    {
        $mapExtent = $printData->getMapExtent();

        $rotatedExtent = self::getRotatedExtent($mapExtent, $printData->getRotation());

        $bounds = Bounds::from($printData->getCenter(), $rotatedExtent->getWidth(), $rotatedExtent->getHeight());

        // TODO: build the WMS Size Parameters
        // $width = '&WIDTH=' . $neededImageWidth;
        // $height = '&HEIGHT=' . $neededImageHeight;

        $scaleX = self::getFraction($realWorldCoordinate->getX(), $bounds->getMinX(), $bounds->getWidth());
        $scaleY = self::getFraction($bounds->getMaxY(), $realWorldCoordinate->getY(), $bounds->getHeight());

        $x = $scaleX * $rotatedExtent->getWidth();
        $y = $scaleY * $rotatedExtent->getHeight();

        return self::round(UnitUtils::convert($printConfiguration->getUnit(), array($x, $y)));
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