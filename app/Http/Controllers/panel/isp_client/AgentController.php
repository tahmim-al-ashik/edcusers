<?php

namespace App\Http\Controllers\panel\isp_client;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateAgent;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    // Agent List --------------
    public function getAgentList($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list according to client_id ---
        $query = CorporateAgent::query();
        $query->leftJoin('users', 'users.id', '=', 'corporate_agents.uid')
              ->leftJoin('user_profiles', 'user_profiles.uid', '=', 'corporate_agents.uid')
              ->select([
                  'corporate_agents.id',
                  'corporate_agents.uid',
                  'user_profiles.full_name',
                  'users.auth_id as username',
                  'users.text_password',
                  'user_profiles.email',
                  'user_profiles.mobile_number',
                  'user_profiles.whatsapp_number',
                  'corporate_agents.status',
                  'corporate_agents.activated_at as activate_at',
                  'corporate_agents.created_at',
                  'user_profiles.nid',
                  'user_profiles.division_id',
                  'user_profiles.district_id',
                  'user_profiles.upazila_id',
                  'corporate_agents.union_name',
                  'corporate_agents.village_name',
                  'user_profiles.house_no',
                  'user_profiles.address',
                  'user_profiles.address_direction',
                  'user_profiles.wallet_amount as balance',
                  'corporate_agents.commission',
                  DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'broadband' AND internet_users.agent_id = corporate_agents.uid) as secret_count"),
                  DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'wifi' AND internet_users.agent_id = corporate_agents.uid) as userinfo_count")
              ]);

                $query->where('client_id', '=', $user_id); // where('client_id', '=', $client_id)
              $query->orderBy('corporate_agents.created_at', 'DESC');

        $returned_data['results']['list'] = $query->get([
            'corporate_agents.id',
            'corporate_agents.uid',
            'user_profiles.full_name',
            'users.auth_id as username',
            'users.text_password',
            'user_profiles.email',
            'user_profiles.mobile_number',
            'user_profiles.whatsapp_number',
            'corporate_agents.status',
            'corporate_agents.activated_at as activate_at',
            'corporate_agents.created_at',
            'user_profiles.nid',
            'user_profiles.division_id',
            'user_profiles.district_id',
            'user_profiles.upazila_id',
            'corporate_agents.union_name',
            'corporate_agents.village_name',
            'user_profiles.house_no',
            'user_profiles.address',
            'user_profiles.address_direction',
            'user_profiles.wallet_amount as balance',
            'corporate_agents.commission',
            DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'broadband' AND internet_users.agent_id = corporate_agents.uid) as secret_count"),
            DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'wifi' AND internet_users.agent_id = corporate_agents.uid) as userinfo_count")
        ]);

        // Cast status to string in the result array
        foreach ($returned_data['results']['list'] as $result) {
            $result->status = (int) $result->status;
        }

        // Client Balance ----
        $client_balance = CorporateClient::where('uid',$user_id)->value('balance');
        $returned_data['results']['balance'] = $client_balance;

        return ResponseWrapper::End($returned_data);
    }

    public function getAgentListAdmin() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list according to client_id ---
        $query = CorporateAgent::query();
        $query->join('users', 'users.id', '=', 'corporate_agents.uid')
              ->join('user_profiles', 'user_profiles.uid', '=', 'corporate_agents.uid')
              ->select([
                  'corporate_agents.id',
                  'corporate_agents.uid',
                  'corporate_agents.client_id',
                  DB::raw("(SELECT zone_name FROM corporate_clients WHERE corporate_clients.uid = corporate_agents.client_id) as zone_name"),
                  'user_profiles.full_name',
                  'users.auth_id as username',
                  'users.text_password',
                  'user_profiles.email',
                  'user_profiles.mobile_number',
                //   'user_profiles.whatsapp_number',
                  'corporate_agents.status',
                  'corporate_agents.activated_at as activate_at',
                  'corporate_agents.created_at',
                //   'user_profiles.nid',
                //   'user_profiles.division_id',
                //   'user_profiles.district_id',
                //   'user_profiles.upazila_id',
                //   'corporate_agents.union_name',
                //   'corporate_agents.village_name',
                //   'user_profiles.house_no',
                //   'user_profiles.address',
                //   'user_profiles.address_direction',
                  'user_profiles.wallet_amount as balance',
                //   'corporate_agents.balance',
                  'corporate_agents.commission',
                  DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'broadband' AND internet_users.agent_id = corporate_agents.uid) as secret_count"),
                  DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'wifi' AND internet_users.agent_id = corporate_agents.uid) as userinfo_count")
              ]);

        $query->orderBy('corporate_agents.created_at', 'DESC');

        $returned_data['results']['list'] = $query->get([
            'corporate_agents.id',
            'corporate_agents.uid',
            'corporate_agents.client_id',
            DB::raw("(SELECT zone_name FROM corporate_clients WHERE corporate_clients.uid = corporate_agents.client_id) as zone_name"),
            'user_profiles.full_name',
            'users.auth_id as username',
            'users.text_password',
            'user_profiles.email',
            'user_profiles.mobile_number',
            // 'user_profiles.whatsapp_number',
            'corporate_agents.status',
            'corporate_agents.activated_at as activate_at',
            'corporate_agents.created_at',
            // 'user_profiles.nid',
            // 'user_profiles.division_id',
            // 'user_profiles.district_id',
            // 'user_profiles.upazila_id',
            // 'corporate_agents.union_name',
            // 'corporate_agents.village_name',
            // 'user_profiles.house_no',
            // 'user_profiles.address',
            // 'user_profiles.address_direction',
            'user_profiles.wallet_amount as balance',
            'corporate_agents.commission',
            DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'broadband' AND internet_users.agent_id = corporate_agents.uid) as secret_count"),
            DB::raw("(SELECT COUNT(*) FROM internet_users WHERE internet_users.package_type = 'wifi' AND internet_users.agent_id = corporate_agents.uid) as userinfo_count")
        ]);

        // Cast status to string in the result array
        foreach ($returned_data['results']['list'] as $result) {
            $result->status = (int) $result->status;
        }

        return ResponseWrapper::End($returned_data);
    }

    public function getAgentDetails($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list according to client_id ---
        $query = CorporateAgent::with(['userProfile' => function($query) {
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_divisions WHERE geo_divisions.id = user_profiles.division_id) as division_name"));
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_districts WHERE geo_districts.id = user_profiles.district_id) as district_name"));
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_upazilas WHERE geo_upazilas.id = user_profiles.upazila_id) as upazila_name"));
            $query->addSelect('user_profiles.*', DB::raw("(SELECT en_name FROM geo_union_pouroshovas WHERE geo_union_pouroshovas.id = user_profiles.union_id) as union_name"));
        }])->where('uid', $uid)->get();

        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }
    
    // Store Agent -------------
    public function registerNewAgent(Request $request, $user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'agent_name' => 'required',
            'contact_number' => ['required', 'regex:/^(01[6789])(\d{8})$/'],
            'contact_number' => 'required',
            'email' => 'required|email',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'commission' => 'required',
        ],[
            'agent_name.required' => 'Agent name is required.',
            'contact_number.required' => 'Contact number is required.',
            'contact_number.regex' => 'Your number should be from Bangladesh.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'division.required' => 'Division is required.',
            'district.required' => 'District is required.',
            'upazila.required' => 'Upazila is required.',
            'union.required' => 'Union is required.',
            'village.required' => 'Village is required.',
            'commission.required' => 'Commission is required.',
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
            $userData = (new \App\Classes\CustomHelpers)->create_new_user($request->get('contact_number'), 'agent');
            $uid = $userData['user']['id'];
            $password = $userData['password'];

            // create new profile
            $userProfile = new UserProfile();
            $userProfile->uid = $uid;
            $userProfile->full_name = $request->get('agent_name');
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

            // Saving Agent Table
            $agent = new CorporateAgent();
            $agent->uid = $uid;
            $agent->client_id = $user_id;
            $agent->village_name = $request->get('village');
            $agent->union_name = $request->get('union');
            $agent->commission = $request->get('commission');
            $agent->status = 1;
            $agent->activated_at = Carbon::now();
            $agent->save();

            $returned_data['message'] = "আপনি নতুন একজন এজেন্ট তৈরী করেছেন। এজেন্টের Username : ".$request->get('contact_number')." এবং Password :" . $password;
            $returned_data['status'] = 'success';
            return ResponseWrapper::End($returned_data);
        }
    }

    // Update Agent -------------
    public function updateAgent(Request $request, $user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'agent_name' => 'required',
            'contact_number' => ['required', 'regex:/^(01[6789])(\d{8})$/'],
            'contact_number' => 'required',
            'email' => 'required|email',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'commission' => 'required',
        ],[
            'agent_name.required' => 'Agent name is required.',
            'contact_number.required' => 'Contact number is required.',
            'contact_number.regex' => 'Your number should be from Bangladesh.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'division.required' => 'Division is required.',
            'district.required' => 'District is required.',
            'upazila.required' => 'Upazila is required.',
            'union.required' => 'Union is required.',
            'village.required' => 'Village is required.',
            'commission.required' => 'Commission is required.',
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
                'full_name' => $request->get('agent_name'),
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
        $client_id = CorporateAgent::where('uid', $user_id)->value('client_id');
        if (!$client_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Client ID not found for the given user.";
            return ResponseWrapper::End($returned_data);
        }

        // Update agent information
        $agent = CorporateAgent::where('uid', $user_id)->first();
        if ($agent) {
            $agent->update([
                'client_id' => $client_id,
                'village_name' => $request->get('village'),
                'union_name' => $request->get('union'),
                'commission' => $request->get('commission'),
                'status' => $request->get('status')
            ]);
        }

        $password = User::where('id', $user_id)->value('text_password');

        $returned_data['status'] = 'success';
        $returned_data['message'] = "আপনি এজেন্টের তথ্য আপডেট করেছেন। এজেন্টের Username : " . $request->get('contact_number') . " এবং Password :" . $password;
        return ResponseWrapper::End($returned_data);
    }

    // Delete Agent
    public function deleteAgent($user_id, $agent_uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $checkClient = CorporateAgent::where('client_id', $user_id)->exists();
        if($checkClient){
            $userDeleted = User::where('id', $agent_uid)->delete();
            $profileDeleted = UserProfile::where('uid', $agent_uid)->delete();
            $agentDeleted = CorporateAgent::where('uid', $agent_uid)->delete();
            if ($userDeleted && $profileDeleted && $agentDeleted) {
                $returned_data['results'] = true;
                $returned_data['status'] = 'success';
                $returned_data['message'] = "Agent deleted successfully!";
            } else {
                $returned_data['results'] = false;
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Try again something went wrong!";
            }
            return ResponseWrapper::End($returned_data);
        } else{
            $returned_data['results'] = false;
            $returned_data['status'] = 'error';
            $returned_data['message'] = "You are not allowed to delete this agent.";
        }

    }
}
