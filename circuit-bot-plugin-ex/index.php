<?php
require_once(__DIR__ . '/vendor/autoload.php');

if(!function_exists('example_wakeup'))
{
    global $hooks;

    $hooks->add_filter('wakeup', 'example_wakeup');

    function example_wakeup($ary){
        $ary[] = 'External filter';
        return $ary;
    }
}
