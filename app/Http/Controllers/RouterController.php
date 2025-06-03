<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\RouterStatusLog;
use App\Models\ConnectedDevice;
use App\Services\MikroTikService;   // ← Correct namespace for the service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RouterOS\Config;
use RouterOS\Client;
use RouterOS\Query;

class RouterController extends Controller
{
    /**
     * Show paginated list of all routers.
     */
    public function index()
    {
        $routers = Router::with('latestStatus')->paginate(15);

        return view('routers.index', compact('routers'));
    }

    /**
     * Show “create new router” form.
     */
    public function create()
    {
        return view('routers.create');
    }

    /**
     * Persist a newly created router.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'ip_address'  => 'required|ip',
            'username'    => 'required|string',
            'password'    => 'required|string',
            'port'        => 'nullable|integer',
            'description' => 'nullable|string',
            'location'    => 'nullable|string',
        ]);

        Router::create($data);

        return redirect()
            ->route('routers.index')
            ->with('success', 'Router added.');
    }

    /**
     * Show details for a single router, including 
     *   • 7-day bandwidth,
     *   • current interface stats,
     *   • paginated device list.
     */
    public function show(Router $router)
    {
        // 1) Load only latestStatus (no connectedDevices yet)
        $router->load('latestStatus');

        // 2) Build 7-day bandwidth data:
        $bwData = RouterStatusLog::select([
                DB::raw('DATE(logged_at) AS date'),
                DB::raw('MAX(total_bytes_in) - MIN(total_bytes_in)   AS daily_in'),
                DB::raw('MAX(total_bytes_out) - MIN(total_bytes_out) AS daily_out'),
            ])
            ->where('router_id', $router->id)
            ->where('logged_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 3) Fetch “dynamic + running” (DR) and “running only” (R) interface lists + totals:
        $drInterfaces = [];
        $rInterfaces  = [];
        $ifaceTotals  = [];

        // Find which interfaces have connected devices (optional use).
        // Not strictly needed, but can help grouping later if desired.
        $interfacesWithDevices = ConnectedDevice::where('router_id', $router->id)
            ->whereNotNull('interface')
            ->distinct()
            ->pluck('interface')
            ->all();

        try {
            $client = new Client(
                (new Config())
                    ->set('host', $router->ip_address)
                    ->set('user', $router->username)
                    ->set('pass', $router->password)
                    ->set('port', (int)$router->port)
                    ->set('timeout', 5)
                    ->set('socket_timeout', 5)
            );

            $allIfaces = $client
                ->query((new Query('/interface/print'))->add('detail', ''))
                ->read();

            foreach ($allIfaces as $iface) {
                $name     = $iface['name'] ?? null;
                $type     = strtolower($iface['type'] ?? '');
                $running  = filter_var($iface['running'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $disabled = filter_var($iface['disabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (! $name || ! $running || $disabled) {
                    continue;
                }

                $rxBytes = intval($iface['rx-byte'] ?? 0);
                $txBytes = intval($iface['tx-byte'] ?? 0);

                // Store per-interface totals
                $ifaceTotals[$name] = [
                    'rx_bytes' => $rxBytes,
                    'tx_bytes' => $txBytes,
                ];

                // Classify dynamic vs non-dynamic
                $dynamicTypes = ['vlan', 'pppoe', 'eoip', 'pptp', 'l2tp', 'ovpn'];
                if (in_array($type, $dynamicTypes, true)) {
                    $drInterfaces[] = $name;
                } else {
                    $rInterfaces[] = $name;
                }
            }
        } catch (\Throwable $e) {
            Log::error("Router {$router->id} interface fetch error: {$e->getMessage()}");
        }

        // 4) Paginate connected devices (15 per page) for this router:
        $paginatedDevices = ConnectedDevice::where('router_id', $router->id)
            ->orderBy('interface')
            ->paginate(15);

        return view('routers.show', compact(
            'router',
            'bwData',
            'drInterfaces',
            'rInterfaces',
            'ifaceTotals',
            'paginatedDevices'
        ));
    }

    /**
     * Return last-7-days bandwidth usage JSON for a single router.
     * Called via AJAX from the “7-Day Bandwidth” chart.
     */
    public function bwData(Router $router)
    {
        $bwData = RouterStatusLog::select([
                DB::raw('DATE(logged_at) AS date'),
                DB::raw('MAX(total_bytes_in) - MIN(total_bytes_in)   AS daily_in'),
                DB::raw('MAX(total_bytes_out) - MIN(total_bytes_out) AS daily_out'),
            ])
            ->where('router_id', $router->id)
            ->where('logged_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($bwData);
    }

    /**
     * Show “edit router” form.
     */
    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    /**
     * Persist updates to an existing router.
     */
    public function update(Request $request, Router $router)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'ip_address'  => 'required|ip',
            'username'    => 'required|string',
            'password'    => 'required|string',
            'port'        => 'nullable|integer',
            'description' => 'nullable|string',
            'location'    => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $router->update($data);

        return redirect()
            ->route('routers.index')
            ->with('success', 'Router updated.');
    }

    /**
     * Delete a router.
     */
    public function destroy(Router $router)
    {
        $router->delete();

        return redirect()
            ->route('routers.index')
            ->with('success', 'Router deleted.');
    }

    /**
     * Manually trigger a “check now” on a router (calls MikroTikService).
     */
    public function checkNow(Router $router, MikroTikService $service)
    {
        $result = $service->checkRouterStatus($router);

        return back()->with(
            $result ? 'success' : 'error',
            $result ? 'Checked.' : 'Failed.'
        );
    }

    /**
     * Return current RX/TX counters (router + per-interface).
     * Called via AJAX polling from show.blade.php (route name: routers.realtime-data).
     * 
     * NOTE: There must be exactly one realtimeData(...) method in this class.
     */
    public function realtimeData(Router $router)
    {
        $totalRx = 0;
        $totalTx = 0;
        $interfaces = [];

        try {
            $client = new Client(
                (new Config())
                    ->set('host', $router->ip_address)
                    ->set('user', $router->username)
                    ->set('pass', $router->password)
                    ->set('port', (int) $router->port)
                    ->set('timeout', 3)
                    ->set('socket_timeout', 3)
            );

            $allIfaces = $client
                ->query((new Query('/interface/print'))->add('detail', ''))
                ->read();

            foreach ($allIfaces as $iface) {
                $name = $iface['name'] ?? null;
                if (! $name || ($iface['running'] ?? 'false') !== 'true') {
                    continue;
                }

                $rx = intval($iface['rx-byte'] ?? 0);
                $tx = intval($iface['tx-byte'] ?? 0);

                $interfaces[$name] = ['rx' => $rx, 'tx' => $tx];
                $totalRx += $rx;
                $totalTx += $tx;
            }
        } catch (\Throwable $e) {
            Log::error("Router {$router->id} realtimeData error: {$e->getMessage()}");
        }

        return response()->json([
            'router'     => ['rx' => $totalRx, 'tx' => $totalTx],
            'interfaces' => $interfaces,
        ]);
    }
}
