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

    $hooks->add_filter('wakeup_advanced', 'example_wakeup_advanced_w_parent');
    $hooks->add_filter('wakeup_advanced', 'example_wakeup_advanced_wo_parent');

    function example_wakeup_advanced_w_parent($ary)
    {
        $ary[] = new AdvancedMessage('Hello!', '0435575c-f3c3-4dd1-8acd-0de249a649df');
        return $ary;
    }

    function example_wakeup_advanced_wo_parent($ary)
    {
        $ary[] = new AdvancedMessage('Hello?');
        return $ary;
    }

    // add_action(action, callback, priority, num_args), priority defaults to 10
    $hooks->add_action('parent_id', 'example_parent_id', 10, 2);

    function example_parent_id($message_id, $parent_id)
    {
        echo "Message with ID ${message_id} is ${parent_id}.", PHP_EOL;
    }

}
