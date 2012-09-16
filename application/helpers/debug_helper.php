<?php

if (!defined('debug_helper')) {
    define('debug_helper', true);

    function printr($object){
        echo "<pre>";
        print_r($object);
        echo "</pre>";
    }

}
