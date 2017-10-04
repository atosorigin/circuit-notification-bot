<?php

use ICanBoogie\Storage\FileStorage;

if(!function_exists('circuit_bot'))
{
    define('TOKEN_ENDPOINT', 'https://eu.yourcircuit.com/oauth/token');

    define('ACTION_WAKEUP', 'wakeup');
    define('ACTION_WAKEUP_ADV', 'wakeup_advanced');

    define('ACTION_PLG_INIT', 'init_plugins');
    define('ACTION_PARENT_ID', 'parent_id');

    function print_conv_item($conv_item)
    {
        echo 'Message...', PHP_EOL,
            'ID      ', $conv_item['item_id'], PHP_EOL,
            'Content ', $conv_item['text']['content'], PHP_EOL, PHP_EOL;
    }

    function hooks_only($config)
    {
        return isset($config['hooks_only']) && $config['hooks_only'];
    }

    function circuit_bot($the_config)
    {
        global $hooks;
        global $config;
        global $plugin_states;

        $config = $the_config; // make config available in filters and actions
        $plugin_states = [];
        $hooks_only = hooks_only($config);

        if(isset($config['client']) && isset($config['client']['id']) && isset($config['client']['secret']))
        {
            $storage = new FileStorage(__DIR__);
            $token_key = 'token_' . $config['client']['id'];

            // Try to reuse OAuth token, request new one if expired.
            if($response = $storage->retrieve($token_key))
            {
                echo 'Token loaded', PHP_EOL;
                $token = $response['access_token'];
            }
            else
            {
                echo 'No token found, requesting new one...', PHP_EOL;

                $response = (new OAuth2\Client($config['client']['id'], $config['client']['secret']))
                    ->getAccessToken(TOKEN_ENDPOINT, 'client_credentials', ['scope' => 'ALL'])
                    ['result'];

                $storage->store($token_key, $response, $response['expires_in'] - 10 /* just to be sure */);

                $token = $response['access_token'];
            }

            // Configure OAuth2 access token for authorization
            Swagger\Client\Configuration::getDefaultConfiguration()->setAccessToken($token);

        }
        elseif(!$hooks_only)
        {
            die('Missing OAuth Client-ID and/or Client secret!');
        }

        $config['api.messaging.basic'] = new Swagger\Client\Api\MessagingBasicApi;

        echo 'Initializing plugins', PHP_EOL;

        $hooks->do_action(ACTION_PLG_INIT);

        echo 'Running hooks', PHP_EOL;

        $wakeup = $hooks->do_action(ACTION_WAKEUP);
        $wakeup_advanced = $hooks->do_action(ACTION_WAKEUP_ADV);

        echo 'Done.', PHP_EOL;

        if($hooks_only)
        {
            echo 'Ran only hooks, as requested by $config[\'hooks_only\']', PHP_EOL,
                'hooks:', PHP_EOL;
            print_r($hooks);
            return;
        }

    }

    function circuit_message_truncate($msg)
    {
        if(strlen($msg_adv->message))
        {
            $toolongmsg = '...';
            return substr($msg, 0, 2048 - strlen($toolongmsg)) . $toolongmsg;
        }
        else
        {
            return $msg;
        }
    }

    function circuit_send_message($content)
    {
        global $config;

        if(hooks_only($config)) return;

        $content = circuit_message_truncate($content);

        try
        {
            print_conv_item($config['api.messaging.basic']->addTextItem($config['conv_id'], $content));
        }
        catch (Exception $e)
        {
            echo 'Exception when calling MessagingBasicApi->addTextItem: ', $e->getMessage(), PHP_EOL;
        }
    }

    function circuit_send_message_adv(AdvancedMessage $msg_adv)
    {

        global $config;
        global $hooks;

        if(hooks_only($config)) return;

        $api_instance = $config['api.messaging.basic'];
        $conv_id = $config['conv_id'];

        $content = circuit_message_truncate($msg_adv->message);

        try
        {
            if($msg_adv->parent)
            {
                $result = $api_instance->addTextItemWithParent($msg_adv->conv_id ? $msg_adv->conv_id : $conv_id, $msg_adv->parent, $content, [ /* attachments */ ], $msg_adv->title);
            }
            else
            {
                global $hooks;

                $result = $api_instance->addTextItem($conv_id, $content, [ /* attachments */ ], $msg_adv->title);

                $hooks->do_action(ACTION_PARENT_ID, $msg_adv->id, $result['item_id']);

            }
            print_conv_item($result);
        }
        catch (Exception $e)
        {
            echo 'Exception when calling MessagingBasicApi->addTextItem/addTextItemWithParent: ', $e->getMessage(), PHP_EOL;
        }
    }

    // This is mainly a structure, not an encapsulated container.
    class AdvancedMessage
    {
        public $parent;
        public $message;
        public $title;
        public $id;
        public $conv_id;

        private static $nextId = 0;

        public function __construct($message, $parent = null)
        {
            $this->id = AdvancedMessage::$nextId++;
            $this->message = $message;
            $this->parent  = $parent;
        }

        /**
         * Record an ID in plugins' state for later use.
         */
        public function record_id($plg, $key = 'msg_ids')
        {
            global $plugin_states;
            $plugin_states[$plg][$key][] = $this->id; // the array should be initialized by plugin
        }

    }
}
