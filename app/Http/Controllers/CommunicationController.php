<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\Communication;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function createNewCommunication(Request $request, $customer_uid, $employee_uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = new Communication();
        $query->customer_uid = $customer_uid;
        $query->employee_uid = $employee_uid;
        $query->status = $request->get('status');
        $query->type = $request->get('type');
        $query->message = $request->get('message');
        $query->save();

        if($query->id){
            $query->full_name = UserProfile::where('uid', '=', $employee_uid)->value('full_name');
            $returned_data['results'] = $query;
        }

        return ResponseWrapper::End($returned_data);
    }


    public function getCommunicationList(Request $request, $customer_uid, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

            $data = Communication::query();
            $data->leftJoin('user_profiles', 'communications.employee_uid', '=', 'user_profiles.uid');
            $data->where('customer_uid', '=', $customer_uid);
            $data->where('type', '=', $type);
            $data->orderBy('id', 'DESC');
            $returned_data['results'] = $data->get(['communications.*', 'user_profiles.full_name']);

        return ResponseWrapper::End($returned_data);
    }


    public function deleteCommunicationItem(Request $request, $type, $id, $employee_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $permissionGranted = false;
        $userRole = User::where('id', '=', $employee_id)->value('base_role');

        if($userRole === 'admin'){
            $permissionGranted = true;
        } else if($type){
            $permissionGranted = UserPermission::where('uid', '=', $employee_id)->where('name', '=', $type.'_communication_delete')->exists();
        }

        if($permissionGranted){
            $returned_data['results'] = Communication::where('id', '=', $id)->delete();
        } else {
            $returned_data['error_type'] = 'access_denied';
            $returned_data['message'] = "you do not have access.";
        }

        return ResponseWrapper::End($returned_data);
    }
}
