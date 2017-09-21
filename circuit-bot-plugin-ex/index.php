<?php

if(!function_exists('example_wakeup'))
{
    global $hooks;

    /**
     * Record a message to our plugin state
     */
    function example_mrec($mes)
    {
        global $plugin_states;
        $plugin_states['ciis0.example']['msg_ids'][] = $mes->id;
    }

    $hooks->add_action(ACTION_PLG_INIT, 'example_init');

    function example_init()
    {
        global $plugin_states;

        $plugin_states['ciis0.example'] = [
            'msg_ids' => []
        ];
    }

    $hooks->add_action('wakeup', 'example_wakeup');

    function example_wakeup(){
        circuit_send_message("External filter");
    }

    $hooks->add_action('wakeup_advanced', 'example_wakeup_advanced_w_parent');
    $hooks->add_action('wakeup_advanced', 'example_wakeup_advanced_wo_parent');
    $hooks->add_action('wakeup_advanced', 'example_wakeup_advanced_w_conv_id');
    $hooks->add_action('wakeup_advanced', 'example_wakeup_advanced_w_title');

    function example_wakeup_advanced_w_parent()
    {
        global $config;
        circuit_send_message_adv(new AdvancedMessage('Hello!', $config['plugins']['example']['parent_id']));
    }

    function example_wakeup_advanced_wo_parent()
    {
        global $plugin_states;

        $msg = new AdvancedMessage('Hello?');
        example_mrec($msg); // to be able to determine if it's ours, see example_parent_id

        circuit_send_message_adv($msg);
    }

    function example_wakeup_advanced_w_conv_id()
    {
        global $config;

        $mes = new AdvancedMessage('Hello!');
        $mes->conv_id = $config['plugins']['example']['conv_id'];

        circuit_send_message_adv($mes);
    }

    // add_action(action, callback, priority, num_args), priority defaults to 10
    $hooks->add_action('parent_id', 'example_parent_id', 10, 2);

    function example_parent_id($message_id, $parent_id)
    {

        global $plugin_states;

        echo "Message with ID ${message_id} is ${parent_id}.", PHP_EOL,
            'It\'s ' . (in_array($message_id, $plugin_states['ciis0.example']['msg_ids']) ? '' : 'not ' ) . 'ours.', PHP_EOL;
    }

    function example_wakeup_advanced_w_title()
    {
        global $plugin_states;

        $mes = new AdvancedMessage('Hello.');
        $mes->title = 'Title.';

        example_mrec($mes);

        circuit_send_message_adv($mes);
    }

}
