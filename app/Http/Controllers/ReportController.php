<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\RouterStatusLog;
use App\Models\ConnectedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Show a paginated “Router Status” report.
     */
    public function routerStatus()
    {
        $routers = Router::with('latestStatus')
            ->withCount([
                'connectedDevices as total_devices',
                'connectedDevices as active_devices'   => function($query) {
                    $query->where('active', true);
                },
                'connectedDevices as inactive_devices' => function($query) {
                    $query->where('active', false);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('reports.router-status', compact('routers'));
    }

    /**
     * Show a “Bandwidth Usage” report, optionally filtered by router.
     */
    public function bandwidthUsage(Request $request)
    {
        $routerId = $request->input('router_id');
        $days     = $request->input('days', 7);

        $query = RouterStatusLog::select([
                'router_id',
                DB::raw('DATE(logged_at) AS date'),
                DB::raw('MAX(total_bytes_in) - MIN(total_bytes_in)   AS bytes_in'),
                DB::raw('MAX(total_bytes_out) - MIN(total_bytes_out) AS bytes_out'),
            ])
            ->where('logged_at', '>=', now()->subDays($days))
            ->groupBy('router_id', 'date')
            ->orderBy('date');

        if ($routerId) {
            $query->where('router_id', $routerId);
        }

        $usageData = $query->get();

        // Only load ID & name for the dropdown:
        $routers = Router::select('id', 'name')
                         ->orderBy('name')
                         ->get();

        return view('reports.bandwidth-usage', compact(
            'usageData',
            'routers',
            'routerId',
            'days'
        ));
    }

    /**
     * Show a list of connected devices, optionally filtered by router & status.
     */
    public function connectedDevices(Request $request)
    {
        $routerId = $request->input('router_id');
        $status   = $request->input('status', 'active');

        $query = ConnectedDevice::query();

        if ($routerId) {
            $query->where('router_id', $routerId);
        }
        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        // Always paginate to avoid large-memory usage:
        $devices = $query->with('router')
                         ->orderBy('id', 'desc')
                         ->paginate(50);

        // Only load ID & name for the router dropdown:
        $routers = Router::select('id', 'name')
                         ->orderBy('name')
                         ->get();

        return view('reports.connected-devices', compact(
            'devices',
            'routers',
            'routerId',
            'status'
        ));
    }

    /**
     * Show details for a single connected device (plus 7-day logs of its router).
     */
    public function deviceDetails($id)
    {
        $device = ConnectedDevice::with([
                'router',
                'router.statusLogs' => fn($q) => $q->where('logged_at', '>=', now()->subDays(7))
            ])
            ->findOrFail($id);

        return view('reports.device-details', compact('device'));
    }
}
