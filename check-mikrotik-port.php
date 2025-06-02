<?php
/**
 * test-mikrotik-api.php
 *
 * Place this file in your Laravel project root (next to artisan).
 * Then run:
 *
 *     php test-mikrotik-api.php
 *
 * It uses RouterOS\Client/Query to connect and run /system/identity/print.
 * You will see either a “SUCCESS” block or an error.
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;

// Put your MikroTik details here:
$host     = '103.131.146.18';
$port     = 8728;
$user     = 'edc-panel';
$password = 'edc-paneledc-panel@123';

echo "Testing API login on {$host}:{$port} …\n";

try {
    // 1) Create a new Client instance
    $client = new Client([
        'host'    => $host,
        'user'    => $user,
        'pass'    => $password,
        'port'    => $port,
        'timeout' => 5,  // seconds to wait for any response
    ]);

    // 2) Send '/system/identity/print'
    $response = $client->query(new Query('/system/identity/print'))->read();

    echo "SUCCESS! /system/identity/print returned:\n";
    print_r($response);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
