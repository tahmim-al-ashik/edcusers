<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CorporateClientController extends Controller
{
    public function corporateUserRoleCheck($auth_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $user_id = User::where('auth_id', $auth_id)->value('id');

        // Check if user is a client
        $client = CorporateClient::where('uid', $user_id)->first();
        if ($client) {
            $returned_data = [
                'status' => 'success',
                'client_id' => $client->uid,
            ];
            return ResponseWrapper::End($returned_data);
        }

        // Check if user is an agent
        $agent = CorporateAgent::where('uid', $user_id)->first();
        if ($agent) {
            $returned_data = [
                'status' => 'success',
                'agent_id' => $agent->uid,
                'client_id' => $agent->client_id,
            ];
            return ResponseWrapper::End($returned_data);
        }

        // Check if user is a sub-agent
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->first();
        if ($sub_agent) {
            $returned_data = [
                'status' => 'success',
                'sub_agent_id' => $sub_agent->uid,
                'agent_id' => $sub_agent->agent_id,
                'client_id' => $sub_agent->client_id,
            ];
            return ResponseWrapper::End($returned_data);
        }

        // If user does not match any role
        $returned_data['status'] = 'error';
        $returned_data['results'] = false;
        return ResponseWrapper::End($returned_data);
    }

    public function deleteClient(Request $request, $client_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        $client = CorporateClient::where('id', '=', $client_id)->first();
        if($client !== null){
            CorporateClient::where('id', '=', $client_id)->delete();
            $returned_data['results'] = User::where('id', '=', $client['uid'])->delete();
        } else {
            $returned_data['results'] = false;
        }
        return ResponseWrapper::End($returned_data);
    }

    public function getClientList(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = CorporateClient::query();
        if(!empty($request->get('is_active'))){
            $query->where('is_active', '=', $request->get('is_active'));
        }
        $query->orderBy('zone_name');
        $returned_data['results'] = $query->get();

        return ResponseWrapper::End($returned_data);
    }

    public function createUpdateClient(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $requestedData = $request->input();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        unset($requestedData['access_token']);
        if(empty($requestedData['id'])){

            if(User::where('auth_id', '=', $requestedData['auth_id'])->exists()){
                $returned_data['error_type'] = 'account_exists';
                return ResponseWrapper::End($returned_data);
            }

            $userQuery = new User();
            $userQuery->auth_id = $requestedData['auth_id'];
            $userQuery->base_role = 'corporate';
            $userQuery->status = 'active';
            $userQuery->password = Hash::make($requestedData['password']);
            $userQuery->save();
            if($userQuery->id){
                unset($requestedData['password']);
                unset($requestedData['auth_id']);
                $requestedData['uid'] = $userQuery->id;
                $returned_data['results'] = CorporateClient::create($requestedData);
            }
        } else {
            unset($requestedData['password']);
            unset($requestedData['auth_id']);
            $returned_data['results'] = CorporateClient::where('id', '=', $request->get('id'))->update($requestedData);
        }

        return ResponseWrapper::End($returned_data);
    }

    public function getClientDetails(Request $request, $auth_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        $uid = User::where('auth_id', '=', $auth_id)->value('id');
        $returned_data['results'] = CorporateClient::where('uid', '=', $uid)->first();

        return ResponseWrapper::End($returned_data);
    }
}
