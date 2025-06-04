<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Http\Controllers\Controller;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PanelInternetUserController extends Controller
{
    public function getInternetUserCount(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = InternetUsers::query();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'internet_users.uid');

        // Initialize counters
        $total = 0;
        $active = 0;
        $pending = 0;

        // Check for each geographic level
        if (!empty($request->get('village')) && $request->get('village') != 'undefined') {
            $query->where('user_profiles.village_id', $request->get('village'));
        } elseif (!empty($request->get('union')) && $request->get('union') != 'undefined') {
            $query->where('user_profiles.union_id', $request->get('union'));
        } elseif (!empty($request->get('upazila')) && $request->get('upazila') != 'undefined') {
            $query->where('user_profiles.upazila_id', $request->get('upazila'));
        } elseif (!empty($request->get('district')) && $request->get('district') != 'undefined') {
            $query->where('user_profiles.district_id', $request->get('district'));
        } elseif (!empty($request->get('division')) && $request->get('division') != 'undefined') {
            $query->where('user_profiles.division_id', $request->get('division'));
        }

        $total = $query->count();

        // Clone the query to count active and pending separately
        $activeQuery = clone $query;
        $pendingQuery = clone $query;

        $active = $activeQuery->where('internet_users.connection_status', 'active')->count();
        $pending = $pendingQuery->where('internet_users.connection_status', 'pending')->count();

        $returned_data['results']['total'] = $total;
        $returned_data['results']['active'] = $active;
        $returned_data['results']['pending'] = $pending;

        return ResponseWrapper::End($returned_data);
    }

    public function getAllLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 100;

        $query = InternetUsers::query();
        $total = $query->count();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'internet_users.uid');

        if (!empty($request->get('division')) && $request->get('division') != 'undefined') {
            $query->where('user_profiles.division_id', $request->get('division'));
        }
        if (!empty($request->get('district')) && $request->get('district') != 'undefined') {
            $query->where('user_profiles.district_id', $request->get('district'));
        }
        if (!empty($request->get('upazila')) && $request->get('upazila') != 'undefined') {
            $query->where('user_profiles.upazila_id', $request->get('upazila'));
        }
        if (!empty($request->get('union')) && $request->get('union') != 'undefined') {
            $query->where('user_profiles.union_id', $request->get('union'));
        }
        if (!empty($request->get('village')) && $request->get('village') != 'undefined') {
            $query->where('user_profiles.village_id', $request->get('village'));
        }
        $query->whereNotNull('internet_users.latitude');
        $query->whereNotNull('internet_users.longitude');

        if(!empty($request->get('status'))){
            if($request->get('status') !== 'all'){
                $query->where('connection_status',$request->get('status'));
            }
        }

        if(!empty($request->get('skip')) && $request->get('skip') !== 'none'){
            $query->skip($totalSkip)->take($totalLimit);
        }
        $query->skip($totalSkip)->take($totalLimit);

        $returned_data['results']['list'] = $query->get(['internet_users.id', 'internet_users.latitude', 'internet_users.longitude', 'internet_users.connection_status as status','user_profiles.full_name', 'user_profiles.mobile_number','user_profiles.email', DB::raw("'internet-users' as url_type")]);
        $returned_data['results']['total'] = $total;
        return ResponseWrapper::End($returned_data);
    }

    public function getInternetUserSummary(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $query = InternetUsers::all()->groupBy('connection_status')->map(function ($row) {
            return $row->count();
        });

        $countArray["active"] = !empty($query['active']) ? $query['active'] : 0;
        $countArray["pending"] = !empty($query['pending']) ? $query['pending'] : 0;
        $countArray["total"] = $countArray['active'] + $countArray['pending'];

        $returned_data['results'] = $countArray;


        return ResponseWrapper::End($returned_data);

    }

    public function getInternetUserList(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keywords')));
        $status = $request->get('status');
        $c_status = $request->get('communication_status');
        $package_type = $request->get('package_type');
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $limit = $request->get('limit') !== null ? $request->get('limit') : 50;

        $query = InternetUsers::query();
        $query->leftJoin('users as u', 'u.id', '=', 'internet_users.uid');
        $query->leftJoin('communications', function ($join) {
            $join->on('communications.customer_uid', '=', 'internet_users.uid')
                ->where('communications.type', '=', 'internet_user')
                ->whereRaw('communications.id = (SELECT id FROM communications WHERE customer_uid = internet_users.uid AND type = "internet_user" ORDER BY created_at DESC LIMIT 1)');
        });

        if($keywords) {
            $query->where(function($qr) use ($keywords){
                $qr->where('u.auth_id', '=', $keywords)
                ->orWhere('internet_users.zone_id', '=', $keywords);
            });
        }

        if($status !== 'all' && $status !== null){
            $query->where('internet_users.connection_status', '=', $status);
        }

        if($package_type !== 'all' && $package_type !== null){
            $query->where('internet_users.package_type', '=', $package_type);
        }

        if ($c_status !== 'all' && $c_status !== null) {
            $query->whereExists(function ($query) use ($c_status) {
                $query->select(DB::raw(1))
                      ->from('communications as com2')
                      ->whereColumn('com2.customer_uid', 'internet_users.uid')
                      ->where('com2.type', '=', 'internet_user')
                      ->where('com2.status', '=', $c_status)
                      ->whereRaw('com2.customer_uid = internet_users.uid AND com2.created_at = (SELECT MAX(created_at) FROM communications WHERE customer_uid = com2.customer_uid)');
            });
        }

        $query->orderBy('internet_users.created_at', 'DESC');
        $query->skip($totalSkip)->take($limit);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'u.auth_id as mobile_number',
            'internet_users.id',
            'internet_users.uid',
            'internet_users.connection_status',
            'communications.status as c_status',
            'internet_users.created_at',
            'internet_users.package_type',
            'internet_users.zone_id'
        ]);
        return ResponseWrapper::End($returned_data);
    }

    public function searchInternetUser(Request $request, $keywords) : JsonResponse {
        $returned_data = ResponseWrapper::Start();


        $query = InternetUsers::query();
        $query->leftJoin('users as u', 'u.id', '=', 'internet_users.uid');
        $query->leftJoin('user_profiles as up', 'up.uid', '=', 'internet_users.uid');

        $keywords = strtolower(trim($keywords));
        $query->where('u.auth_id', 'LIKE', '%' . $keywords . '%');

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'u.id as uid',
            'u.base_role',
            'u.panel_access',
            'up.full_name',
            'u.auth_id as mobile_number',
            'up.email',
            'internet_users.id',
            'internet_users.connection_status',
            'internet_users.package_type',
            'internet_users.created_at',
        ]);

        return ResponseWrapper::End($returned_data);
    }

    public function getInternetUserBasic(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = InternetUsers::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'internet_users.uid');
        $query->where('internet_users.id', '=', $id);
        $internetUser = $query->first(['internet_users.zone_id','internet_users.connection_status', 'user_profiles.full_name']);
        $internetUser['zone_name'] = null;
        if(!empty($internetUser['zone_id'])){
            $internetUser['zone_name'] = NetworkSupportCenter::where('zone_id', '=', $internetUser['zone_id'])->value('zone_name');
        }
        $returned_data['results'] = $internetUser;
        return ResponseWrapper::End($returned_data);
    }

    public function internetUserDetails(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internetUsers = InternetUsers::where('id', '=', $id)->first();
        $internetUsers['package_data'] = InternetPackage::find($internetUsers['package_id']);
        if(!empty($internetUsers['zone_id'])){
            $internetUsers['zone_name'] = NetworkSupportCenter::where('zone_id', $internetUsers['zone_id'])->value('zone_name');
        }


        // get next previous================
        $prevQuery = InternetUsers::query();
        $prevQuery->where('id', '<', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $prevQuery->where('connection_status', '=', $request->get('status'));
        }

        $internetUsers['previous_id'] = $prevQuery->max('id');

        $nxtQuery = InternetUsers::query();
        $nxtQuery->where('id', '>', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $nxtQuery->where('connection_status', '=', $request->get('status'));
        }

        $internetUsers['next_id'] = $nxtQuery->min('id');

        $returned_data['results']['profile'] = UserProfile::where('uid', '=', $internetUsers['uid'])->first();
        $returned_data['results']['data'] = $internetUsers;

        return ResponseWrapper::End($returned_data);
    }

    public function statusUpdate(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $update = InternetUsers::find($id);
        $internetUser = $update;
        $update->zone_id = $request->get('zone_id');
        $update->connection_status = $request->get('connection_status');
        $returned_data['results'] = $update->save();

        if($internetUser->package_type === 'broadband'){
            $status = $request->get('connection_status');
            $user_auth_id = User::where('id', '=', $internetUser->uid)->value('auth_id');
            $networkZone = NetworkSupportCenter::where('zone_id', '=', $internetUser->zone_id)->first();
            $RouterOsAPI = new RouterOsApi();
            if ($RouterOsAPI->connect($networkZone['zone_ip'], $networkZone['zone_username'], $networkZone['zone_password'])){
                if($status === 'active'){
                    $arrID = $RouterOsAPI->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $user_auth_id));
                    $RouterOsAPI->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                } else if($status === 'inactive'){
                    $arrID = $RouterOsAPI->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $user_auth_id));
                    $RouterOsAPI->comm("/ppp/secret/disable", array(".id" => $arrID[0][".id"]));
                }
            }
            $RouterOsAPI->disconnect();
        }
        return ResponseWrapper::End($returned_data);
    }
}
