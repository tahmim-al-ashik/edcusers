<?php

namespace App\Http\Controllers\apps;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\WifiDbRadAcct;
use App\Models\WifiDbRadCheck;
use App\Models\WifiDbRadReply;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\SettingsApp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppDashboardController extends Controller {

    public function getDashboardData(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));
        $auth_id = $request->get('auth_id');

        $user_base_role = User::where('id', '=', $uid)->value('base_role');
        $returned_data['user_base_role'] = $user_base_role;
        $returned_data['base_role_menu_access'] = false;
        $returned_data['base_role_input_access'] = false;
        if($user_base_role === 'support_center'){
            $status = NetworkSupportCenter::where('uid', '=', $uid)->value('status');
            if($status === 'active'){
                $returned_data['base_role_menu_access'] = true;
                $returned_data['base_role_input_access'] = true;
            } else if($status === 'trial') {
                $returned_data['base_role_menu_access'] = true;
            }

        } else if($user_base_role === 'sales_point'){
            $status = SalesPoint::where('uid', '=', $uid)->value('status');
            if($status === 'active'){
                $returned_data['base_role_menu_access'] = true;
                $returned_data['base_role_input_access'] = true;
            } else if($status === 'trial') {
                $returned_data['base_role_menu_access'] = true;
            }
        } else if($user_base_role === 'sales_agent'){
            $status = SalesAgent::where('uid', '=', $uid)->value('status');
            if($status === 'active'){
                $returned_data['base_role_menu_access'] = true;
                $returned_data['base_role_input_access'] = true;
            } else if($status === 'trial') {
                $returned_data['base_role_menu_access'] = true;
            }
        }

        $returned_data['internet_package_data'] = [
            'exist' => false,
            'id' => null,
            'zone_id' => null,
            'price' => 0,
            'user_points' => 0,
            'package_title' => ["en"=>'',"bn"=>''],
            'package_type' => null,
            'connection_status' => 'pending',
            'package_expire_date' => null,
        ];

        $internetUserData = InternetUsers::where('uid', '=', $uid)->first(['zone_id','package_type','connection_status','package_expire_date','package_id','latitude','longitude']);
        if(!empty($internetUserData) && !empty($internetUserData['package_id'])){

            $internetPackageData = InternetPackage::where('id', '=', $internetUserData['package_id'])->first(['en_title','bn_title','price','user_points']);
            $returned_data['internet_package_data'] = [
                "exist"=> true,
                "id"=> $internetUserData['package_id'],
                "zone_id"=> $internetUserData['zone_id'],
                "price"=> $internetPackageData['price'],
                "user_points"=> $internetPackageData['user_points'],
                "package_title" => ["en"=>$internetPackageData['en_title'],"bn"=>$internetPackageData['bn_title']],
                "package_type" => $internetUserData['package_type'],
                "connection_status" => $internetUserData['connection_status'],
                "package_expire_date" => $internetUserData['package_expire_date'],
            ];

            if($internetUserData['package_type'] === 'broadband' && $internetUserData['zone_id'] === null){
                $zone_id = '';
                if(!empty($internetUserData['latitude']) && !empty($internetUserData['longitude'])){
                    $zoneInfo = (new \App\Http\Controllers\panel\location\GeoController)->getZoneInfo($internetUserData['latitude'], $internetUserData['longitude']);
                    if($zoneInfo !== null && $zoneInfo['zone_id'] !== null){
                        $zone_id = $zoneInfo['zone_id'];
                    }
                }
                (new \App\Classes\CustomHelpers)->syncOldBroadbandUserWithNewErp($auth_id, $uid, $zone_id);
            }

        }

        return ResponseWrapper::End($returned_data);
    }
}
