<?php

namespace Dvlpp\Merx\Utils;

use Dvlpp\Merx\Utils\OrderRefGenerator\DateAndDayIncrement;
use Dvlpp\Merx\Utils\OrderRefGenerator\DateAndIncrement;
use Dvlpp\Merx\Utils\OrderRefGenerator\Increment;
use Dvlpp\Merx\Utils\OrderRefGenerator\OrderRefGenerator;

class OrderRefGeneratorFactory
{
    static protected $generators = [
        "increment" => Increment::class,
        "date-and-increment" => DateAndIncrement::class,
        "date-and-day-increment" => DateAndDayIncrement::class,
    ];

    /**
     * @param string $generatorName
     *
     * @return OrderRefGenerator
     */
    public static function create($generatorName)
    {
        if (isset(static::$generators[$generatorName])) {
            $generatorClass = static::$generators[$generatorName];
        } else {
            $generatorClass = app($generatorName);
        }

        return new $generatorClass;
    }
}