<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\InternetPackageCorporate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternetPackageCorporateController extends Controller
{
    public function getPackageDetails(Request $request, $package_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = InternetPackageCorporate::find($package_id);
        return ResponseWrapper::End($returned_data);
    }

    public function deletePackage(Request $request, $client_id, $package_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        if(InternetPackageCorporate::where('id', '=', $package_id)->where('client_id', '=', $client_id)->delete()){
            $returned_data['results'] = true;
        } else {
            $returned_data['results'] = false;
        }
        return ResponseWrapper::End($returned_data);
    }

    public function getPackageList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        $request_params = $request->input();
        $query = InternetPackageCorporate::query();
        $query->where('client_id', '=', $request_params['client_id']);
        if(!empty($request->get('is_active'))){
            $query->where('is_active', '=', $request->get('is_active'));
        }
        if(isset($request_params['package_type']) && !empty($request_params['package_type'])){
            $query->where('package_type', '=', $request_params['package_type']);
        }
        $query->orderBy('price');
        $query->orderBy('weight');
        $results = $query->get();

        foreach ($results as $result){
            $result->expiration_days = null;
            if(!empty($result->expiration)){
                $result->expiration_days = ($result->expiration / 1440);
            }
            $returned_data['results'][] = $result;
        }

        return ResponseWrapper::End($returned_data);
    }

    public function createUpdatePackage(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        $requestedData = $request->input();
        unset($requestedData['access_token']);

        if(empty($request->get('id'))){
            $returned_data['results'] = InternetPackageCorporate::create($requestedData);
        } else {
            $returned_data['results'] = InternetPackageCorporate::where('id', '=', $request->get('id'))->update($requestedData);
        }

        return ResponseWrapper::End($returned_data);
    }
}
