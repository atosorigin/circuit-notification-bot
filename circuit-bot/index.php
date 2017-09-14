<?php

require_once(__DIR__ . '/vendor/autoload.php');

use ICanBoogie\Storage\FileStorage;

if(!function_exists('circuit_bot'))
{
    define('TOKEN_ENDPOINT', 'https://eu.yourcircuit.com/oauth/token');

    define('FILTER_WAKEUP', 'wakeup');
    define('FILTER_WAKEUP_ADV', 'wakeup_advanced');

    define('ACTION_PARENT_ID', 'parent_id');

    function circuit_bot($config)
    {
        global $hooks;

        if(!isset($config['client']) || !isset($config['client']['id']))
        {
            die('Missing OAuth Client-ID!');
        }

        $storage = new FileStorage(__DIR__);

        $conv_id = $config['conId'];
        $token_key = 'token_' . $config['client']['id'];

        function print_conv_item($conv_item)
        {
            echo 'Message...', PHP_EOL,
                'ID      ', $conv_item['item_id'], PHP_EOL,
                'Content ', $conv_item['text']['content'], PHP_EOL, PHP_EOL;
        }

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

        $api_instance = new Swagger\Client\Api\MessagingBasicApi();

        $wakeup = $hooks->apply_filters(FILTER_WAKEUP, []);
        $wakeup_advanced = $hooks->apply_filters(FILTER_WAKEUP_ADV, []);

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
                    $result = $api_instance->addTextItemWithParent($conv_id, $msg_adv->parent, $msg_adv->message);
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

        private static $nextId = 0;

        public function __construct($message, $parent = null)
        {
            $this->id = AdvancedMessage::$nextId++;
            $this->message = $message;
            $this->parent  = $parent;
        }

    }
}
