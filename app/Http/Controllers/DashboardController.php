<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\RouterStatusLog;
use App\Models\ConnectedDevice;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        //
        // 1) Count total routers:
        $totalRouters = Router::count();

        //
        // 2) Count only those whose latestStatus->online is true:
        $onlineRouters = Router::whereHas('latestStatus', function($q) {
            $q->where('online', true);
        })->count();

        //
        // 3) The rest are “offline”:
        $offlineRouters = $totalRouters - $onlineRouters;

        //
        // 4) Count devices:
        $totalDevices  = ConnectedDevice::count();
        $activeDevices = ConnectedDevice::where('active', true)->count();

        //
        // 5) Bandwidth usage over the last 7 days:
        $bandwidthUsage = RouterStatusLog::select(
                DB::raw('SUM(total_bytes_in)  as total_in'),
                DB::raw('SUM(total_bytes_out) as total_out'),
                DB::raw('DATE(logged_at)       as date')
            )
            ->where('logged_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        //
        // 6) “Recently offline” in the last 24 hours, paginated:
        $recentlyOffline = Router::whereHas('latestStatus', function($q) {
                $q->where('online', false)
                  ->where('logged_at', '>=', now()->subDay());
            })
            ->with('latestStatus')
            ->orderByDesc('latestStatus.logged_at')
            ->paginate(10);

        return view('dashboard', compact(
            'totalRouters',
            'onlineRouters',
            'offlineRouters',
            'totalDevices',
            'activeDevices',
            'bandwidthUsage',
            'recentlyOffline'
        ));
    }
}
