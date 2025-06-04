<?php

namespace App\Http\Controllers\apps;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\NetworkSupportCenter;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppsSalesPointController extends Controller
{
    public function registerSalesPoint(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));

        if(SalesAgent::where('uid', '=', $userId)->exists() || NetworkSupportCenter::where('uid', '=', $userId)->exists()){
            $returned_data['error_type'] = "already_exist";
            return ResponseWrapper::End($returned_data);
        }

        if(!SalesPoint::where('uid', '=', $userId)->exists()){
            $salesPoint = new SalesPoint();
            $salesPoint->uid = $userId;
            $salesPoint->store_name = $request->get('store_name');
            $salesPoint->division_id = $request->get('division_id');
            $salesPoint->district_id = $request->get('district_id');
            $salesPoint->upazila_id = $request->get('upazila_id');
            $salesPoint->union_id = $request->get('union_id');
            $salesPoint->village_id = $request->get('village_id');
            $salesPoint->latitude = $request->get('latitude');
            $salesPoint->longitude = $request->get('longitude');
            $salesPoint->address = $request->get('address');
            $salesPoint->trade_licence = $request->get('trade_licence');
            $salesPoint->logo_source = $request->get('logo_source');
            $salesPoint->data_object = json_encode($request->get('data_object'));
            $salesPoint->save();

            if($salesPoint->id){
                $returned_data['results'] = $salesPoint;
            }
        } else {
            $returned_data['results'] = "already_exist";
        }
        return ResponseWrapper::End($returned_data);
    }


    public function salesPointDashboard(Request $request, $auth_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($auth_id);

        $returned_data['results'] = SalesPoint::where('uid', '=', $userId)->first(['id', 'store_name', 'address', 'status', 'latitude', 'longitude', 'created_at']);

        return ResponseWrapper::End($returned_data);

    }
}
