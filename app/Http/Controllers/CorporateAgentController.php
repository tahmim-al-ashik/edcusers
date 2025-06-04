<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Classes\ResponseWrapper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\InternetUsers;
use App\Models\Classes\GlobalClasses;
use App\Models\CorporateSubAgent;
use Illuminate\Support\Facades\DB;

class CorporateAgentController extends Controller
{
    // Agent List ---
    public function index(Request $request, $uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong Token!',
            ]);
        }

        $query = CorporateAgent::query();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'corporate_agents.uid');
        $query->leftJoin("users", 'users.id', '=', 'corporate_agents.uid');
        $query->select([
            'corporate_agents.id',
            'corporate_agents.uid',
            'user_profiles.full_name',
            'user_profiles.mobile_number',
            'user_profiles.email',
            'users.text_password',
            'corporate_agents.status',
            'corporate_agents.activated_at'
        ]);
        $query->where('corporate_agents.client_id',$uid);
        $returned_data['results'] = $query->get();
        return ResponseWrapper::End($returned_data);
    }

    // Store Agent ---
    public function store(Request $request, $user_id) : JsonResponse
    {
        $validated = $request->validate([
            'agent_name' => 'required',
            'contact_number' => ['required', 'regex:/^(01[6789])(\d{8})$/'],
            'email' => 'required|email',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
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
        ]);

        if(!$validated){
            return response()->json([
                'status' => 'error',
                'message' => 'Something missing, Try Again!',
            ]);
        }

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong Token!',
            ]);
        }

        // Unique Validation --
        $auth_id = User::where('auth_id', $request->get('contact_number'))->exists();
        if ($auth_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'This number already in use, try another number!',
            ]);
        } else {
            // Create user in user table
            $userData = (new \App\Classes\CustomHelpers)->create_new_user($request->get('contact_number'), 'agent');
            $uid = $userData['user']['id'];
            $password = $userData['password'];

            // Create new profile
            $userProfile = new UserProfile();
            $userProfile->uid = $uid;
            $userProfile->full_name = $request->get('agent_name');
            $userProfile->mobile_number = $request->get('contact_number');
            $userProfile->whatsapp_number = $request->get('whatsapp_number');
            $userProfile->email = $request->get('email');
            $userProfile->nid = $request->get('nid');
            $userProfile->gender = $request->get('gender');
            $userProfile->division_id = $request->get('division');
            $userProfile->district_id = $request->get('district');
            $userProfile->upazila_id = $request->get('upazila');
            $userProfile->union_id = $request->get('union');
            $userProfile->village_id = $request->get('village');
            $userProfile->house_no = $request->get('house');
            $userProfile->address = $request->get('address');
            $userProfile->save();

            // Save Agent in Agent Table
            $agent = new CorporateAgent();
            $agent->uid = $uid;
            $agent->client_id = $user_id;
            $agent->village_name = DB::raw("(SELECT bn_name FROM geo_villages WHERE geo_villages.id = " . $request->get('village') . ")");
            $agent->union_name = DB::raw("(SELECT bn_name FROM geo_union_pouroshovas WHERE geo_union_pouroshovas.id = " . $request->get('union') . ")");
            $agent->status = '1';
            $agent->activated_at = Carbon::now();
            $agent->save();

            return response()->json([
                'status' => 'success',
                'password' => $password,
                'message' => "আপনি নতুন একজন এজেন্ট তৈরী করেছেন। এজেন্টের Username : ".$request->get('contact_number')." এবং Password :" . $password,
            ]);
        }
    }

    // Single Agent Details ---
    public function getAgentDetails(Request $request, $client_id, $agent_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong Token!',
            ]);
        }

        $query = CorporateAgent::query();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'corporate_agents.uid');
        $query->leftJoin("users", 'users.id', '=', 'corporate_agents.uid');
        $query->leftJoin('geo_divisions', 'user_profiles.division_id', '=', 'geo_divisions.id');
        $query->leftJoin('geo_districts', 'user_profiles.district_id', '=', 'geo_districts.id');
        $query->leftJoin('geo_upazilas', 'user_profiles.upazila_id', '=', 'geo_upazilas.id');
        $query->leftJoin('geo_union_pouroshovas', 'user_profiles.union_id', '=', 'geo_union_pouroshovas.id');
        $query->leftJoin('geo_villages', 'user_profiles.village_id', '=', 'geo_villages.id');
        $query->select([
            'corporate_agents.id',
            'corporate_agents.uid',
            'user_profiles.full_name',
            'user_profiles.mobile_number',
            'user_profiles.whatsapp_number',
            'user_profiles.nid',
            'user_profiles.gender',
            'user_profiles.email',
            'user_profiles.division_id',
            'user_profiles.district_id',
            'user_profiles.upazila_id',
            'user_profiles.union_id',
            'user_profiles.village_id',
            'user_profiles.house_no',
            'users.text_password',
            'corporate_agents.status',
            'geo_divisions.en_name as division',
            'geo_districts.en_name as district',
            'geo_upazilas.en_name as upazila',
            'geo_union_pouroshovas.en_name as union',
            'geo_villages.bn_name as village',
            'user_profiles.address',
            'corporate_agents.activated_at'
        ]);
        $query->where('corporate_agents.uid',$agent_id);
        $query->where('corporate_agents.client_id',$client_id);

        $returned_data['results'] = $query->get();
        return ResponseWrapper::End($returned_data);
    }

    // Update Agent Details
    public function update(Request $request, $client_id, $agent_id) : JsonResponse
    {
        $validated = $request->validate([
            'agent_name' => 'required',
            'email' => 'required|email',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
        ],[
            'agent_name.required' => 'Agent name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'division.required' => 'Division is required.',
            'district.required' => 'District is required.',
            'upazila.required' => 'Upazila is required.',
            'union.required' => 'Union is required.',
            'village.required' => 'Village is required.',
        ]);

        if(!$validated){
            return response()->json([
                'status' => 'error',
                'message' => 'Something missing, Try Again!',
            ]);
        }

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong Token!',
            ]);
        }

        // Unique Validation --
        $auth_id = User::where('auth_id', $request->get('contact_number'))->exists();
        if ($auth_id) {
            // Create new profile
            $userProfile = UserProfile::where('uid',$agent_id)->update([
                'full_name' => $request->get('agent_name'),
                'whatsapp_number' => $request->get('whatsapp_number'),
                'email' => $request->get('email'),
                'nid' => $request->get('nid'),
                'gender' => $request->get('gender'),
                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'union_id' => $request->get('union'),
                'village_id' => $request->get('village'),
                'house_no' => $request->get('house'),
                'address' => $request->get('address')
            ]);

            // Save Agent in Agent Table
            $agent = CorporateAgent::where('uid',$agent_id)->update([
                "client_id" => $client_id,
                "village_name" => DB::raw("(SELECT bn_name FROM geo_villages WHERE geo_villages.id = " . $request->get('village') . ")"),
                "union_name" => DB::raw("(SELECT bn_name FROM geo_union_pouroshovas WHERE geo_union_pouroshovas.id = " . $request->get('union') . ")"),
                "status" => '1'
            ]);

            $password = User::where('auth_id', $request->get('contact_number'))->value('text_password');

            return response()->json([
                'password' => $password,
                'status' => 'success',
                'message' => "You updated agent information successfully!",
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "This user does not exists!",
            ]);
        }
    }

    // Update Status
    public function status(Request $request, $client_id, $agent_id) : JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required',
        ],[
            'status.required' => 'Status is required.',
        ]);

        if(!$validated){
            return response()->json([
                'status' => 'error',
                'message' => 'Something missing, Try Again!',
            ]);
        }

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong Token!',
            ]);
        }

        // Unique Validation --
        $auth_id = User::where('id', $agent_id)->exists();
        if ($auth_id){

            $agent = CorporateAgent::where('uid',$agent_id)->where('client_id',$client_id)->update([
                "status" =>  $request->get('status')
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "You updated agent status successfully!",
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "This user does not exists!",
            ]);
        }
    }

    // Update Password
    public function password(Request $request, $client_id, $agent_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'password' => 'required',
        ],[
            'password.required' => 'Password is required.',
        ]);

        if(!$validated){
            return response()->json([
                'status' => 'error',
                'message' => 'Something missing, Try Again!',
            ]);
        }

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong Token!',
            ]);
        }

        // Unique Validation ---
        $auth_id = User::where('id', $agent_id)->exists();
        $client_id = CorporateAgent::where('client_id', $client_id)->exists();
        if ($auth_id && $client_id){
            $agent = User::where('id',$agent_id)->update([
                "password" => Hash::make($request->get('password')),
                "text_password" => $request->get('password')
            ]);
            $password = User::where('id', $agent_id)->value('text_password');
            return response()->json([
                'status' => 'success',
                'password' => $password,
                'message' => "You updated agent password successfully!",
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "This user does not exists!",
            ]);
        }
    }

    // Delete User
    public function destroy(Request $request, $client_id, $agent_id) : JsonResponse
    {
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong Token!',
            ]);
        }

        // Unique Validation ---
        $auth_id = User::where('id', $agent_id)->exists();
        $client_id = CorporateAgent::where('client_id', $client_id)->exists();

        if($auth_id && $client_id){
            $user = User::where('id', '=', $agent_id)->delete();
            $user_profile = UserProfile::where('uid', '=', $agent_id)->delete();
            $agent = CorporateAgent::where('uid', '=', $agent_id)->delete();
            if($user && $user_profile && $agent){
                return response()->json([
                    'status' => 'success',
                    'message' => 'You have deleted agent suucessfully!',
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missed any table!',
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'This use not exists!',
            ]);
        }
    }
}
