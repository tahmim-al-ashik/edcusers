<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\CorporateSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporateSettingsController extends Controller
{
    public function getMikrotikCredentials(Request $request, $settings_type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = CorporateSettings::where('settings_type', '=', $settings_type)->first(['settings_name','settings_value']);
        return ResponseWrapper::End($returned_data);
    }
}
