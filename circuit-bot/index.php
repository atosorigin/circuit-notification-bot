<?php

require_once(__DIR__ . '/vendor/autoload.php');

use ICanBoogie\Storage\FileStorage;

if(!function_exists('circuit_bot'))
{
    define('BASE_URL', 'https://eu.yourcircuit.com/');
    define('TOKEN_KEY', 'token_response');

    function circuit_bot($config)
    {
        global $hooks;

        $storage = new FileStorage(__DIR__);
        $tokenEndpoint = BASE_URL . 'oauth/token';

        function print_conv_item($conv_item)
        {
            echo 'Message...', PHP_EOL,
                'ID      ', $conv_item['item_id'], PHP_EOL,
                'Content ', $conv_item['text']['content'], PHP_EOL, PHP_EOL;
        }

        if($response = $storage->retrieve(TOKEN_KEY))
        {
            echo 'Token loaded', PHP_EOL;
            $token = $response['access_token'];
        }
        else
        {
            echo 'No token found, requesting new one...', PHP_EOL;

            $response = (new OAuth2\Client($config['client']['id'], $config['client']['secret']))
                ->getAccessToken($tokenEndpoint, 'client_credentials', ['scope' => 'ALL'])
                ['result'];

            $storage->store(TOKEN_KEY, $response, $response['expires_in'] - 10 /* just to be sure */);

            $token = $response['access_token'];
        }

        // Configure Host and OAuth2 access token for authorization
        Swagger\Client\Configuration::getDefaultConfiguration()
            ->setAccessToken($token)
            ->setHost(BASE_URL . 'rest/v2');

        $hooks->add_filter('wakeup', 'wakeup_ex');

        function wakeup_ex($ary){
            return array_merge($ary, ['integrated filter']);
        }

        $api_instance = new Swagger\Client\Api\MessagingBasicApi();
        $conv_id = $config['conId'];

        $wakeup = $hooks->apply_filters('wakeup', []);
        $wakeup_advanced = $hooks->apply_filters('wakeup_advanced', []);

        foreach($wakeup as $key => $content)
        {
            try
            {
                $result = $api_instance->addTextItem($conv_id, $content);
                print_conv_item($result);
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

                    $hooks->do_action('parent_id', $msg_adv->id, $result['item_id']);

                }
                print_conv_item($result);
            }
            catch (Exception $e)
            {
                echo 'Exception when calling MessagingBasicApi->addTextItem: ', $e->getMessage(), PHP_EOL;
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
