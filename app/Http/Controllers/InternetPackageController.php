<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\NetworkSupportCenterPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Log;

class InternetPackageController extends Controller
{
    public function sharedPackageList(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results']['general'] = [];
        $returned_data['results']['opening'] = null;
        $request_params = $request->input();
        
        //Log::info($request->all());

        $userId = null;
        if(isset($request_params['user_auth_id']) && $request_params['user_auth_id'] !== 'null'){
            $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('user_auth_id'));
        }
        
        //$lastRequest = Session::get('last_api_request', 'No request found');
        
        if(!empty($request->zone_id) && $request->zone_id == '2350039'){
            $query = InternetPackage::query();
            $query->where('is_active', '=', 1);
            $query->where('skip_from_display', '=', 0);
            if(isset($request_params['without_free_package']) && $request_params['without_free_package'] === 'yes'){
                $query->where('price', '>=', 1);
            }

            $query->whereNull('zone_id');
            $userZoneId = null;
            if($userId !== null && InternetUsers::where('uid', '=', $userId)->exists()){
                $userZoneId = InternetUsers::where('uid', '=', $userId)->value('zone_id');
            } else if(!empty($request_params['zone_id']) && $request_params['zone_id'] !== 'null') {
                $userZoneId = $request_params['zone_id'];
            }

            $userInternetPackageType = null;
            if($userId !== null){
                $userInternetPackageType = InternetUsers::where('uid', '=', $userId)->value('package_type');
            }

            if(isset($request_params['without_free_package']) &&  $request_params['without_free_package'] === 'yes'){
                if($userId !== null){
                    $query->where('type', '=', $userInternetPackageType);
                }
            }
            if(!empty($request_params['type'])){
                $query->where('type', '=', $request_params['type']);
            }

            
            $returned_data['active_package_type'] = $userInternetPackageType;

            if($userZoneId !== null){
                $centerPackages = NetworkSupportCenterPackage::query();
                $centerPackages->where('zone_id', '=', $userZoneId);
                $centerPackagesIds = $centerPackages->pluck('package_id');
                // Log::info($centerPackagesIds);
                $query->orWhereIn('id', $centerPackagesIds);
                $query->where('is_active', '=', 1);
                $query->where('skip_from_display', '=', 0);
                //Log::info($query->get());
            }

            $query->orderBy('price');
            $query->orderBy('weight');
            $results = $query->get();
            //Log::info('show package list');
            
        } else{
            $query = InternetPackage::query();
            $query->where('is_active', '=', 1);
            $query->where('mikrotik_radius_group_name', '!=', 'Desh');
            $query->where('skip_from_display', '=', 0);
            if(isset($request_params['without_free_package']) && $request_params['without_free_package'] === 'yes'){
                $query->where('price', '>=', 1);
            }

            $query->whereNull('zone_id');
            $userZoneId = null;
            if($userId !== null && InternetUsers::where('uid', '=', $userId)->exists()){
                $userZoneId = InternetUsers::where('uid', '=', $userId)->value('zone_id');
            } else if(!empty($request_params['zone_id']) && $request_params['zone_id'] !== 'null') {
                $userZoneId = $request_params['zone_id'];
            }

            $userInternetPackageType = null;
            if($userId !== null){
                $userInternetPackageType = InternetUsers::where('uid', '=', $userId)->value('package_type');
            }

            if(isset($request_params['without_free_package']) &&  $request_params['without_free_package'] === 'yes'){
                if($userId !== null){
                    $query->where('type', '=', $userInternetPackageType);
                }
            }
            if(!empty($request_params['type'])){
                $query->where('type', '=', $request_params['type']);
            }

            $returned_data['active_package_type'] = $userInternetPackageType;

            if($userZoneId !== null){
                $centerPackages = NetworkSupportCenterPackage::query();
                $centerPackages->where('zone_id', '=', $userZoneId);
                $centerPackagesIds = $centerPackages->pluck('package_id');
                // Log::info($centerPackagesIds);
                $query->orWhereIn('id', $centerPackagesIds);
                $query->where('is_active', '=', 1);
                $query->where('mikrotik_radius_group_name', '!=', 'Desh');
                $query->where('skip_from_display', '=', 0);
            }

            $query->orderBy('price');
            $query->orderBy('weight');
            $results = $query->get();
            
        }
        

        foreach ($results as $result){
            $expiration_days = ($result->expiration / 1440);
            $result->expiration_days = $expiration_days;
            $returned_data['results']['general'][] = $result;
        }

        if(isset($request_params['zone_id']) && $request_params['zone_id'] != null){
            $opening_package_id = NetworkSupportCenter::where('zone_id', '=', $request_params['zone_id'])->value('opening_package_id');
            $returned_data['results']['opening'] = InternetPackage::where('id', '=', $opening_package_id)->where('is_active', '=', 1)->first();
        }
        //Log::info($returned_data);
        return ResponseWrapper::End($returned_data);
    }

    public function sharedPackageList2(Request $request, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results']['general'] = [];
        $returned_data['results']['opening'] = null;
        $request_params = $request->input();
        
        //Log::info($request->all());

        $userId = null;
        if(isset($request_params['user_auth_id']) && $request_params['user_auth_id'] !== 'null'){
            $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('user_auth_id'));
        }
        
        //$lastRequest = Session::get('last_api_request', 'No request found');
        
        if(!empty($request->zone_id) && $request->zone_id == '2350039'){
            $query = InternetPackage::query();
            $query->where('type', '=', $type);
            $query->where('is_active', '=', 1);
            $query->where('skip_from_display', '=', 0);
            if(isset($request_params['without_free_package']) && $request_params['without_free_package'] === 'yes'){
                $query->where('price', '>=', 1);
            }

            $query->whereNull('zone_id');
            $userZoneId = null;
            if($userId !== null && InternetUsers::where('uid', '=', $userId)->exists()){
                $userZoneId = InternetUsers::where('uid', '=', $userId)->value('zone_id');
            } else if(!empty($request_params['zone_id']) && $request_params['zone_id'] !== 'null') {
                $userZoneId = $request_params['zone_id'];
            }

            $userInternetPackageType = null;
            if($userId !== null){
                $userInternetPackageType = InternetUsers::where('uid', '=', $userId)->value('package_type');
            }

            if(isset($request_params['without_free_package']) &&  $request_params['without_free_package'] === 'yes'){
                if($userId !== null){
                    $query->where('type', '=', $userInternetPackageType);
                }
            }
            if(!empty($request_params['type'])){
                $query->where('type', '=', $request_params['type']);
            }

            
            $returned_data['active_package_type'] = $userInternetPackageType;

            if($userZoneId !== null){
                $centerPackages = NetworkSupportCenterPackage::query();
                $centerPackages->where('zone_id', '=', $userZoneId);
                $centerPackagesIds = $centerPackages->pluck('package_id');
                // Log::info($centerPackagesIds);
                $query->orWhereIn('id', $centerPackagesIds);
                $query->where('is_active', '=', 1);
                $query->where('skip_from_display', '=', 0);
                //Log::info($query->get());
            }

            $query->orderBy('price');
            $query->orderBy('weight');
            $results = $query->get();
            //Log::info('show package list');
            
        } else{
            $query = InternetPackage::query();
            $query->where('type', '=', $type);
            $query->where('is_active', '=', 1);
            $query->where('mikrotik_radius_group_name', '!=', 'Desh');
            $query->where('skip_from_display', '=', 0);
            if(isset($request_params['without_free_package']) && $request_params['without_free_package'] === 'yes'){
                $query->where('price', '>=', 1);
            }

            $query->whereNull('zone_id');
            $userZoneId = null;
            if($userId !== null && InternetUsers::where('uid', '=', $userId)->exists()){
                $userZoneId = InternetUsers::where('uid', '=', $userId)->value('zone_id');
            } else if(!empty($request_params['zone_id']) && $request_params['zone_id'] !== 'null') {
                $userZoneId = $request_params['zone_id'];
            }

            $userInternetPackageType = null;
            if($userId !== null){
                $userInternetPackageType = InternetUsers::where('uid', '=', $userId)->value('package_type');
            }

            if(isset($request_params['without_free_package']) &&  $request_params['without_free_package'] === 'yes'){
                if($userId !== null){
                    $query->where('type', '=', $userInternetPackageType);
                }
            }
            if(!empty($request_params['type'])){
                $query->where('type', '=', $request_params['type']);
            }

            $returned_data['active_package_type'] = $userInternetPackageType;

            if($userZoneId !== null){
                $centerPackages = NetworkSupportCenterPackage::query();
                $centerPackages->where('zone_id', '=', $userZoneId);
                $centerPackagesIds = $centerPackages->pluck('package_id');
                // Log::info($centerPackagesIds);
                $query->orWhereIn('id', $centerPackagesIds);
                $query->where('is_active', '=', 1);
                $query->where('mikrotik_radius_group_name', '!=', 'Desh');
                $query->where('skip_from_display', '=', 0);
            }

            $query->orderBy('price');
            $query->orderBy('weight');
            $results = $query->get();
            
        }
        

        foreach ($results as $result){
            $expiration_days = ($result->expiration / 1440);
            $result->expiration_days = $expiration_days;
            $returned_data['results']['general'][] = $result;
        }

        if(isset($request_params['zone_id']) && $request_params['zone_id'] != null){
            $opening_package_id = NetworkSupportCenter::where('zone_id', '=', $request_params['zone_id'])->value('opening_package_id');
            $returned_data['results']['opening'] = InternetPackage::where('id', '=', $opening_package_id)->where('is_active', '=', 1)->first();
        }
        //Log::info($returned_data);
        return ResponseWrapper::End($returned_data);
    }

    public function plexusCloudWebsitePackages( $type ) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $packageList = InternetPackage::where('type', '=', $type)->get();
        foreach ($packageList as $result){
            $expiration_days = ($result->expiration / 1440);
            $result->expiration_days = $expiration_days;
            $returned_data['results']['general'][] = $result;
        }
        return ResponseWrapper::End($returned_data);
    }
    
    public function sharedPackageDetails(Request $request, $package_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $packageDetails = InternetPackage::where('id', '=', $package_id)->first();
        $packageDetails->expiration_days = ($packageDetails->expiration / 1440);
        $returned_data['results'] = $packageDetails;
        return ResponseWrapper::End($returned_data);
    }

    public function createUpdatePackage(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        // if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
        //     $returned_data['error_type'] = 'token_error';
        //     return ResponseWrapper::End($returned_data);
        // }

        $requestedData = $request->input();
        // unset($requestedData['access_token']);
        
        if(empty($request->get('id'))){
            $returned_data['results'] = InternetPackage::create([
                'mikrotik_radius_group_name' => str_replace(' ', '-', $requestedData['en_title']),
                'en_title' => $requestedData['en_title'],
                'bn_title' => $requestedData['bn_title'],
                'type' => $requestedData['type'],
                // 'zone_id' => $requestedData['zone_id'],
                'price' => $requestedData['price'],
                'expiration' => $requestedData['expiration'],
                // 'sales_point_commission' => $requestedData['sales_point_commission'],
                // 'sales_agent_commission' => $requestedData['sales_agent_commission'],
                // 'commission_type' => $requestedData['commission_type'],
                // 'user_points' => $requestedData['user_points'],
                'is_active' => 1,
                'skip_from_display' => 0,
                // 'weight' => $requestedData['weight'],
                // 'bg_image_source' => $requestedData['bg_image_source']
            ]);
        } else {
            $returned_data['results'] = InternetPackage::where('id', '=', $request->get('id'))->update([
                'mikrotik_radius_group_name' => str_replace(' ', '-', $requestedData['en_title']),
                'en_title' => $requestedData['en_title'],
                'bn_title' => $requestedData['bn_title'],
                'type' => $requestedData['type'],
                // 'zone_id' => $requestedData['zone_id'],
                'price' => $requestedData['price'],
                'expiration' => $requestedData['expiration'],
                // 'sales_point_commission' => $requestedData['sales_point_commission'],
                // 'sales_agent_commission' => $requestedData['sales_agent_commission'],
                // 'commission_type' => $requestedData['commission_type'],
                // 'user_points' => $requestedData['user_points'],
                // 'is_active' => $requestedData['is_active'],
                // 'skip_from_display' => $requestedData['skip_from_display'],
                // 'weight' => $requestedData['weight'],
                // 'bg_image_source' => $requestedData['bg_image_source']
            ]);
        }

        return ResponseWrapper::End($returned_data);
    }

    public function deletePackage(Request $request, $package_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        // if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
        //     $returned_data['error_type'] = 'token_error';
        //     return ResponseWrapper::End($returned_data);
        // }

        if(InternetPackage::where('id', '=', $package_id)->delete()){
            $returned_data['results'] = true;
        } else {
            $returned_data['results'] = false;
        }
        return ResponseWrapper::End($returned_data);
    }
}
