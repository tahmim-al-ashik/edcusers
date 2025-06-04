<?php

namespace App\Http\Controllers\radius;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\BroadbandDbSecret;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\WifiDbRadAcct;
use App\Models\WifiDbRadCheck;
use App\Models\WifiDbRadReply;
use App\Models\WifiDbRadUserGroup;
use App\Models\WifiDbUserInfo;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RadiusServerController extends Controller{

    public function checkOldBroadbandUserExistence($user_auth_id) : bool {
        return BroadbandDbSecret::where('username', '=', $user_auth_id)->exists();
    }


    public function user_group_assign($username, $group_name, $priority = 0) : bool {
        $query = WifiDbRadUserGroup::where('username', $username, '=')->first();
        if(empty($query)){
            $query = new WifiDbRadUserGroup();
            $query->username = $username;
        }
        $query->groupname = $group_name;
        $query->priority = $priority;
        if($query->save()){
            return true;
        }
        return false;
    }

    public function user_info($username) : bool {
        $query = WifiDbUserInfo::where('username', $username, '=')->first();
        if(empty($query)){
            $query = new WifiDbUserInfo();
            $query->username = $username;
            $query->mobilephone = $username;
            $query->ipaddress = '0.0.0.0';
            $query->creationdate = Carbon::now()->toDateTimeString();
        }
        $query->updatedate = Carbon::now()->toDateTimeString();
        if($query->save()){
            return true;
        }
        return false;
    }

    public function create_radius_db_information($auth_id, $package_id){

        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($auth_id);
        $internetUser = InternetUsers::where('uid', '=', $uid)->first();
        $internetPackage = InternetPackage::find($package_id)->toArray();

        $everything_fine = false;
        if($internetUser['zone_id'] === null){
            return false;
        }

        $radCheckExistence = WifiDbRadCheck::where('username', '=', $auth_id)->where('attribute', '=', 'Cleartext-Password')->first();
        $package_expire_date = Carbon::now();
        $branchInfo = (new \App\Classes\CustomHelpers)->getNetworkSupportCenterZoneInfo($internetUser['zone_id']);
        if($radCheckExistence == null){
            if($branchInfo !== null && $branchInfo['zone_name'] !== null){
                $rdrcQuery = new WifiDbRadCheck();
                $rdrcQuery['username'] = $auth_id;
                $rdrcQuery['attribute'] = 'Cleartext-Password';
                $rdrcQuery['op'] = ':=';
                $rdrcQuery['value'] = $internetUser['password'];
                $rdrcQuery['branch'] = $branchInfo['zone_name'];
                $rdrcQuery['updatetime'] = Carbon::now();
                $rdrcQuery->save();

                //duplicate use check
                if($branchInfo['simultaneous_use_disable'] === 0){
                    $SimultaneousUseQuery = new WifiDbRadCheck();
                    $SimultaneousUseQuery['username'] = $auth_id;
                    $SimultaneousUseQuery['attribute'] = 'Simultaneous-Use';
                    $SimultaneousUseQuery['op'] = ':=';
                    $SimultaneousUseQuery['value'] = 1;
                    $SimultaneousUseQuery['branch'] = $branchInfo['zone_name'];
                    $SimultaneousUseQuery['updatetime'] = Carbon::now();
                    $SimultaneousUseQuery->save();
                }


                // set expire date time to radius
                $radreplySessionTime = WifiDbRadReply::where('username', '=', $auth_id)->where('attribute', '=', 'WISPr-Session-Terminate-Time')->first();
                if($radreplySessionTime === null){
                    $rdrcExpirationQuery = new WifiDbRadReply();
                    $rdrcExpirationQuery['username'] = $auth_id;
                    $rdrcExpirationQuery['attribute'] = 'WISPr-Session-Terminate-Time';
                    $rdrcExpirationQuery['op'] = '=';
                    $rdrcExpirationQuery['value'] = $package_expire_date->format('Y-m-d')."T".$package_expire_date->format('H:i:s');
                    $rdrcExpirationQuery->save();
                }

                // user assign to package group
                $internetPartner = NetworkSupportCenter::where('zone_id', '=', $internetUser['zone_id'])->first();
                if($internetPartner['opening_package_id'] !== null){
                    $openingPackage = InternetPackage::where('id', '=', $internetPartner['opening_package_id'])->first();
                    self::user_group_assign($auth_id,  $openingPackage['mikrotik_radius_group_name']);
                } else {
                    self::user_group_assign($auth_id,  $internetPackage['mikrotik_radius_group_name']);
                }
                self::user_info($auth_id);
                $everything_fine = true;
            }
        } else {
            $radCheckExistence->value = $internetUser['password'];
            $radCheckExistence->branch = $branchInfo['zone_name'];
            $radCheckExistence->save();

            $everything_fine = $radCheckExistence['id'] != null;
        }

        return $everything_fine;
    }


    public function activateWifiPackage(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth_id');
        $package_id = $request->get('package_id');
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($auth_id);
        $internetPackage = InternetPackage::find($package_id)->toArray();


        $radCheckExist = WifiDbRadCheck::where('username', '=', $auth_id)->exists();
        if($radCheckExist){
            // update expire date time to db
            $internetUser = InternetUsers::where('uid', '=', $uid)->first();
            $package_expire_date = (new \App\Classes\CustomHelpers)->add_minutes_with_datetime($internetPackage['expiration']);
            $internetUser->connection_status = 'active';
            $internetUser->package_expire_date = $package_expire_date;
            $internetUser->save();


            // update expire date time to radius
            $rdrcExpirationQuery = WifiDbRadReply::where('username', '=', $auth_id)->where('attribute', '=', 'WISPr-Session-Terminate-Time')->first();
            $rdrcExpirationQuery['value'] = $package_expire_date->format('Y-m-d')."T".$package_expire_date->format('H:i:s');
            $rdrcExpirationQuery->save();
            $returned_data['results'] = $rdrcExpirationQuery;
        } else {
            $returned_data['error_type'] = "zone_identify_failed";
        }

        return ResponseWrapper::End($returned_data);
    }


    public function checkWifiConnectionStatus(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = [
            "exist" => false,
            "is_expired" => false,
            "is_connected" => false,
            "is_zone_active" => false,
            "zone_id" => null
        ];

        $auth_id = $request->get('auth_id');
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($auth_id);
        $internetUser = InternetUsers::where('uid', '=', $uid)->first();
        $radCheckData = WifiDbRadCheck::where('username', '=', $auth_id)->where('attribute', '=', 'Cleartext-Password')->first();
        //dd($internetUser['zone_id']);
        if($internetUser['zone_id'] !== null){
            $returned_data['results']['zone_id'] = $internetUser['zone_id'];
        } else if($request->get('user_zone_id') !== null) {
            $internetUser['zone_id'] = $request->get('user_zone_id');
            $internetUser->save();
            $returned_data['results']['zone_id'] = $internetUser['zone_id'];
        }
        if($returned_data['results']['zone_id']){
            $returned_data['results']['is_zone_active'] = NetworkSupportCenter::where('status', '=', 'active')->where('zone_id', '=', $internetUser['zone_id'])->exists();
        }
        //return ResponseWrapper::End($auth_id);
       //dd($returned_data['results']['zone_id']);

        if(!empty($radCheckData)){
            $returned_data['results']['exist'] = true;

            // get expiration date
            $radExpiration = WifiDbRadReply::where('username', '=', $auth_id)->where('attribute', '=', 'WISPr-Session-Terminate-Time')->first();
            if(!empty($radExpiration)){
                $radExpireDateTimeValue = explode("T", $radExpiration['value']);
                $radExpireDateTime = Carbon::parse("{$radExpireDateTimeValue[0]} {$radExpireDateTimeValue[1]}");
                if(Carbon::now() > $radExpireDateTime){
                    $returned_data['results']['is_expired'] = true;
                }
            }
            $returned_data['results']['is_connected'] = WifiDbRadAcct::where('username', '=', $auth_id)->whereNull('acctstoptime')->exists();
        }

        return ResponseWrapper::End($returned_data);

    }
}
