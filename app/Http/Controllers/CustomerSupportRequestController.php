<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Models\CustomerSupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSupportRequestController extends Controller
{
    public function createUserSupportRequest(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $uid = null;
        if(!empty($request->get('api_auth_id'))){
            $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('api_auth_id'));
        }

        $query = new CustomerSupportRequest();
        $query->uid = $uid;
        $query->mobile_number = $request->get('api_auth_id');
        $query->support_type = $request->get('support_type');
        $query->message = $request->get('message');
        $query->save();

        $returned_data['results'] = $query;

        return ResponseWrapper::End($returned_data);
    }
}
