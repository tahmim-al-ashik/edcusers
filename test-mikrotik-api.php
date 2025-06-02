<?php
/**
 * test-mikrotik-api.php
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOS\Config;
use RouterOS\Client;
use RouterOS\Query;

$host     = '103.131.146.18';
$port     = 8728;
$user     = 'edc-panel';
$password = 'edc-paneledc-panel@123';

echo "Testing API login on {$host}:{$port} …\n";

try {
    $config = (new Config())
        ->set('host', $host)
        ->set('user', $user)
        ->set('pass', $password)
        ->set('port', $port)
        ->set('timeout', 5);

    $client = new Client($config);

    $response = $client->query(new Query('/system/identity/print'))->read();
    echo "SUCCESS! /system/identity/print returned:\n";
    print_r($response);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
