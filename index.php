<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/config.php');

use GuzzleHttp\Client;

$client = new \GuzzleHttp\Client();

$response = $client->post('https://eu.yourcircuit.com/oauth/token/', [
    'body' => [
        'client_id' => $config['client']['id'],
        'client_secret' => $config['client']['secret'],
        'grant_type' => 'client_credentials',
        'scope' => 'ALL'
    ]
])->json();

$response['expires'] = time() + $response['expires_in'];
print_r($response);

$token = $response['access_token'];

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
