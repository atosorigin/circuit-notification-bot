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

        $result = print_r($hooks->apply_filters('wakeup', []), TRUE);

        echo 'Hook result:', PHP_EOL, $result;
        echo 'Hooks:', PHP_EOL;

        print_r($hooks);

        $result = str_replace(PHP_EOL, "<br>", $result);

        $api_instance = new Swagger\Client\Api\MessagingBasicApi();
        $conv_id = $config['conId']; // string | The ID of the conversation to which the new item has to be added
        $content = "<pre><code>$result</code></pre>"; // string | The actual content of the item, is mandatory unless an attachment is added

        try {
            $result = $api_instance->addTextItem($conv_id, $content);
            print_r($result);
        } catch (Exception $e) {
            echo 'Exception when calling MessagingBasicApi->addTextItem: ', $e->getMessage(), PHP_EOL;
        }
    }
}
