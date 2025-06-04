<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\CorporateInternetUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporateInternetUsersController extends Controller
{
    public function getInternetUserDetails($auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = CorporateInternetUsers::query();
        $query->leftJoin('internet_package_corporates', 'internet_package_corporates.id', '=', 'corporate_internet_users.package_id');
        $query->where('username', '=', $auth_id);
        $returned_data['results'] = $query->first(['corporate_internet_users.*', 'internet_package_corporates.package_name', 'internet_package_corporates.price', 'internet_package_corporates.is_active']);
        return ResponseWrapper::End($returned_data);
    }

    public function getInternetUsersList(Request $request, $client_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }
        $request_params = $request->input();
        $userType = !empty($request->get('user_type')) ? $request->get('user_type') : 'all';
        $query = CorporateInternetUsers::query();
        $query->where('corporate_internet_users.client_id', '=', $client_id);
        $query->leftJoin('internet_package_corporates', 'corporate_internet_users.package_id', '=', 'internet_package_corporates.id');
        if(!empty($request->get('is_active'))){
            $query->where('corporate_internet_users.is_active', '=', $request->get('is_active'));
        }
        if($userType !== 'all'){
            $query->where('corporate_internet_users.user_type', '=', $userType);
        }
        $returned_data['results'] = $query->get(['corporate_internet_users.*','internet_package_corporates.package_name']);
        return ResponseWrapper::End($returned_data);
    }

    public function createUpdateInternetUser(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }
        $requestedData = $request->input();
        unset($requestedData['access_token']);
        if(!CorporateInternetUsers::where('client_id', '=', $request->get('client_id'))->where('username', '=', $request->get('username'))->exists()){
            $returned_data['results'] = CorporateInternetUsers::create($requestedData);
        } else {
            $returned_data['results'] = CorporateInternetUsers::where('client_id', '=', $request->get('client_id'))->where('username', '=', $request->get('username'))->update($requestedData);
        }
        return ResponseWrapper::End($returned_data);
    }

    public function deleteInternetUser(Request $request, $client_id, $username) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }
        if(CorporateInternetUsers::where('username', '=', $username)->where('client_id', '=', $client_id)->delete()){
            $returned_data['results'] = true;
        } else {
            $returned_data['results'] = false;
        }
        return ResponseWrapper::End($returned_data);
    }
}
