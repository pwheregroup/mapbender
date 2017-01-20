<?php

namespace Mapbender\PrintBundle\Utils;


/**
 * Class UnitUtils
 * @package Mapbender\PrintBundle\Utils
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class UnitUtils
{

    const inchInMm = 25.4;
    const inchInCm = self::inchInMm / 10.0;

    public static function convertInchesToMm($inches)
    {
        return $inches / self::inchInMm;
    }

    public static function convertInchesToCm($inches)
    {
        return $inches / self::inchInCm;
    }

    public static function convertMmToInches($mm)
    {
        return $mm * self::inchInMm;
    }

    public static function convertCmToInches($cm)
    {
        return $cm * self::inchInCm;
    }


    public static function convert($unit,$values){

        $result=$values;

        switch (strtolower($unit)) {

            case "mm":
               return array_map(array(UnitUtils::class, "convertInchesToMm"),$values,$result);

            case "cm":
                return array_map(array(UnitUtils::class, "convertInchesToCm"),$values,$result);

        }

        return $result;
    }

}