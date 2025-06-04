<?php

namespace App\Http\Controllers\panel\shadhin_client;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MessageAndNotificationController;
use App\Imports\ISPClientHotspotUserBulkRegisterImport;
use App\Models\AffiliateHistory;
use App\Models\AgentCommissionBreakdown;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\BroadbandDbUsers;
use App\Models\BroadbandDbZone;
use App\Models\CorporateAgent;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUpazila;
use App\Models\InternetPackage;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\PaymentToken;
use App\Models\UserProfile;
use App\Models\WifiDbBkashInfo;
use App\Models\WifiDbBkashInfoClient;
use App\Models\WifiDbPayment;
use App\Models\WifiDbPaymentClient;
use App\Models\WifiDbRadAcct;
use App\Models\WifiDbRadCheck;
use App\Models\WifiDbRadCheckClient;
use App\Models\WifiDbRadReply;
use App\Models\WifiDbRadReplyClient;
use App\Models\WifiDbRadUserGroup;
use App\Models\WifiDbRadUserGroupClient;
use App\Models\WifiDbUserInfo;
use App\Models\WifiDbUserInfoClient;
use App\Models\NetworkSupportCenter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ShadhinHotspotClientController extends Controller
{
    // hotspot billing portal
    public function user_login_hotspot_billing(Request $request){
        $returned_data = ResponseWrapper::Start();

        $request->validate([
            'auth_id' => 'required',
            'password' => 'required',
        ]);

        $radCheck = WifiDbRadCheck::where('username',$request->auth_id)->first();
        //Log::info($radCheck);
        //Log::info($request->auth_id);
        if(!$radCheck){
            Log::info('Id-'.$request->auth_id.'Not found in radCheck table');
            $returned_data['error_type']= 'radCheck_not_found';
            $returned_data['message']= 'You are not allowed to login here.';
            return ResponseWrapper::End($returned_data);
        }

        $userInfo = WifiDbUserInfo::where('username',$request->auth_id)->first();
        if(!$userInfo){
            Log::info('Id-'.$request->auth_id.'Not found in userInfo table');
            $returned_data['error_type']= 'userInfo_not_found';
            $returned_data['message']= 'You are not allowed to login here.';
            return ResponseWrapper::End($returned_data);
        }

        $radUserGroup = WifiDbUserInfo::where('username',$request->auth_id)->first();
        if(!$radUserGroup){
            Log::info('Id-'.$request->auth_id.'Not found in radUserGroup table');
            $returned_data['error_type']= 'radUserGroup_not_found';
            $returned_data['message']= 'You are not allowed to login here.';
            return ResponseWrapper::End($returned_data);
        }

        $supportCenter = NetworkSupportCenter::where('zone_name',$radCheck->branch)->first();
        if(!$supportCenter){
            Log::info($radCheck->branch.' support center not activated yet.');
            $returned_data['error_type']= 'supportCenter_not_found';
            $returned_data['message']= $radCheck->branch.' support center not activated yet.';
            return ResponseWrapper::End($returned_data);
        }

        // Need zone_id, division_id, district_id, upazila_id, union_id, package_id
        $zone_id = $supportCenter->zone_id;
        $package_id = InternetPackage::where('mikrotik_radius_group_name', $radUserGroup->groupname ?? 'FREE-PACKAGE')->value('id');
        $password = $radCheck->value;
        $division_id = $supportCenter->division_id;
        $district_id = $supportCenter->district_id;
        $upazila_id = $supportCenter->upazila_id;
        $union_id = $supportCenter->union_id;
        $village_id = $supportCenter->village_id;
        $latitude = $supportCenter->latitude;
        $longitude = $supportCenter->longitude;

        $user = User::where('auth_id', $request->auth_id)->first();
        if(!$user){
            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($request->auth_id);

            // Data pushing to User table
            $user = new User();
            $user->auth_id = $auth_id;
            $user->status = 'active';
            $user->base_role = 'user';
            $user->panel_access = 0;
            $user->password = Hash::make($password);
            $user->text_password = $password;
            $user->save();

            $newUser = User::where('auth_id', $auth_id)->first();

            // Data pushing to User Profile Table
            $userProfile = new UserProfile();
            $userProfile->uid = $newUser->id;
            $userProfile->full_name = $userInfo->firstname;
            $userProfile->wallet_amount = 0.00;
            $userProfile->mobile_number = $request->auth_id;
            $userProfile->whatsapp_number = $request->auth_id;
            $userProfile->email = $userInfo->email;
            $userProfile->profession = 'no data';
            // $userProfile->nid = ;
            // $userProfile->gender = ;
            $userProfile->division_id = $division_id;
            $userProfile->district_id = $district_id;
            $userProfile->upazila_id = $upazila_id;
            $userProfile->union_id = $union_id;
            $userProfile->village_id = $village_id;
            // $userProfile->house_no = ;
            $userProfile->address = $userInfo->address;
            $userProfile->latitude = $latitude;
            $userProfile->longitude = $longitude;
            $userProfile->address_direction = $userInfo->address;
            $userProfile->device_info = 'Hotspot Billing Portal V2';
            $userProfile->save();

            // Data pushing to Internet Users Table
            $internetUser = new InternetUsers();
            $internetUser->uid = $newUser->id;
            $internetUser->zone_id = $zone_id;
            $internetUser->added_by = $supportCenter->uid;
            $internetUser->package_id = $package_id;
            $internetUser->package_type = 'wifi';
            // $internetUser->package_expire_date = ;
            $internetUser->latitude = $latitude;
            $internetUser->longitude = $longitude;
            $internetUser->password = $password;
            // $internetUser->password_broadband = ;
            // $internetUser->user_type = ;
            $internetUser->billing_address = $userInfo->address;
            // $internetUser->serial_number = ;
            // $internetUser->broadband_pop_id = ;
            // $internetUser->connection_media = ;
            // $internetUser->installation_charge = ;
            $internetUser->connection_status = 'inactive';
            $internetUser->save();
        }else{
            $user = User::find($user->id);
            $user->password = Hash::make($password);
            $user->text_password = $password;
            $user->update();

            $internetUser = InternetUsers::where('uid', $user->id)->first();
            $internetUser->password = $password;
            $internetUser->update();
        }

        if($user->status === 'block'){
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
        return ResponseWrapper::End($returned_data);
    }

    // create hotspot user ------
    public function createHotspotInternetUserShadhinClient(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $editorInfo = NetworkSupportCenter::where('zone_name',$request->get('branch'))->first();
        if(empty($editorInfo)){
            $editorInfo = CorporateClient::where('zone_name',$request->get('branch'))->first();            
        }
        $profile = strtoupper($request->get('branch')) . '-FREE';

        // Validate the request input
        $validate = $request->validate([
            'full_name' => 'required',
            'mobile_number' => 'required',
        ]);

        if(!$validate){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went, Validation Error!";
            return ResponseWrapper::End($returned_data);
        }

        if($request->get('mobile_number') !== null){
            $mobileNumber = $request->get('mobile_number');
            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $auth_id)->exists()){
                $returned_data['status'] = 'error';
                $returned_data['message'] = "This mobile number is already in use.";
                return ResponseWrapper::End($returned_data);
            } else {
                //create new user
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                // $editor_profile = NetworkSupportCenter::where('uid',$editor_id)->first();

                // data for user_profile table ----------
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $request->get('full_name');
                $userProfile->mobile_number = $request->get('mobile_number');
                $userProfile->email = 'noemail@shadhinwifi.com';
                $userProfile->division_id = $editorInfo->division_id;
                $userProfile->district_id = $editorInfo->district_id;
                $userProfile->upazila_id = $editorInfo->upazila_id;
                $userProfile->union_id = $editorInfo->union_id;
                $userProfile->village_id = $editorInfo->village_id;
                $userProfile->house_no = $editorInfo->house_no;
                $userProfile->address = $editorInfo->address;
                $userProfile->address_direction = $editorInfo->address;
                $userProfile->latitude = $editorInfo->latitude ?? "0.00000";
                $userProfile->longitude = $editorInfo->longitude ?? "0.00000";
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                // data for internet user table ---------
                $internetUser = new InternetUsers();
                $internetUser->uid = $uid;
                $internetUser->package_id = 1; // package_uid
                $internetUser->zone_id = $editorInfo->zone_id;
                $internetUser->added_by = $editorInfo->uid;
                $internetUser->package_type = 'wifi';
                $internetUser->latitude = $editorInfo->latitude ?? "0.00000";
                $internetUser->longitude = $editorInfo->longitude ?? "0.00000";
                $internetUser->password = $password;
                $internetUser->user_type = 'wifi';
                $internetUser->billing_address = $editorInfo->address;
                $internetUser->save();

                //$ip_address = NetworkSupportCenter::where('uid',$editor_id)->value('zone_ip');

                // data for user info table radius database ----------------
                $user_info = new WifiDbUserInfo();
                $user_info->username = $request->get('mobile_number');
                $user_info->firstname = $request->get('full_name');
                $user_info->email = 'noemail@shadhinwifi.com';
                $user_info->ipaddress = $editorInfo->zone_ip;
                $user_info->mobilephone = $request->get('mobile_number');
                $user_info->address = $editorInfo->address;
                $user_info->branch = $request->get('branch');
                $user_info->thana = GeoUpazila::where('id',$editorInfo->upazila_id)->value('en_name');
                $user_info->district = GeoDistrict::where('id',$editorInfo->district_id)->value('en_name');
                $user_info->city = GeoDistrict::where('id',$editorInfo->district_id)->value('en_name');
                $user_info->state = GeoDivision::where('id',$editorInfo->division_id)->value('en_name');
                $user_info->country = 'Bangladesh';
                $user_info->country = '1340';
                $user_info->client_id = $editorInfo->uid;
                $user_info->creationdate = Carbon::now();
                $user_info->creationby = $editorInfo->uid;
                $user_info->updatedate = Carbon::now();
                $user_info->updateby = $editorInfo->uid;
                $user_info->save();

                // data for rad acct table radius database ----------------
                $radcheck = new WifiDbRadCheck();
                $radcheck->username = $request->get('mobile_number');
                $radcheck->attribute = "Cleartext-Password";
                $radcheck->op = ":=";
                $radcheck->value = $password;
                $radcheck->branch = $request->get('branch');
                $radcheck->updatetime = Carbon::now();
                $radcheck->save();

                // data for Rad User Group table radius database ----------------
                $radUserGroup = new WifiDbRadUserGroup();
                $radUserGroup->username = $request->get('mobile_number');
                $radUserGroup->groupname = $profile;
                $radUserGroup->priority = "0";
                $radUserGroup->save();

                // data for Rad User Group table radius database ----------------
                $radUserGroup = new WifiDbRadReply();
                $radUserGroup->username = $request->get('mobile_number');
                $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
                $radUserGroup->op = ":=";
                $radUserGroup->value = Carbon::now()->addMinutes(30);
                $radUserGroup->save();

                // For response ------
                $internet_table = InternetUsers::where('uid',$uid)->first();
                $user_pro_table = UserProfile::where('uid',$uid)->first();

                $mobile = $request->get('mobile_number');
                $smsText = "আপনার স্বাধীন ওয়াইফাই ইউজার আইডি: ".$mobile." এবং পাসওয়ার্ড: " . $password;
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);

                // Final Results ---------
                $returned_data['status'] = 'success';
                $returned_data['message'] = 'You added '.$request->get('mobile_number').' as a free hotspot internet user for 30 Minutes!';
                $returned_data['results'] = [
                    'uid' => $uid,
                    'full_name' => $request->get('full_name'),
                    'text_password' => User::where('id',$uid)->value('text_password'),
                    'package_id' => 1,
                    'package_name' => $profile,
                    'added_by' => $internet_table->added_by,
                    'owner_type' => User::where('id',$editorInfo->uid)->value('base_role'),
                    'mobile_number' => $request->get('mobile_number'),
                    'whatsapp_number' => $user_pro_table->whatsapp_number,
                    'email' => $user_pro_table->email,
                    'nid' => $user_pro_table->nid,
                    'division_id' => $user_pro_table->division_id,
                    'district_id' => $user_pro_table->district_id,
                    'upazila_id' => $user_pro_table->upazila_id,
                    'union_id' => $user_pro_table->union_id,
                    'village_id' => $user_pro_table->village_id,
                    'house_no' => $user_pro_table->house_no,
                    'address' => $user_pro_table->address,
                    'latitude' => $user_pro_table->latitude,
                    'longitude' => $internet_table->longitude,
                    'created_at' => $user_pro_table->created_at,
                    'address_direction' => $user_pro_table->address_direction,
                    'UserType' => $internet_table->user_type,
                ];
                return ResponseWrapper::End($returned_data);
            }
        }
    }
}
