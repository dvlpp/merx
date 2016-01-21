<?php

namespace Dvlpp\Merx\Facade;

use Illuminate\Support\Facades\Facade;

class Merx extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'merx';
    }
}