<?php

namespace App\Http\Controllers\panel\user;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MessageAndNotificationController;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\MessageAndNotification;
use App\Models\NetworkSupportCenter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserProfile;
use App\Models\UserRole;
use App\Models\WifiDbRadCheck;
use App\Models\WifiDbRadUserGroup;
use App\Models\Payment;
use App\Models\PanelUser;
use App\Models\School\NMSLotAdmin;
use Carbon\Carbon;
use App\Models\WifiDbUserInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserController extends Controller {

    public function getAgentMonthlyCommissionRate(Request $request, $agent_type, $agent_auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $agent_uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($agent_auth_id);
        $returned_data['results'] = (new \App\Classes\CustomHelpers)->getAgentsMonthlyCommissionRate($agent_type, $agent_uid);
        return ResponseWrapper::End($returned_data);
    }

    public function sendMessageToUser(Request $request, $sender_uid, $receiver_uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $user_auth_id = User::where('id', '=', $receiver_uid)->value('auth_id');
        (new \App\Http\Controllers\MessageAndNotificationController)->OneSignalSendExternalId($user_auth_id, $request->get('title'), $request->get('message'));

        $query = new MessageAndNotification();
        $query->uid = $receiver_uid;
        $query->title = $request->get('title');
        $query->description = $request->get('message');
        $query->sender_uid = $sender_uid;
        $query->is_read = 0;
        $query->save();

        $query['sender_name'] = UserProfile::where('uid', '=', $sender_uid)->value('full_name');

        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    public function userPermissionUpdate(Request $request, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        // Log::info($request->all);
        if(UserPermission::where('name', '=', $request->get('name'))->where('uid', '=', $uid)->exists()){
            $returned_data['results'] = UserPermission::where('name', '=', $request->get('name'))->where('uid', '=', $uid)->delete();
        } else {
            $newPermission = new UserPermission();
            $newPermission->uid = $uid;
            $newPermission->name = $request->get('name');
            $newPermission->save();
            $returned_data['results'] = $newPermission;
        }
        return ResponseWrapper::End($returned_data);
    }

    public function getUserPermissions($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $userPermissions = UserPermission::where('uid', '=', $uid)->pluck('name')->toArray();
        $returned_data['results'] = $userPermissions;
        $returned_data['permissions_list'] = Permission::all()->groupBy('group_name');
        return ResponseWrapper::End($returned_data);
    }

    public function getCorporateUserPermissions($uid, $module_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $userPermissions = UserPermission::where('uid', '=', $uid)->pluck('name')->toArray();
        $returned_data['results'] = $userPermissions;
        $returned_data['permissions_list'] = Permission::whereRaw('JSON_CONTAINS(module_names, ?)', ['"' . $module_name . '"'])->get()->groupBy('group_name');
        return ResponseWrapper::End($returned_data);
    }

    public function getUserPermissionAccess($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $panelAccess = User::where('id', $uid)->value('panel_access');
        $userPermissions = UserPermission::where('uid', '=', $uid)->pluck('name')->toArray();
        $returned_data['results']['panelAccess'] = $panelAccess;
        $returned_data['results']['userPermissions'] = $userPermissions;
        return ResponseWrapper::End($returned_data);
    }

    public function assignAsEmployee(Request $request, $row_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $user = $request->user();
        $full_name = UserProfile::where('uid','=',$row_id)->value('full_name');
        if(!Employee::where('uid', '=', $row_id)->exists()){

            $query = new Employee();
            $query->uid = $row_id;
            $query->designation = $request->get('designation_id');
            $query->status = 'pending';
            $query->updated_by = $user->id;
            $query->save();

            $query->full_name = $full_name;
            $returned_data['results'] = $query;
        } else {
            $returned_data['error_type'] = "employee_exists";
            $returned_data['message'] = "$full_name already assigned as employee";
        }

        return ResponseWrapper::End($returned_data);
    }

    public function searchUserByKeywords(Request $request, $searchType, $keywords) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        if($searchType === 'employee'){
            $query = Employee::query();
            $query->leftJoin('users', 'employees.uid', '=', 'users.id');
        } else {
            $query = User::query();
            $query->leftJoin('employees', 'employees.uid', '=', 'users.id');
        }
        $query->leftJoin('user_profiles as p', 'p.uid', '=', 'users.id');
        $query->leftJoin('user_profiles as uby', 'uby.uid', '=', 'employees.updated_by');

        $keywords = strtolower(trim($keywords));
        if(is_numeric($keywords)){
            $query->where('users.auth_id', 'LIKE', '%' . $keywords . '%');
        } else {
            $query->where('p.full_name', 'LIKE', '%' . $keywords . '%');
        }
        $result = $query->get([
            'users.id',
            'users.id as uid',
            'users.status',
            'users.base_role',
            'users.panel_access',
            'p.full_name',
            'employees.designation',
            'p.mobile_number',
            'p.email',
            'uby.full_name as updated_by',
            'p.created_at',
            'employees.updated_at'
        ]);
        if(!empty($result->designation)){
            $result->designation = EmployeeDesignation::find($result->designation);
        }
        $returned_data['results'] = $result;

        return ResponseWrapper::End($returned_data);
    }

    public function checkWalletBalance(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));
        $returned_data['results'] = UserProfile::where('uid', '=', $uid)->value('wallet_amount');
        return ResponseWrapper::End($returned_data);
    }

    public function access_check(Request $request){
        $result = 0;
        if($request->type === 'panel_access'){
            $result = User::where('auth_id','=', $request->auth_id)->value('panel_access');
        }
        return response()->json(["status"=>"success", "results"=> $result]);
    }
    public function panel_access_update($uid){
        $access_check = PanelUser::where('user_id', $uid)->first();
        $status_check = NMSLotAdmin::where('uid', $uid)->first();
        if($access_check){
            $access_check->update([
                'panel_access' => $access_check->panel_access === 1 ? 0 : 1,
                'status' => $access_check->status === 'active' ? 'suspend' : 'active'
            ]);
            if($status_check){
                $status_check->update([
                    'status' => $status_check->status === 'active' ? 'suspend' : 'active'
                ]);
            }
            return response()->json(["status"=>"success"]);
        } else {
            return response()->json(["status"=>"error", "message"=>"User not found"], 404);
        }
    }
    // public function user_login(Request $request){
    //     $returned_data = ResponseWrapper::Start();

    //     $request->validate([
    //         'auth_id' => 'required',
    //         'password' => 'required',
    //     ]);
        
    //     $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($request->auth_id);
    //     $user = User::where('auth_id', $auth_id)->first();
    //     if(!$user){
    //         $returned_data['error_type']= 'account_not_found';
    //         $returned_data['message']= 'account not found';
    //         return ResponseWrapper::End($returned_data);
    //     }
    //     if(!Hash::check($request->password, $user->password)){
    //         $returned_data['error_type']= 'password_incorrect';
    //         $returned_data['message']= 'password incorrect';
    //         return ResponseWrapper::End($returned_data);
    //     }
    //     if($user->text_password === null || $user->text_password === ''){
    //         $user->text_password = $request->password;
    //         $user->save();
    //     }

    //     // user permissions
    //     $userPermissions = UserPermission::where('uid', '=', $user->id)->pluck('name')->toArray();
    //     $profileData = UserProfile::where('uid', '=', $user->id)->get();
        
    //     if(!empty($profileData)){
    //         $user->force_profile_update = false;
            
    //     } else {
    //         $user->force_profile_update = true;
    //         //Log::info('true');
    //     }
        
    //     if($user->status === 'pending'){
    //         $returned_data['error_type'] = 'account_pending';
    //         return ResponseWrapper::End($returned_data);
    //     } else if($user->status === 'block'){
    //         $returned_data['error_type'] = 'account_blocked';
    //         return ResponseWrapper::End($returned_data);
    //     } else if($user->status === 'suspend'){
    //         $returned_data['error_type'] = 'account_suspend';
    //         return ResponseWrapper::End($returned_data);
    //     }
    //     // if($user->id == 46301){
    //     //     $userData = [
    //     //         'id' => '38776',
    //     //         'auth_id' => '01971399998',
    //     //         'status' => $user->status,
    //     //         'base_role' => $user->base_role,
    //     //         'is_password_changed' => $user->is_password_changed,
    //     //     ];
    //     // }else{
    //     //     $userData = [
    //     //         'id' => $user->id,
    //     //         'auth_id' => $user->auth_id,
    //     //         'status' => $user->status,
    //     //         'base_role' => $user->base_role,
    //     //         'is_password_changed' => $user->is_password_changed,
    //     //     ];
    //     // }


    //     $returned_data['accessToken'] = $user->createToken($request->device_id)->plainTextToken;
    //     $returned_data['results'] = $userData;
    //     $returned_data['permissions'] = $userPermissions;

    //     Log::info($returned_data['results']);
    //     return ResponseWrapper::End($returned_data);
    // }

    public function user_login(Request $request){

        $returned_data = ResponseWrapper::Start();

        $request->validate([
            'auth_id' => 'required',
            'password' => 'required',
        ]);
        $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($request->auth_id);
        $user = User::where('auth_id', $auth_id)->first();
        if(!$user){
            $returned_data['error_type']= 'account_not_found';
            $returned_data['message']= 'account not found';
            return ResponseWrapper::End($returned_data);
        }
        if(! Hash::check($request->password, $user->password)){
            $returned_data['error_type']= 'password_incorrect';
            $returned_data['message']= 'password incorrect';
            return ResponseWrapper::End($returned_data);
        }
        if($user->text_password === null || $user->text_password === ''){
            $user->text_password = $request->password;
            $user->save();
        }


        // user permissions
        $userPermissions = UserPermission::where('uid', '=', $user->id)->pluck('name')->toArray();

        $profileData = UserProfile::where('uid', '=', $user->id)->get();
        if(!empty($profileData)){
            $user->force_profile_update = false;
        } else {
            $user->force_profile_update = true;
        }


        if($user->status === 'pending'){
            $returned_data['error_type'] = 'account_pending';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'block'){
            $returned_data['error_type'] = 'account_blocked';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'suspend'){
            $returned_data['error_type'] = 'account_suspend';
            return ResponseWrapper::End($returned_data);
        }

        $returned_data['accessToken'] = $user->createToken($request->device_id)->plainTextToken;
        $returned_data['results'] = $user;
        $returned_data['permissions'] = $userPermissions;
        //$returned_data['profileData'] = $profileData;
        return ResponseWrapper::End($returned_data);
    }


    public function panel_user_login(Request $request){
        $returned_data = ResponseWrapper::Start();

        $request->validate([
            'auth_id' => 'required',
            'password' => 'required',
        ]);
        
        $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($request->auth_id);
        $user = User::where('auth_id', $auth_id)->first();
        if(!$user){
            $returned_data['error_type']= 'account_not_found';
            $returned_data['message']= 'account not found';
            return ResponseWrapper::End($returned_data);
        }
        if(!Hash::check($request->password, $user->password)){
            $returned_data['error_type']= 'password_incorrect';
            $returned_data['message']= 'password incorrect';
            return ResponseWrapper::End($returned_data);
        }
        if($user->text_password === null || $user->text_password === ''){
            $user->text_password = $request->password;
            $user->save();
        }

        // user permissions
        $userPermissions = UserPermission::where('uid', '=', $user->id)->pluck('name')->toArray();
        $profileData = UserProfile::where('uid', '=', $user->id)->get();
        
        if(!empty($profileData)){
            $user->force_profile_update = false;
            
        } else {
            $user->force_profile_update = true;
            //Log::info('true');
        }
        
        if($user->status === 'pending'){
            $returned_data['error_type'] = 'account_pending';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'block'){
            $returned_data['error_type'] = 'account_blocked';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'suspend'){
            $returned_data['error_type'] = 'account_suspend';
            return ResponseWrapper::End($returned_data);
        }
        if($user->id == 46301){
            $userData = [
                'id' => '38776',
                'auth_id' => '01971399998',
                'status' => $user->status,
                'base_role' => $user->base_role,
                'is_password_changed' => $user->is_password_changed,
            ];
        }else{
            $userData = [
                'id' => $user->id,
                'auth_id' => $user->auth_id,
                'status' => $user->status,
                'base_role' => $user->base_role,
                'is_password_changed' => $user->is_password_changed,
            ];
        }


        $returned_data['accessToken'] = $user->createToken($request->device_id)->plainTextToken;
        $returned_data['results'] = $userData;
        $returned_data['permissions'] = $userPermissions;

        // Log::info($returned_data['results']);
        return ResponseWrapper::End($returned_data);
    }

    public function panel_user_login_by_username(Request $request){
        $returned_data = ResponseWrapper::Start();

        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = PanelUser::where('auth_id', $request->username)->first();
        if(!$user){
            $returned_data['error_type']= 'credential_error';
            $returned_data['message']= 'Invalid Credentials!';
            return ResponseWrapper::End($returned_data);
        }
        if(! Hash::check($request->password, $user->password)){
            $returned_data['error_type']= 'credential_error';
            $returned_data['message']= 'Invalid Credentials!';
            return ResponseWrapper::End($returned_data);
        }
        if($user->text_password === null || $user->text_password === ''){
            $user->text_password = $request->password;
            $user->save();
        }

        // user permissions
        $userPermissions = UserPermission::where('uid', '=', $user->id)->pluck('name')->toArray();
        $profileData = UserProfile::where('uid', $user->id)->get();
        if(!empty($profileData)){
            $user->force_profile_update = false;
        } else {
            $user->force_profile_update = true;
        }

        if($user->status === 'pending'){
            $returned_data['error_type'] = 'account_pending';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'block'){
            $returned_data['error_type'] = 'account_blocked';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'suspend'){
            $returned_data['error_type'] = 'account_suspend';
            return ResponseWrapper::End($returned_data);
        }

        $userData = [
            'id' => $user->id,
            'auth_id' => $user->auth_id,
            'status' => $user->status,
            'base_role' => $user->base_role,
            'is_password_changed' => $user->is_password_changed,
        ];

        $returned_data['accessToken'] = $user->createToken($request->device_id)->plainTextToken;
        $returned_data['results'] = $userData;
        $returned_data['permissions'] = $userPermissions ?? null;
        return ResponseWrapper::End($returned_data);
    }

    public function user_login_support_center(Request $request){
        $returned_data = ResponseWrapper::Start();


        $request->validate([
            'auth_id' => 'required',
            'password' => 'required',
        ]);

        
        $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($request->auth_id);
        $user = User::where('auth_id', $auth_id)->first();
        if(!$user){
            $returned_data['error_type']= 'account_not_found';
            $returned_data['message']= 'account not found';
            return ResponseWrapper::End($returned_data);
        }
        if($user->base_role !== 'support_center'){
            $returned_data['error_type']= 'not_a_support_center';
            $returned_data['message']= 'You are not allowed to login here!';
            return ResponseWrapper::End($returned_data);
        }
        if(! Hash::check($request->password, $user->password)){
            $returned_data['error_type']= 'password_incorrect';
            $returned_data['message']= 'password incorrect';
            return ResponseWrapper::End($returned_data);
        }
        if($user->text_password === null || $user->text_password === ''){
            $user->text_password = $request->password;
            $user->save();
        }

        // user permissions
        $userPermissions = UserPermission::where('uid', '=', $user->id)->pluck('name')->toArray();
        $profileData = UserProfile::where('uid', '=', $user->id)->get();
        if(!empty($profileData)){
            $user->force_profile_update = false;
        } else {
            $user->force_profile_update = true;
        }

        if($user->status === 'pending'){
            $returned_data['error_type'] = 'account_pending';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'block'){
            $returned_data['error_type'] = 'account_blocked';
            return ResponseWrapper::End($returned_data);
        } else if($user->status === 'suspend'){
            $returned_data['error_type'] = 'account_suspend';
            return ResponseWrapper::End($returned_data);
        }

        $userData = [
            'id' => $user->id,
            'auth_id' => $user->auth_id,
            'status' => $user->status,
            'base_role' => $user->base_role,
            'is_password_changed' => $user->is_password_changed,
        ];

        $returned_data['accessToken'] = $user->createToken($request->device_id)->plainTextToken;
        $returned_data['results'] = $userData;
        $returned_data['permissions'] = $userPermissions;
        return ResponseWrapper::End($returned_data);
    }

    public function password_update(Request $request)
    {
        $returned_data = ResponseWrapper::Start();
        $id = $request->id;
        $userInfo = User::find($id);
        if($userInfo !== null){
            $userInfo->password = Hash::make($request->password);
            $userInfo->text_password = $request->password;
            $userInfo->is_password_changed = 1;
            $userInfo->update();
        }
        $returned_data['results'] = true;
        return ResponseWrapper::End($returned_data);
    }

    public function user_registration(Request $request){

        $returned_data = ResponseWrapper::Start();

        if($request->get('full_name') === null){
            $returned_data['error_type']= 'full_name_missing';
            return ResponseWrapper::End($returned_data);
        }

        $request->validate([
            'auth_id' => 'required|numeric|digits:11',
            'password' => 'required|min:4',
        ]);
        $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($request->auth_id);
        $user = User::where('auth_id', $auth_id)->first();
        if($user){
            $returned_data['error_type']= 'account_exist';
            return ResponseWrapper::End($returned_data);
        }

        $userData = [
            'auth_id' => $auth_id,
            'text_password' => $request->get('password'),
            'password' => bcrypt($request->get('password')),
        ];

        $newUserResult = User::create($userData);

        // create user profile
        $newUserResult->force_profile_update = false;
        $userLocationData = ["division_id"=> null, "district_id"=> null, "upazila_id"=> null, "union_id"=> null, "village_id"=> null,];
        if($request->get('user_zone_id') === null || $request->get('user_zone_id') === '') {
            $newUserResult->force_profile_update = true;
        } else {
            $InternetPartner = NetworkSupportCenter::where('id', '=', $request->get('user_zone_id'))->first();
            if(!empty($InternetPartner)){
                $userLocationData['division_id'] = $InternetPartner['division_id'];
                $userLocationData['district_id'] = $InternetPartner['district_id'];
                $userLocationData['upazila_id'] = $InternetPartner['upazila_id'];
                $userLocationData['union_id'] = $InternetPartner['union_id'];
                $userLocationData['village_id'] = $InternetPartner['village_id'];
            }
        }
        (new \App\Classes\CustomHelpers)->create_basic_user_profile($newUserResult->id, $request->get('full_name'), $newUserResult->auth_id, $userLocationData);
        $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজারনেম: ".$auth_id." ও পাসওয়ার্ড: " . $request->get('password');
        $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($request->get('mobile_number'), $smsText);


        $returned_data['results'] = $newUserResult;
        $returned_data['permissions'] = UserPermission::where('uid', '=', $newUserResult->id)->pluck('name')->toArray();
        $returned_data['accessToken'] = $newUserResult->createToken($request->device_id)->plainTextToken;
        MessageAndNotificationController::createNewMessage($newUserResult->id, 1, 'স্বাগতম!', 'স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।');
        return ResponseWrapper::End($returned_data);
    }

    public function getMyData(Request $request) : JsonResponse {
        $user = $request->user();
        $user->profile = UserProfile::find($user->id);
        $user->roles = UserRole::where('uid', '=', $user->id)->pluck('rid')->toArray();
        if(empty($user->permissions)){
            $user->permissions = [];
        } else {
            $user->permissions = json_decode($user->permissions);
        }
        return response()->json($user);
    }

    public function getUserProfile(Request $request, $row_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $user = User::find($row_id);
        $user->profile = UserProfile::where('uid', '=', $row_id)->first();
        //$userPermissions = UserPermission::where('uid', '=', $user->id)->pluck('name')->toArray();

        if(empty($user->permissions)){
            $user->permissions = [];
        } else {
            $user->permissions = json_decode($user->permissions);
        }
        if(!empty($user->profile['device_info'])){
            $user->profile['device_info'] = json_decode($user->profile['device_info']);
        }

        $returned_data['results'] = $user;
        //$returned_data['permissions'] = $userPermissions;
        return ResponseWrapper::End($returned_data);
    }

    public function userProfileUpdate(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));
        $query = UserProfile::where('uid', '=', $userId)->first();

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
        $returned_data['results'] = $query->update();
        $returned_data['data'] = $query;

        return ResponseWrapper::End($returned_data);

    }

    public function checkInternetPackageExist(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));

        $userProfileExist = UserProfile::where('uid', '=', $uid)->exists();
        $internetUserExist = InternetUsers::where('uid', '=', $uid)->exists();
        if($internetUserExist){
            $returned_data['error_type'] = 'already_exist';
        } else if(!$userProfileExist){
            $returned_data['error_type'] = 'update_profile';
        } else {
            $returned_data['results'] = 'no_internet_package';
        }
        return ResponseWrapper::End($returned_data);
    }

    public function getUsersList(Request $request): JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keywords')));
        $skip_user_id = Config::get('constants.skip_user_id');
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        $query = User::query();
        $query->join('user_profiles', 'user_profiles.uid', '=', 'users.id');
        $query->whereNotIn('users.auth_id', $skip_user_id);

        if($keywords) {
            $query->where(function($qr) use ($keywords){
                $qr->where('users.auth_id', '=', $keywords);
            });
        }

        $query->orderBy('users.created_at', $sortBy);
        $query->skip($totalSkip)->take(50);

        $returned_data['results']['total'] = $query->count();
        // dd($returned_data['results']['total']);
        $returned_data['results']['list'] = $query->get(['users.id', 'users.auth_id', 'users.status','users.panel_access','users.base_role','user_profiles.full_name', 'user_profiles.wallet_amount','user_profiles.email','users.created_at']);

        return ResponseWrapper::End($returned_data);
    }

    public function update(Request $request, $id)
    {
        $params = $request->input();

        $user = User::find($id);
        $user->name = $params['name'];
        $user->email = $params['email'];
        $user->mobile_number = $params['mobile_number'];
        $user->panel_access = $params['panel_access'];
        if(isset($params['password']) && !empty($params['password'])){
            $user->password = Hash::make($params['password']);
        }
        $user->save();

        return $user;
    }

    public function passwordRecovery(Request $request){
        $returned_data = ResponseWrapper::Start();
        $auth_id = $request->get('mobile_number');
        $userData = (new \App\Classes\CustomHelpers)->update_user_password($auth_id);
        if($userData['status'] === 'account_not_found'){
            $returned_data['error_type'] = 'account_not_found';
            return ResponseWrapper::End($returned_data);
        }
        
        (new \App\Classes\CustomHelpers)->send_text_sms($auth_id, "আপনার স্বাধীন ওয়াইফাই পাসওয়ার্ডটি হলো- " . $userData['password']);
        $returned_data['results'] = $userData['password'] !== null;
        return ResponseWrapper::End($returned_data);
    }

    public function userData(Request $request) : JsonResponse {
        $getData = $request->user();
        $supportCenter = NetworkSupportCenter::where('uid',$getData->id)->first();
        $returnData = [
            'id' => $getData->id,
            'zone_id' => $supportCenter->zone_id,
            'zone_name' => $supportCenter->zone_name,
            'status' => 'success'
        ];
        return response()->json($returnData);
    }
    
    public function schoolData(Request $request) : JsonResponse {
        $getData = $request->user();

        if($getData->base_role == 'manager'){
            $userProfile = UserProfile::with('school_managers')->where('uid',$getData->user_id)->first();
            $profile_image = 'school/profile/'.$userProfile->school_managers->profile_image;
            $name = $userProfile->full_name;
        }else if($getData->base_role == 'lot_admin'){
            $userProfile = NMSLotAdmin::where('uid', $getData->user_id)->first();
            $profile_image = null;
            $name = $userProfile->name;
        }else{
            $userProfile = UserProfile::where('uid',$getData->user_id)->first();
            $profile_image = null;
            $name = $userProfile->full_name;
        }

        $returnData = [
            'id' => $getData->id,
            'auth_id' => $getData->auth_id,
            'base_role' => $getData->base_role,
            'name' => $name,
            'image' => $profile_image,
            'status' => 'success',
            'permissions' => UserPermission::where('uid', '=', $getData->user_id)->pluck('name')->toArray()
        ];
        return response()->json($returnData);
    }

    public function userDataBillingPanel(Request $request) : JsonResponse {
        $getData = $request->user();
        $returnData = [
            'id' => $getData->id,
            // 'user_type' => InternetUsers::where('uid',$getData->id)->value('user_type'),
            'zone_id' => InternetUsers::where('uid',$getData->id)->value('zone_id'),
            'package_id' => InternetUsers::where('uid',$getData->id)->value('package_id'),
            'status' => 'success'
        ];
        return response()->json($returnData);
    }

    public function zoneAssign(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $user = InternetUsers::where('uid', $request->uid)->first();
        $user->zone_id = $request->zone_id;
        $user->update();

        $returned_data['results'] = true;
        $returned_data['message'] = "Zone Assigned Successfully!";
        return ResponseWrapper::End($returned_data);
    }
}
