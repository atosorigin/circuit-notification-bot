<?php

use ICanBoogie\Storage\FileStorage;

if(!function_exists('circuit_bot'))
{
    define('TOKEN_ENDPOINT', 'https://eu.yourcircuit.com/oauth/token');

    define('FILTER_WAKEUP', 'wakeup');
    define('FILTER_WAKEUP_ADV', 'wakeup_advanced');

    define('ACTION_PARENT_ID', 'parent_id');

    function circuit_bot($the_config)
    {
        global $hooks;
        global $config;

        $config = $the_config; // make config available in filters and actions
        $hooks_only = isset($config['hooks_only']) && $config['hooks_only'];

        function print_conv_item($conv_item)
        {
            echo 'Message...', PHP_EOL,
                'ID      ', $conv_item['item_id'], PHP_EOL,
                'Content ', $conv_item['text']['content'], PHP_EOL, PHP_EOL;
        }

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

        echo 'Running hooks', PHP_EOL;

        $wakeup = $hooks->apply_filters(FILTER_WAKEUP, []);
        $wakeup_advanced = $hooks->apply_filters(FILTER_WAKEUP_ADV, []);

        echo 'Done.', PHP_EOL;

        if($hooks_only)
        {
            echo 'Ran only hooks, as requested by $config[\'hooks_only\']', PHP_EOL,
                'hooks:', PHP_EOL;
            print_r($hooks);

            echo 'wakeup result', PHP_EOL;
            print_r($wakeup);

            echo 'wakeup_advanced result', PHP_EOL;
            print_r($wakeup_advanced);

            return;
        }

        $conv_id = $config['conId'];
        $api_instance = new Swagger\Client\Api\MessagingBasicApi();

        foreach($wakeup as $key => $content)
        {
            try
            {
                print_conv_item($result = $api_instance->addTextItem($conv_id, $content));
            }
            catch (Exception $e)
            {
                echo 'Exception when calling MessagingBasicApi->addTextItem: ', $e->getMessage(), PHP_EOL;
            }

        }

        foreach($wakeup_advanced as $msg_adv)
        {
            try
            {
                if($msg_adv->parent)
                {
                    $result = $api_instance->addTextItemWithParent($msg_adv->conv_id ? $msg_adv->conv_id : $conv_id, $msg_adv->parent, $msg_adv->message);
                }
                else
                {
                    global $hooks;

                    $result = $api_instance->addTextItem($conv_id, $msg_adv->message);

                    $hooks->do_action(ACTION_PARENT_ID, $msg_adv->id, $result['item_id']);

                }
                print_conv_item($result);
            }
            catch (Exception $e)
            {
                echo 'Exception when calling MessagingBasicApi->addTextItem/addTextItemWithParent: ', $e->getMessage(), PHP_EOL;
            }

        }

    }

    // This is mainly a structure, not an encapsulated container.
    class AdvancedMessage
    {
        public $parent;
        public $message;
        public $id;
        public $conv_id;

        private static $nextId = 0;

        public function __construct($message, $parent = null)
        {
            $this->id = AdvancedMessage::$nextId++;
            $this->message = $message;
            $this->parent  = $parent;
        }

    }
}
