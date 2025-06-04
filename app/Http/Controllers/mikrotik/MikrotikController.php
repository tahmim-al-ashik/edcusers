<?php

namespace App\Http\Controllers\mikrotik;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\BroadbandDbSecret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MikrotikController extends Controller
{
    public function activateId(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        return ResponseWrapper::End($returned_data);

    }
}
