<?php

namespace App\Http\Controllers\apps;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserRole;
use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AppsAccountController extends Controller{

    public function appAccountBasic(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));
        $user = User::where('id','=', $userId)->first();
        $user->auth_id = $request->get('auth_id');
        $user->roles = UserRole::where('uid', '=', $user->id)->pluck('rid')->toArray();
        $user->profile = UserProfile::where('uid', '=', $user->id)->first();
        $user->profile->address = (new \App\Classes\CustomHelpers)->generate_user_address($userId);
        $user->account_type = ['user'];
        if($user->base_role !== 'user' && $user->base_role !== 'agent'){
            $user->account_type = array_merge($user->account_type, [$user->base_role]);
            //$user->account_type = $user->base_role;
            
        } else if($user->base_role !== 'user' && $user->base_role == 'agent'){
            $user->account_type = array_merge($user->account_type, ['sales_agent']);
            //Log::info($user->account_type);
        }
        if(empty($user->permissions)){
            $user->permissions = [];
        } else {
            $user->permissions = json_decode($user->permissions);
        }
        $returned_data['results'] = $user;
        //Log::info( $returned_data['results']);

       
        return ResponseWrapper::End($returned_data);
    }


    public function appAccountProfileUpdate(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));
        $query = UserProfile::where('uid', '=', $userId)->first();
        if(empty($query)){
            $query = new UserProfile();
        }
        $query->full_name = $request->get('full_name');
        $query->mobile_number = $request->get('mobile_number');
        $query->whatsapp_number = $request->get('whatsapp_number');
        $query->email = $request->get('email');
        $query->profession = $request->get('profession');
        $query->nid = $request->get('nid');
        $query->division_id = $request->get('division_id');
        $query->district_id = $request->get('district_id');
        $query->upazila_id = $request->get('upazila_id');
        $query->union_id = $request->get('union_id');
        $query->village_id = $request->get('village_id');
        $query->house_no = $request->get('house_no');
        $query->address = $request->get('address');
        $query->address_direction = $request->get('address_direction');
        $query->latitude = $request->get('latitude');
        $query->longitude = $request->get('longitude');
        $query->device_info = json_encode($request->get('device_info'));
        $returned_data['results'] = $query->save();

        return ResponseWrapper::End($returned_data);

    }
}
