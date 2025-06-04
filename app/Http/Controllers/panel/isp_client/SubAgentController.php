<?php

namespace App\Http\Controllers\panel\isp_client;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use App\Models\InternetUsers;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SubAgentController extends Controller
{
    // Agent List --------------
    public function getSubAgentList($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list according to client_id ---
        $query = CorporateSubAgent::query();
        $query->leftJoin('users', 'users.id', '=', 'corporate_sub_agents.uid')
              ->leftJoin('user_profiles', 'user_profiles.uid', '=', 'corporate_sub_agents.uid')
              ->select([
                  'corporate_sub_agents.id',
                  'corporate_sub_agents.uid',
                  'corporate_sub_agents.client_id',
                  'corporate_sub_agents.agent_id',
                  DB::raw("(SELECT full_name FROM user_profiles WHERE user_profiles.uid = corporate_sub_agents.agent_id) as added_by"),
                  'user_profiles.full_name',
                  'users.auth_id as username',
                  'users.text_password',
                  'user_profiles.email',
                  'user_profiles.mobile_number',
                  'user_profiles.whatsapp_number',
                  'corporate_sub_agents.status',
                  'corporate_sub_agents.activated_at as activate_at',
                  'corporate_sub_agents.created_at',
                  'user_profiles.nid',
                  'user_profiles.division_id',
                  'user_profiles.district_id',
                  'user_profiles.upazila_id',
                  'corporate_sub_agents.union_name',
                  'corporate_sub_agents.village_name',
                  'user_profiles.house_no',
                  'user_profiles.address',
                  'user_profiles.address_direction',
                  'corporate_sub_agents.balance',
                  'corporate_sub_agents.commission',
                  DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'broadband' AND internet_users.sub_agent_id = corporate_sub_agents.uid) as secret_count"),
                  DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'wifi' AND internet_users.sub_agent_id = corporate_sub_agents.uid) as userinfo_count")
              ]);

            $client = CorporateClient::where('uid',$user_id)->exists();
            $agent = CorporateAgent::where('uid',$user_id)->exists();

            if($client){
                $query->where('client_id', '=', $user_id);
            } elseif($agent){
                $query->where('agent_id', '=', $user_id);
            }

            $query->orderBy('corporate_sub_agents.created_at', 'DESC');

            $returned_data['results']['list'] = $query->get();

            // Cast status to string in the result array
            foreach ($returned_data['results']['list'] as $result) {
                $result->status = (int) $result->status;
            }

            // Agent Balance ----
            $agent_balance = CorporateAgent::where('uid',$user_id)->value('balance');
            $returned_data['results']['balance'] = $agent_balance;

            return ResponseWrapper::End($returned_data);
    }

    // Agent List --------------
    public function getSubAgentListAdmin() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list according to client_id ---
        $query = CorporateSubAgent::query();
        $query->leftJoin('users', 'users.id', '=', 'corporate_sub_agents.uid')
                ->leftJoin('user_profiles', 'user_profiles.uid', '=', 'corporate_sub_agents.uid')
                ->select([
                    'corporate_sub_agents.id',
                    'corporate_sub_agents.uid',
                    'corporate_sub_agents.client_id',
                    'corporate_sub_agents.agent_id',
                    DB::raw("(SELECT zone_name FROM corporate_clients WHERE corporate_clients.uid = corporate_sub_agents.client_id) as zone_name"),
                    DB::raw("(SELECT full_name FROM user_profiles WHERE user_profiles.uid = corporate_sub_agents.agent_id) as added_by"),
                    'user_profiles.full_name',
                    'users.auth_id as username',
                    'users.text_password',
                    'user_profiles.email',
                    'user_profiles.mobile_number',
                //   'user_profiles.whatsapp_number',
                    'corporate_sub_agents.status',
                    'corporate_sub_agents.activated_at as activate_at',
                //   'corporate_sub_agents.created_at',
                //   'user_profiles.nid',
                //   'user_profiles.division_id',
                //   'user_profiles.district_id',
                //   'user_profiles.upazila_id',
                //   'corporate_sub_agents.union_name',
                //   'corporate_sub_agents.village_name',
                //   'user_profiles.house_no',
                //   'user_profiles.address',
                //   'user_profiles.address_direction',
                    'corporate_sub_agents.balance',
                    'corporate_sub_agents.commission',
                    DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'broadband' AND internet_users.sub_agent_id = corporate_sub_agents.uid) as secret_count"),
                    DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'wifi' AND internet_users.sub_agent_id = corporate_sub_agents.uid) as userinfo_count")
                ]);

            $query->orderBy('corporate_sub_agents.created_at', 'DESC');
            $returned_data['results']['list'] = $query->get([
                'corporate_sub_agents.id',
                'corporate_sub_agents.uid',
                'corporate_sub_agents.client_id',
                'corporate_sub_agents.agent_id',
                DB::raw("(SELECT zone_name FROM corporate_clients WHERE corporate_clients.uid = corporate_sub_agents.client_id) as zone_name"),
                DB::raw("(SELECT full_name FROM user_profiles WHERE user_profiles.uid = corporate_sub_agents.agent_id) as added_by"),
                'user_profiles.full_name',
                'users.auth_id as username',
                'users.text_password',
                'user_profiles.email',
                'user_profiles.mobile_number',
                // 'user_profiles.whatsapp_number',
                'corporate_sub_agents.status',
                'corporate_sub_agents.activated_at as activate_at',
                // 'corporate_sub_agents.created_at',
                // 'user_profiles.nid',
                // 'user_profiles.division_id',
                // 'user_profiles.district_id',
                // 'user_profiles.upazila_id',
                // 'corporate_sub_agents.union_name',
                // 'corporate_sub_agents.village_name',
                // 'user_profiles.house_no',
                // 'user_profiles.address',
                // 'user_profiles.address_direction',
                'corporate_sub_agents.balance',
                'corporate_sub_agents.commission',
                DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'broadband' AND internet_users.sub_agent_id = corporate_sub_agents.uid) as secret_count"),
                DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'wifi' AND internet_users.sub_agent_id = corporate_sub_agents.uid) as userinfo_count")
            ]);

        // Cast status to string in the result array
        foreach ($returned_data['results']['list'] as $result) {
            $result->status = (int) $result->status;
        }

        return ResponseWrapper::End($returned_data);
    }

    public function getSubAgentDetails($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list according to client_id ---
        $query = CorporateSubAgent::with(['userProfile' => function($query) {
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_divisions WHERE geo_divisions.id = user_profiles.division_id) as division_name"));
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_districts WHERE geo_districts.id = user_profiles.district_id) as district_name"));
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_upazilas WHERE geo_upazilas.id = user_profiles.upazila_id) as upazila_name"));
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_union_pouroshovas WHERE geo_union_pouroshovas.id = user_profiles.union_id) as union_name"));
        }])->where('uid', $uid)->get();
        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    // Store Agent -------------
    public function registerNewSubAgent(Request $request, $user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'sub_agent_name' => 'required',
            'contact_number' => ['required', 'regex:/^(01[6789])(\d{8})$/'],
            'contact_number' => 'required',
            'email' => 'required|email',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'commission' => 'required',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Unique Validation --
        $auth_id = User::where('auth_id', $request->get('contact_number'))->exists();
        if ($auth_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "This mobile number already in use.";
            return ResponseWrapper::End($returned_data);
        } else {

            // Create user in user table
            $userData = (new \App\Classes\CustomHelpers)->create_new_user($request->get('contact_number'), 'sub_agent');
            $uid = $userData['user']['id'];
            $password = $userData['password'];

            // create new profile
            $userProfile = new UserProfile();
            $userProfile->uid = $uid;
            $userProfile->full_name = $request->get('sub_agent_name');
            $userProfile->mobile_number = $request->get('contact_number');
            $userProfile->whatsapp_number = $request->get('whatsapp_number');
            $userProfile->email = $request->get('email');
            $userProfile->nid = $request->get('nid');
            $userProfile->division_id = $request->get('division');
            $userProfile->district_id = $request->get('district');
            $userProfile->upazila_id = $request->get('upazila');
            $userProfile->house_no = $request->get('house');
            $userProfile->address = $request->get('address');
            $userProfile->address_direction = $request->get('address_direction');
            $userProfile->save();

            // Finding Client ID --
            $client_id = CorporateAgent::where('uid',$user_id)->value('client_id');

            // Saving Agent Table
            $agent = new CorporateSubAgent();
            $agent->uid = $uid;
            $agent->client_id = $client_id;
            $agent->agent_id = $user_id;
            $agent->village_name = $request->get('village');
            $agent->union_name = $request->get('union');
            $agent->commission = $request->get('commission');
            $agent->status = 1;
            $agent->activated_at = Carbon::now();
            $agent->save();

            $returned_data['message'] = "আপনি নতুন একজন সাব-এজেন্ট তৈরী করেছেন। সাব-এজেন্টের Username : ".$request->get('contact_number')." এবং Password :" . $password;
            $returned_data['status'] = 'success';
            return ResponseWrapper::End($returned_data);
        }
    }

    // Update Agent -------------
    public function updateSubAgent(Request $request, $user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
                'sub_agent_name' => 'required',
                'contact_number' => ['required', 'regex:/^(01[6789])(\d{8})$/'],
                'contact_number' => 'required',
                'email' => 'required|email',
                'division' => 'required',
                'district' => 'required',
                'upazila' => 'required',
                'union' => 'required',
                'village' => 'required',
                'commission' => 'required',
            ]);

            if(!$validated){
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Validation Failed!";
                return ResponseWrapper::End($returned_data);
            }

        // Check if contact number needs to be updated and is unique
        $numberCheck = User::where('id', $user_id)->value('auth_id');
        if ($numberCheck !== $request->get('contact_number')) {
            $auth_id_exists = User::where('auth_id', $request->get('contact_number'))->exists();
            if ($auth_id_exists) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "This mobile number is already in use.";
                return ResponseWrapper::End($returned_data);
            }
        }

        // Update user in user table
        $user = User::where('id', $user_id)->first();
        if ($user) {
            $user->update([
                'auth_id' => $request->get('contact_number'),
                'password' => Hash::make($request->get('password')),
                'text_password' => $request->get('password'),
            ]);
        }

        // Update user profile
        $userProfile = UserProfile::where('uid', $user_id)->first();
        if ($userProfile) {
            $userProfile->update([
                'full_name' => $request->get('sub_agent_name'),
                'mobile_number' => $request->get('contact_number'),
                'whatsapp_number' => $request->get('whatsapp_number'),
                'email' => $request->get('email'),
                'nid' => $request->get('nid'),
                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'house_no' => $request->get('house'),
                'address' => $request->get('address'),
                'address_direction' => $request->get('address_direction')
            ]);
        }

        // Finding Client ID
        $client_id = CorporateSubAgent::where('uid', $user_id)->value('client_id');
        $agent_id = CorporateSubAgent::where('uid', $user_id)->value('agent_id');
        if (!$client_id && !$agent_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Client ID not found for the given user.";
            return ResponseWrapper::End($returned_data);
        }

        // Update sub_agent information
        $agent = CorporateSubAgent::where('uid', $user_id)->first();
        if ($agent) {
            $agent->update([
                'client_id' => $client_id,
                'agent_id' => $agent_id,
                'village_name' => $request->get('village'),
                'union_name' => $request->get('union'),
                'commission' => $request->get('commission'),
                'status' => $request->get('status')
            ]);
        }

        $password = User::where('id', $user_id)->value('text_password');

        $returned_data['status'] = 'success';
        $returned_data['message'] = "আপনি সাব-এজেন্টের তথ্য আপডেট করেছেন। সাব-এজেন্টের Username : " . $request->get('contact_number') . " এবং Password :" . $password;
        return ResponseWrapper::End($returned_data);
    }

    // Delete Agent
    public function deleteSubAgent($user_id, $agent_id, $sub_agent_uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $checkClient = CorporateSubAgent::where('client_id', $user_id)->exists();
        $checkAgent = CorporateSubAgent::where('agent_id', $agent_id)->exists();

        if($checkClient || $checkAgent){
            $userDeleted = User::where('id', $sub_agent_uid)->delete();
            $profileDeleted = UserProfile::where('uid', $sub_agent_uid)->delete();
            $subAgentDeleted = CorporateSubAgent::where('uid', $sub_agent_uid)->delete();
            if ($userDeleted && $profileDeleted && $subAgentDeleted) {
                $returned_data['results'] = true;
                $returned_data['status'] = 'success';
                $returned_data['message'] = "Sub Agent deleted successfully!";
            } else {
                $returned_data['results'] = false;
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Try again something went wrong!";
            }
            return ResponseWrapper::End($returned_data);
        } else{
            $returned_data['results'] = false;
            $returned_data['status'] = 'error';
            $returned_data['message'] = "You are not allowed to delete this sub-agent.";
        }
    }
}
