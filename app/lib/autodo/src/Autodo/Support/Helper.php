<?php

namespace Autodo\Support;

class Helper
{
    /**
     * Pretty print the passed variable.
     *
     * @param  dynamic  mixed
     * @return void
     */
    public static function pprint($v)
    {
        echo print_r($v, true);
    }
}