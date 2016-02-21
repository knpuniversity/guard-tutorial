<?php

require __DIR__.'/vendor/autoload.php';

$client = new GuzzleHttp\Client();
$res = $client->get('http://localhost:8000/secure', [
    'allow_redirects' => false,
    'http_errors' => false,
    'headers' => [
        // token for anna_admin in LoadUserData fixtures
        'X-Token' => 'ABCD1234'
    ]
]);

echo sprintf("Status Code: %s\n\n", $res->getStatusCode());
echo $res->getBody();
echo "\n\n";
