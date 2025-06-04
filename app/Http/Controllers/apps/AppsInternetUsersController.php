<?php

namespace App\Http\Controllers\apps;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\panel\location\GeoController;
use App\Http\Controllers\radius\RadiusServerController;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\Payment;
use App\Models\User;
use App\Models\WifiDbRadCheck;
use App\Models\UserProfile;
use App\Models\CorporateAgent;
use App\Models\AffiliateHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AppsInternetUsersController extends Controller
{

    public function createInternetUser($uid, $internetPassword, $package_type, $package_id, $zone_id, $agentId, $previous_conn_type = null, $provider_names = null, $latitude = null, $longitude = null) {

        // Create internet user
        $newInternetUser = new InternetUsers();
        $newInternetUser->uid = $uid;
        $newInternetUser->zone_id = $zone_id;
        $newInternetUser->added_by = $agentId;
        $newInternetUser->package_id = $package_id;
        $newInternetUser->previous_conn_type = $previous_conn_type;
        $newInternetUser->provider_names = $provider_names;
        $newInternetUser->package_type = $package_type;
        $newInternetUser->latitude = $latitude;
        $newInternetUser->longitude = $longitude;
        $newInternetUser->password = null;
        $newInternetUser->password_broadband = null;
        if($package_type === 'wifi'){
            $newInternetUser->password = $internetPassword;
        } else {
            $newInternetUser->password_broadband = $internetPassword;
        }
        // should be update after mikrotik tasks
        $newInternetUser->connection_status = 'pending';
        $newInternetUser->package_expire_date = null;
        //------------------------------------------------------
        //Log::info($newInternetUser);
        $newInternetUser->save();
        return $newInternetUser;
    }


    public function purchaseInternetPackage(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        // Log::info($request->all());
        $auth_id = $request->get('auth_id');
        $uid = User::where('auth_id', $auth_id)->value('id');
        // Log::info($uid);
        $package_id = $request->get('package_id');
        $zone_id = null;

        if($request->get('package_type') === 'broadband'){
            $internetPassword = (new \App\Classes\CustomHelpers)->generate_new_password(6);
        } else {
            $internetPassword = (new \App\Classes\CustomHelpers)->generate_new_password();
        }
        
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');
        if($latitude === null){

            $user = UserProfile::where('uid', '=', $uid)->first();
            $latitude = $user['latitude'];
            $longitude = $user['longitude'];
            //Log:info($uid);
            // Log::info($user);
        }
        

        $internetUserExist = InternetUsers::where('uid', '=', $uid)->exists();

        if($internetUserExist){
            $returned_data['error_type'] = 'already_exist';
        }
        else {

            // get zone_id from user latitude,longitude
            if(!empty($latitude) && !empty($longitude)){
                $zoneInfo = (new \App\Http\Controllers\panel\location\GeoController)->getZoneInfo($latitude, $longitude);
                if($zoneInfo !== null && $zoneInfo['zone_id'] !== null){
                    $zone_id = $zoneInfo['zone_id'];
                }
            }

            $agentId = null;
            if(!empty($request->get('referral_auth_id'))){
                $agentData = User::where('auth_id', '=', $request->get('referral_auth_id'))->first();                
                
                if(!empty($agentData)){                    
                    $agentType = $agentData['base_role'];
                    $agentId = $agentData['id'];
                    $internetPackage = InternetPackage::where('id', '=', $package_id)->first();
                    $commission_type = $internetPackage['commission_type'];
                    $package_price = $internetPackage['price'];
                    // Log::info($agentData);

                    $commission_rate = 0;
                    
                    if($agentType === 'sales_point'){
                        $commission_rate = $internetPackage['sales_point_commission'];
                        // Log::info($internetPackage['sales_point_commission']);
                    } else if($agentType === 'sales_agent'){
                        $commission_rate = $internetPackage['sales_agent_commission'];
                    }else if($agentType === 'agent'){
                        $commission_rate = CorporateAgent::where('uid', $agentId)->value('commission');
                        //$commission_rate = $commissionRate;
                    }
                    //commission rate to amount
                    $commission_amount = 0;
                    if($commission_type === 'percentage' && $commission_rate > 0){
                        $commission_amount = ($package_price * $commission_rate) / 100;
                    } else if($commission_rate > 0) {
                        $commission_amount = $commission_rate;
                    }
                    //Log::info($internetPackage);
                    //Log::info($commission_rate);
                    //Log::info($commission_amount);
                    if($commission_amount > 0){
                        // Log::info($commission_amount);
                        // (new \App\Classes\CustomHelpers)->create_new_affiliate_history($agentData['id'], 'internet_package', $uid, $commission_amount);
                        $query = new AffiliateHistory();
                        $query->affiliator_uid = $agentData['id'];
                        $query->product_type = 'internet_package';
                        $query->product_id = $uid;
                        $query->commission_amount = $commission_amount;
                        $query->save();
                    }
                }
            }
            $newUser = self::createInternetUser($uid, $internetPassword, $request->get('package_type'), $package_id, $zone_id, $agentId, null, null, $latitude, $longitude);
            $returned_data['results'] = $newUser;


            // check to radius if already previous value exist
            if($request->get('package_type') === 'wifi'){
                $returned_data['radius_mikrotik_db_status'] = (new \App\Http\Controllers\radius\RadiusServerController)->create_radius_db_information($auth_id, $package_id);
                if(!$returned_data['radius_mikrotik_db_status']){
                    Log::debug('user_radius_insert_error2', [$auth_id]);
                }
            } else if($request->get('package_type') === 'broadband') {
                (new \App\Classes\CustomHelpers)->syncOldBroadbandUserWithNewErp($auth_id, $uid, $zone_id);
            }
        }
        return ResponseWrapper::End($returned_data);
    }

    public function getUserInternetPassword(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));
        $returned_data['results'] = InternetUsers::where('uid', '=', $uid)->value('password');
        return ResponseWrapper::End($returned_data);
    }

    public function userInternetPartnerId(Request $request, $user_auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($user_auth_id);
        $query = InternetUsers::query();
        $query->leftJoin('network_support_centers', 'network_support_centers.id', '=', 'internet_users.zone_id');
        $query->where('internet_users.uid', '=', $uid);
        $returned_data['results'] = $query->first(['internet_users.zone_id', 'network_support_centers.zone_name']);
        return ResponseWrapper::End($returned_data);
    }

    public function userActiveInternetPackage(Request $request, $user_auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internetUserUid = (new \App\Classes\CustomHelpers)->getUidByAuthId($user_auth_id);
        $internetUserExist = InternetUsers::where('uid', '=', $internetUserUid)->whereIn('connection_status', ['active','pending'])->exists();//->whereNotNull('zone_id')

        $secret = BroadbandDbSecret::where('username', $user_auth_id)->first();
        if($secret){
            $subscriber_info = BroadbandDbSubscriberInfo::where('numAsId', $user_auth_id)->first();
            $support_center = NetworkSupportCenter::where('zone_name', $secret->zone)->first();
            $package_id = InternetPackage::where('mikrotik_radius_group_name', $subscriber_info->packageId)->value('id');
        }
        
        // Log::info($internetUserExist);
        // Log::info($internetUserUid);
        if(!empty($internetUserExist)) {
            $is_paid = Payment::where('uid', $internetUserUid)->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->exists();
            //Log::info($internetUserExist);
            if($is_paid){
                $returned_data['error_type'] = 'Bill paid!';
            }else{
                $userData = InternetUsers::where('uid', '=', $internetUserUid)->first(['zone_id','package_id','package_type','package_expire_date','connection_status','latitude','longitude'])->toArray();
                //Log::info($internetUserUid);
                $packageData = InternetPackage::where('id', '=', $userData['package_id'])->first(['id', 'en_title','bn_title','type','price'])->toArray();

                if($userData['package_type'] === 'broadband' && $userData['zone_id'] === null){
                    $zone_id = '';
                    if(!empty($userData['latitude']) && !empty($userData['longitude'])){
                        $zoneInfo = (new \App\Http\Controllers\panel\location\GeoController)->getZoneInfo($userData['latitude'], $userData['longitude']);
                        if($zoneInfo !== null && $zoneInfo['zone_id'] !== null){
                            $zone_id = $zoneInfo['zone_id'];
                        }
                    }
                    (new \App\Classes\CustomHelpers)->syncOldBroadbandUserWithNewErp($user_auth_id, $internetUserUid, $zone_id);
                }


                $userData['is_internet_expired'] = false;
                $package_expire_date = $userData['package_expire_date'];
                $radExpireDateTime = Carbon::parse($package_expire_date);
                if(Carbon::now() > $radExpireDateTime){
                    $userData['is_internet_expired'] = true;
                }
                //Log::info($userData);
                $returned_data['results'] = array_merge($userData, $packageData) ;
            }
            
        } elseif(empty($internetUserExist) && !empty($secret)){
            // Data pushing to User table
            $user = User::updateOrCreate(
                ['auth_id' => $user_auth_id],
                [
                    'status' => 'active',
                    'base_role' => 'user',
                    'panel_access' => 0,
                    'password' => Hash::make($secret->password),
                    'text_password' => $secret->password
                ]
            );

            $newUser = User::where('auth_id', $user_auth_id)->first();

            // Data pushing to User Profile Table
            $userProfile = UserProfile::updateOrCreate(
                ['uid' => $newUser->id],
                [
                    'full_name' => $subscriber_info->customerName,
                    'wallet_amount' => 0.00,
                    'mobile_number' => $request->auth_id,
                    'whatsapp_number' => $request->auth_id,
                    'email' => $subscriber_info->email,
                    'profession' => 'no data',
                    'nid' => $subscriber_info->nid,
                    'gender' => $subscriber_info->gender,
                    'division_id' => $support_center->division_id,
                    'district_id' => $support_center->district_id,
                    'upazila_id' => $support_center->upazila_id,
                    'union_id' => $support_center->union_id,
                    'village_id' => $support_center->village_id,
                    'house_no' => $subscriber_info->home,
                    'address' => $subscriber_info->billingAddress,
                    'latitude' => $support_center->latitude,
                    'longitude' => $support_center->longitude,
                    'address_direction' => $subscriber_info->billingAddress,
                    'device_info' => 'Shadhin Wifi Apps'
                ]
            );

            // Data pushing to Internet Users Table
            $internetUser = InternetUsers::updateOrCreate(
                ['uid' => $newUser->id],
                [
                    'zone_id' => $support_center->zone_id,
                    'added_by' => $support_center->uid,
                    'package_id' => $package_id,
                    'package_type' => 'broadband',
                    'package_expire_date' => $secret->dateOf_Inactive,
                    'latitude' => $support_center->latitude,
                    'longitude' => $support_center->longitude,
                    'password' => $secret->password,
                    'password_broadband' => $secret->password,
                    'user_type' => $subscriber_info->UserType,
                    'billing_address' => $subscriber_info->billingAddress,
                    'serial_number' => $request->auth_id,
                    'broadband_pop_id' => $subscriber_info->popId,
                    'connection_media' => $subscriber_info->connectionMedia,
                    'installation_charge' => 0,
                    'connection_status' => 'active'
                ]
            );

            $userData = InternetUsers::where('uid', '=', $newUser->id)->first(['zone_id','package_id','package_type','package_expire_date','connection_status','latitude','longitude'])->toArray();
            $packageData = InternetPackage::where('id', '=', $userData['package_id'])->first(['id', 'en_title','bn_title','type','price'])->toArray();

            $userData['is_internet_expired'] = false;
            $package_expire_date = $userData['package_expire_date'];
            $radExpireDateTime = Carbon::parse($package_expire_date);
            if(Carbon::now() > $radExpireDateTime){
                $userData['is_internet_expired'] = true;
            }

            $returned_data['results'] = array_merge($userData, $packageData) ;
        } else {
            $returned_data['error_type'] = 'user_not_found';
        }

        //Log::info($returned_data);

        return ResponseWrapper::End($returned_data);
    }
}
