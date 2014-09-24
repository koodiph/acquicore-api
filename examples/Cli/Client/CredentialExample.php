#!/usr/bin/php
<?php

/**
 * Authentication to Aquicore Server with the user credentials grant
 */

include __DIR__ . '/../../../vendor/autoload.php';
include __DIR__ . '/../../config.php';

use Aquicore\API\PHP\Api\Client;
use Aquicore\API\PHP\Api\Helper;
use Aquicore\API\PHP\Api\Exception\ClientException;
use Aquicore\API\PHP\Common\Scopes;

$scope = Scopes::SCOPE_READ_STATION;

$client = new Client(array(
    'username'      => $client_username,
    'password'      => $client_password,
));
$helper = new Helper($client);

try {
    $tokens = $client->getAccessToken();
} catch(ClientException $ex) {
    echo "An error happend while trying to retrieve your tokens\n";
    exit(-1);
}

// Retrieve User Info :
$user = $helper->api("/users/me", "GET");
echo ("-------------\n");
echo ("- User Info -\n");
echo ("-------------\n");
echo ("OK\n");
echo ("---------------\n");
echo ("- Device List -\n");
echo ("---------------\n");
$devicelist = $helper->simplifyDeviceList();
echo ("OK\n");
echo ("-----------------\n");
echo ("- Last Measures -\n");
echo ("-----------------\n");
$mesures = $helper->getLastMeasures();
print_r($mesures);
echo ("OK\n");
echo ("---------------------\n");
echo ("- Last Day Measures -\n");
echo ("---------------------\n");
$mesures = $helper->getAllMeasures(mktime() - 86400);
print_r($mesures);
echo ("OK\n");
