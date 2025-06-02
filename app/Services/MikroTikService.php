<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Router;
use App\Models\RouterStatusLog;
use App\Models\ConnectedDevice;

// Use the namespaced RouterOS classes
use RouterOS\Config;
use RouterOS\Client;
use RouterOS\Query;

class MikroTikService
{
    /**
     * Check a single router’s status.
     * Returns true if online (and saves stats), or false if offline/unreachable.
     */
    public function checkRouterStatus(Router $router)
    {
        $ip   = $router->ip_address;
        $port = (int) $router->port;

        // 1) Quick TCP-port check:
        if (! $this->isApiPortOpen($ip, $port, 5)) {
            Log::warning("ROUTER {$router->id}: TCP port {$port} is CLOSED or unreachable.");

            RouterStatusLog::create([
                'router_id'         => $router->id,
                'online'            => false,
                'cpu_load'          => null,
                'memory_usage'      => null,
                'total_bytes_in'    => 0,
                'total_bytes_out'   => 0,
                'active_connections'=> 0,
                'logged_at'         => now(),
            ]);

            return false;
        }

        Log::info("ROUTER {$router->id}: TCP port {$port} is open. Attempting API login...");

        try {
            // 2) Build a Config object, set a 5s timeout and 5s socket timeout:
            $config = (new Config())
                ->set('host',          $ip)
                ->set('user',          $router->username)
                ->set('pass',          $router->password)
                ->set('port',          $port)
                ->set('timeout',       5)   // overall request timeout (seconds)
                ->set('socket_timeout', 5); // how long to wait for socket read

            // If you need SSL or legacy login, add:
            // ->set('ssl', (bool)$router->ssl)
            // ->set('legacy', (bool)$router->legacy_login)

            $client = new Client($config);

            Log::info("ROUTER {$router->id}: Connected via API. Sending /system/identity/print...");

            // 3) Quick identity check (fail fast on bad credentials):
            $identityResponse = $client
                ->query(new Query('/system/identity/print'))
                ->read();
            Log::info("ROUTER {$router->id}: Identity response:", $identityResponse);

            // 4) Collect stats now that we know it’s online:
            $resources = $client
                ->query(new Query('/system/resource/print'))
                ->read()[0] ?? [];
            Log::info("ROUTER {$router->id}: /system/resource/print returned.");

            $interfaces = $client
                ->query(new Query('/interface/print'))
                ->read();
            Log::info("ROUTER {$router->id}: /interface/print returned " . count($interfaces) . " rows.");

            $leases = $client
                ->query(new Query('/ip/dhcp-server/lease/print'))
                ->read();
            Log::info("ROUTER {$router->id}: /ip/dhcp-server/lease/print returned " . count($leases) . " rows.");

            $wirelessClients = $client
                ->query(new Query('/interface/wireless/registration-table/print'))
                ->read();
            Log::info("ROUTER {$router->id}: /interface/wireless/registration-table/print returned " . count($wirelessClients) . " rows.");

            $arpTable = $client
                ->query(new Query('/ip/arp/print'))
                ->read();
            Log::info("ROUTER {$router->id}: /ip/arp/print returned " . count($arpTable) . " rows.");

            // 5) Save status and connected devices:
            $this->saveRouterStatus($router, $resources, $interfaces);
            $this->saveConnectedDevices($router, $leases, $wirelessClients, $arpTable);

            Log::info("ROUTER {$router->id}: Status saved; marking ONLINE.");
            return true;
        } catch (\Throwable $e) {
            Log::error("ROUTER {$router->id}: MikroTik API error: " . $e->getMessage());

            RouterStatusLog::create([
                'router_id'         => $router->id,
                'online'            => false,
                'cpu_load'          => null,
                'memory_usage'      => null,
                'total_bytes_in'    => 0,
                'total_bytes_out'   => 0,
                'active_connections'=> 0,
                'logged_at'         => now(),
            ]);

            return false;
        }
    }

    /**
     * Check a TCP port quickly. Returns true if open, false otherwise.
     */
    protected function isApiPortOpen(string $ip, int $port, int $timeoutSeconds = 5): bool
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, $timeoutSeconds);
        if ($socket) {
            fclose($socket);
            return true;
        }
        return false;
    }

    /**
     * Save one “online” status record with CPU, memory, bandwidth, and active connections.
     */
    protected function saveRouterStatus(Router $router, array $resources, array $interfaces)
    {
        $bytesIn  = 0;
        $bytesOut = 0;

        foreach ($interfaces as $iface) {
            if (! str_contains(strtolower($iface['name']), 'bridge')) {
                $bytesIn  += $iface['rx-byte'] ?? 0;
                $bytesOut += $iface['tx-byte'] ?? 0;
            }
        }

        RouterStatusLog::create([
            'router_id'         => $router->id,
            'online'            => true,
            'cpu_load'          => $resources['cpu-load'] ?? null,
            'memory_usage'      => isset($resources['total-memory'], $resources['free-memory'])
                                    ? (($resources['total-memory'] - $resources['free-memory']) / $resources['total-memory']) * 100
                                    : null,
            'total_bytes_in'    => $bytesIn,
            'total_bytes_out'   => $bytesOut,
            'active_connections'=> count($this->getActiveConnections($router)),
            'logged_at'         => now(),
        ]);
    }

    /**
     * Update/Create connected devices, then mark old ones inactive.
     */
    protected function saveConnectedDevices(Router $router, array $leases, array $wirelessClients, array $arpTable)
    {
        $activeMacs = [];

        // 1) DHCP leases
        foreach ($leases as $lease) {
            if (! empty($lease['mac-address'])) {
                $mac = strtoupper($lease['mac-address']);
                $activeMacs[] = $mac;
                $ip = $lease['address'] ?? $lease['active-address'] ?? 'unknown';

                ConnectedDevice::updateOrCreate(
                    ['router_id' => $router->id, 'mac_address' => $mac],
                    [
                        'ip_address' => $ip,
                        'hostname'   => $lease['host-name'] ?? null,
                        'interface'  => $lease['server'] ?? 'DHCP',
                        'bytes_in'   => 0,
                        'bytes_out'  => 0,
                        'active'     => true,
                        'last_seen'  => now(),
                    ]
                );
            }
        }

        // 2) Wireless clients
        foreach ($wirelessClients as $client) {
            if (! empty($client['mac-address'])) {
                $mac = strtoupper($client['mac-address']);
                $activeMacs[] = $mac;
                $ip       = $client['last-ip'] ?? 'unknown';
                $hostname = $client['host-name'] ?? null;
                $iface    = $client['interface'] ?? 'wireless';

                ConnectedDevice::updateOrCreate(
                    ['router_id' => $router->id, 'mac_address' => $mac],
                    [
                        'ip_address' => $ip,
                        'hostname'   => $hostname,
                        'interface'  => $iface,
                        'bytes_in'   => $client['bytes-in'] ?? 0,
                        'bytes_out'  => $client['bytes-out'] ?? 0,
                        'active'     => true,
                        'last_seen'  => now(),
                    ]
                );
            }
        }

        // 3) ARP table
        foreach ($arpTable as $arp) {
            if (! empty($arp['mac-address'])) {
                $mac = strtoupper($arp['mac-address']);
                if (! in_array($mac, $activeMacs)) {
                    $activeMacs[] = $mac;
                    $ip    = $arp['address'] ?? 'unknown';
                    $iface = $arp['interface'] ?? 'ARP';

                    ConnectedDevice::updateOrCreate(
                        ['router_id' => $router->id, 'mac_address' => $mac],
                        [
                            'ip_address' => $ip,
                            'hostname'   => $arp['host-name'] ?? null,
                            'interface'  => $iface,
                            'bytes_in'   => 0,
                            'bytes_out'  => 0,
                            'active'     => true,
                            'last_seen'  => now(),
                        ]
                    );
                }
            }
        }

        // 4) Mark devices not seen in the last 5 minutes as inactive
        ConnectedDevice::where('router_id', $router->id)
            ->where('last_seen', '<', now()->subMinutes(5))
            ->update(['active' => false]);
    }

    /**
     * Return an array of active firewall connections.
     */
    protected function getActiveConnections(Router $router): array
    {
        try {
            // Build the Config exactly as above (no connect_timeout, only timeout/socket_timeout)
            $config = (new Config())
                ->set('host',          $router->ip_address)
                ->set('user',          $router->username)
                ->set('pass',          $router->password)
                ->set('port',          (int)$router->port)
                ->set('timeout',       5)
                ->set('socket_timeout', 5);

            $client = new Client($config);

            $connections = $client
                ->query(new Query('/ip/firewall/connection/print'))
                ->read();

            return $connections;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
