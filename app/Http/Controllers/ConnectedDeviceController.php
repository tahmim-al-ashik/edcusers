<?php

namespace App\Http\Controllers;

use App\Models\ConnectedDevice;
use App\Models\Router;

class ConnectedDeviceController extends Controller
{
    public function destroy(Router $router, ConnectedDevice $device)
    {
        if ($device->router_id !== $router->id) {
            return back()->with('error', 'Device mismatch.');
        }

        $device->delete();
        return back()->with('success', 'Device removed.');
    }
}
