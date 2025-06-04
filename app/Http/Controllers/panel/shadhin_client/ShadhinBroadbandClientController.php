<?php

namespace App\Http\Controllers\panel\shadhin_client;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MessageAndNotificationController;
use App\Models\AffiliateHistory;
use App\Imports\ISPClientBroadbandUserBulkRegisterImport;
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
use App\Models\GeoVillage;
use App\Models\InternetPackage;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\MessageAndNotification;
use App\Models\NetworkSupportCenter;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ShadhinBroadbandClientController extends Controller
{
    // Billing portal login
    public function user_login_broadband_billing(Request $request){
        $returned_data = ResponseWrapper::Start();
        $request->validate([
            'auth_id' => 'required',
            'password' => 'required',
        ]);

        $secret = BroadbandDbSecret::where('username', $request->auth_id)->first();
        if(!$secret){
            Log::info('Id-'.$request->auth_id.'Not found in secret table');
            $returned_data['error_type']= 'secret_not_found';
            $returned_data['message']= 'You are not allowed to login here.';
            return ResponseWrapper::End($returned_data);
        }

        $subsInfo = BroadbandDbSubscriberInfo::where('numAsId',$request->auth_id)->first();
        if(!$subsInfo){
            Log::info('Id-'.$request->auth_id.'Not found in subsInfo table');
            $returned_data['error_type']= 'subsInfo_not_found';
            $returned_data['message']= 'You are not allowed to login here.';
            return ResponseWrapper::End($returned_data);
        }
        
        $supportCenter = NetworkSupportCenter::where('zone_name',$secret->zone)->first(); // ask mohib bhai what should if not found
        if(!$supportCenter){
            Log::info($secret->zone.' support center not activated yet.');
            $returned_data['error_type']= 'supportCenter_not_found';
            $returned_data['message']= $secret->zone.' support center not activated yet.';
            return ResponseWrapper::End($returned_data);
        }
        // Need zone_id, division_id, district_id, upazila_id, union_id, package_id
        $zone_id = $supportCenter->zone_id;

        if($subsInfo->packageId === '5M-Home'){
            $package_id = '508';
        }else{
            $package_id = InternetPackage::where('mikrotik_radius_group_name', $secret->profile)->value('id');
        }
        $password = $secret->password;
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
            $userProfile->full_name = $subsInfo->customerName;
            $userProfile->wallet_amount = 0.00;
            $userProfile->mobile_number = $request->auth_id;
            $userProfile->whatsapp_number = $request->auth_id;
            $userProfile->email = $subsInfo->email;
            $userProfile->profession = 'no data';
            $userProfile->nid = $subsInfo->nid;
            $userProfile->gender = $subsInfo->gender;
            $userProfile->division_id = $division_id;
            $userProfile->district_id = $district_id;
            $userProfile->upazila_id = $upazila_id;
            $userProfile->union_id = $union_id;
            $userProfile->village_id = $village_id;
            $userProfile->house_no = $subsInfo->home;
            $userProfile->address = $subsInfo->billingAddress;
            $userProfile->latitude = $latitude; // nai
            $userProfile->longitude = $longitude; // nai
            $userProfile->address_direction = $subsInfo->billingAddress; // nai
            $userProfile->device_info = 'Broadband Billing Portal V2';
            $userProfile->save();

            // Data pushing to Internet Users Table
            $internetUser = new InternetUsers();
            $internetUser->uid = $newUser->id;
            $internetUser->zone_id = $zone_id;
            $internetUser->added_by = $supportCenter->uid;
            $internetUser->package_id = $package_id;
            $internetUser->package_type = 'broadband';
            $internetUser->package_expire_date = $secret->dateOf_Inactive;
            $internetUser->latitude = $latitude;
            $internetUser->longitude = $longitude;
            $internetUser->password = $password;
            $internetUser->password_broadband = $password;
            $internetUser->user_type = $subsInfo->UserType;
            $internetUser->billing_address = $subsInfo->billingAddress;
            $internetUser->serial_number = $request->auth_id;
            $internetUser->broadband_pop_id = $subsInfo->popId;
            $internetUser->connection_media = $subsInfo->connectionMedia;
            $internetUser->installation_charge = 0;
            $internetUser->connection_status = 'inactive';
            $internetUser->save();
        }else{
            $user = User::find($user->id);
            $user->password = Hash::make($password);
            $user->text_password = $password;
            $user->update();

            InternetUsers::updateOrCreate(
                ['uid' => $user->id],
                [
                    'package_id' => $package_id,
                    'package_type' => 'broadband',
                    'password' => $password,
                    'password_broadband' => $password,
                    'zone_id' => $zone_id,
                    'added_by' => $supportCenter->uid,
                    'package_expire_date' => $secret->dateOf_Inactive,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'user_type' => $subsInfo->UserType,
                    'billing_address' => $subsInfo->billingAddress,
                    'serial_number' => $request->auth_id,
                    'broadband_pop_id' => $subsInfo->popId,
                    'connection_media' => $subsInfo->connectionMedia,
                    'installation_charge' => 0,
                    'connection_status' => 'inactive',
                ]
            );
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

    // create broadband user ------
    public function createBroadbandInternetUserShadhinClient(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Validate the request input
        $request->validate([
            'client_id' => 'required',
            'full_name' => 'required',
            'mobile_number' => 'required',
            'nid' => 'required',
            'gender' => 'required',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'house' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'package' => 'required',
            'pop' => 'required',
            'ins_cost' => 'required',
            'connection_media' => 'required',
            'user_type' => 'required',
        ]);

        $mobileNumber = $request->get('mobile_number');
        $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);

        $user = User::where('auth_id', $auth_id)->first();
        $paymentCheck = $user ? Payment::where('uid', $user->id)->exists() : false;

        if ($paymentCheck) {
            return ResponseWrapper::End([
                'status' => 'error',
                'message' => 'This mobile number is already in use.'
            ]);
        }
        $password = '';
        if (!$user) {
            // Create new user and profile
            $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user', 'broadband');
            $user = User::find($userData['user']['id']);
            $password = $userData['password'];
        }

        // Handle user profile creation or update
        $userProfile = UserProfile::updateOrCreate(
            ['uid' => $user->id],
            $request->only([
                'full_name', 'mobile_number', 'whatsapp_number', 'email', 'profession',
                'nid', 'gender', 'division', 'district', 'upazila', 'union', 'village',
                'house', 'address', 'address_direction', 'latitude', 'longitude'
            ]) + ['device_info' => json_encode(["brand" => "website"])]
        );

        $supportCenter = NetworkSupportCenter::where('uid', $request->get('client_id'))->first();

        // Handle Internet user data
        InternetUsers::updateOrCreate(
            ['uid' => $user->id],
            [
                'zone_id' => $supportCenter->zone_id ?? null,
                'added_by' => $request->get('client_id'),
                'package_id' => $request->get('package'),
                'package_type' => 'broadband',
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'password' => $password ?? $user->password,
                'user_type' => $request->get('user_type'),
                'billing_address' => $request->get('address'),
                'broadband_pop_id' => $request->get('pop'),
                'connection_media' => $request->get('connection_media'),
                'installation_charge' => $request->get('ins_cost'),
                'connection_status' => 'pending'
            ]
        );

        // Fetch package data
        $packages = InternetPackage::find($request->get('package'));

        // Handle subscriber info
        BroadbandDbSubscriberInfo::updateOrCreate(
            ['numAsId' => $auth_id],
            [
                'serial' => $user->id,
                'popId' => $request->get('pop'),
                'date' => now()->toDateString(),
                'zone_name' => $supportCenter->zone_name ?? null,
                'packageId' => $packages->mikrotik_radius_group_name ?? null,
                'customerName' => $request->get('full_name'),
                'gender' => $request->get('gender'),
                'instcost' => $request->get('ins_cost'),
                'home' => $request->get('house'),
                'village' => $request->get('village'),
                'police_station' => GeoUpazila::find($request->get('upazila'))->en_name ?? null,
                'district' => GeoDistrict::find($request->get('district'))->en_name ?? null,
                'division' => GeoDivision::find($request->get('division'))->en_name ?? null,
                'billingAddress' => $request->get('address'),
                'nid' => $request->get('nid'),
                'numOne' => $request->get('mobile_number'),
                'email' => $request->get('email'),
                'UserType' => $request->get('user_type'),
                'connectionMedia' => $request->get('connection_media'),
                'acativation_date' => now()
            ]
        );

        // Calculate package price for remaining days of the month
        $daysLeft = now()->daysInMonth - now()->day + 1;
        $finalPackagePrice = (int)(($packages->price / now()->daysInMonth) * $daysLeft);

        $returned_data['status'] = 'success';
        $returned_data['results'] = ['uid' => $user->id];

        return ResponseWrapper::End($returned_data);
    }

    public function getPaymentData($internet_user_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $userData = User::where('id', $internet_user_id)->first();
        $userProfile = UserProfile::where('uid', $internet_user_id)->first();
        $internetUser = InternetUsers::where('uid', $internet_user_id)->where('package_type','broadband')->first();
        $package = InternetPackage::where('id', $internetUser->package_id)->first();

        $currentDayOfMonth = date('j');
        $totalDaysInMonth = date('t');
        $daysleft = ($totalDaysInMonth-$currentDayOfMonth)+1;

        // Calculating package price
        $packageFinalFloat = $daysleft*($package->price / $totalDaysInMonth);
        $finalPackagePrice = (int)$packageFinalFloat;

        $returned_data['status'] = 'success';
        $returned_data['results'] = [
            'uid' => $internet_user_id,
            'full_name' => $userProfile->full_name,
            'mobile_number' => $userData->auth_id,
            'package' => $package->mikrotik_radius_group_name,
            'package_id' => $internetUser->package_id,
            'days_left' => $daysleft,
            'final_price' => $finalPackagePrice
        ];

        return ResponseWrapper::End($returned_data);
    }
}
