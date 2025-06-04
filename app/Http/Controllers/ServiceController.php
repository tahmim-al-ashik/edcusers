<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function sharedServicePackageList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $query = Service::query();
        $query->where('is_active', '=', 1);
        $query->orderBy('price');
        $returned_data['results'] = $query->get();

        return ResponseWrapper::End($returned_data);
    }
}
