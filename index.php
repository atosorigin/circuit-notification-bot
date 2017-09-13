<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/config.php');

use ICanBoogie\Storage\FileStorage;

const BASE_URL  = 'https://eu.yourcircuit.com/';
const TOKEN_KEY = 'token_response';

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

// Configure OAuth2 access token for authorization: oauth
Swagger\Client\Configuration::getDefaultConfiguration()->setAccessToken($token)->setHost('https://eu.yourcircuit.com/rest/v2');

$api_instance = new Swagger\Client\Api\MessagingBasicApi();
$conv_id = $config['conId']; // string | The ID of the conversation to which the new item has to be added
$content = "content_example"; // string | The actual content of the item, is mandatory unless an attachment is added

try {
    $result = $api_instance->addTextItem($conv_id, $content);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling MessagingBasicApi->addTextItem: ', $e->getMessage(), PHP_EOL;
}
