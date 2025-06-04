<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Http\Controllers\Controller;
use App\Classes\ResponseWrapper;
use App\Models\NetworkSupportCenter;
use App\Models\SalesAgent;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\InternetUsers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PanelSalesAgentController extends Controller
{
    public function getSalesAgentUserCount(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = SalesAgent::query();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'sales_agents.uid');

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

        $active = $activeQuery->where('sales_agents.status', 'active')->count();
        $pending = $pendingQuery->where('sales_agents.status', 'pending')->count();

        $returned_data['results']['total'] = $total;
        $returned_data['results']['active'] = $active;
        $returned_data['results']['pending'] = $pending;

        return ResponseWrapper::End($returned_data);
    }

    public function getAllLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 100;

        $query = SalesAgent::query();
        $total = $query->count();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'sales_agents.uid');
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
        $query->whereNotNull('user_profiles.latitude');
        $query->whereNotNull('user_profiles.longitude');
        if(!empty($request->get('status'))){
            if($request->get('status') !== 'all'){
                $query->where('status',$request->get('status'));
            }
        }
        if(!empty($request->get('skip')) && $request->get('skip') !== 'none'){
            $query->skip($totalSkip)->take($totalLimit);
        }
        $query->skip($totalSkip)->take($totalLimit);

        $returned_data['results']['list'] = $query->get(['sales_agents.id', 'user_profiles.latitude', 'user_profiles.longitude', 'sales_agents.status','user_profiles.full_name', 'user_profiles.mobile_number','user_profiles.email', DB::raw("'sales-agents' as url_type")]);
        $returned_data['results']['total'] = $total;
        return ResponseWrapper::End($returned_data);
    }

    public function getSalesAgentSummary(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $activeCount = SalesAgent::join('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid')
        ->leftJoin('communications', 'communications.customer_uid', '=', 'sales_agents.uid')
        ->where('sales_agents.status', 'active')
        ->count();

        $pendingCount = SalesAgent::join('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid')
        ->leftJoin('communications', 'communications.customer_uid', '=', 'sales_agents.uid')
        ->where('sales_agents.status', 'pending')
        ->count();

        $totalCount = $activeCount + $pendingCount;

        $countArray = [
            "active" => $activeCount,
            "pending" => $pendingCount,
            "total" => $totalCount
        ];

        $returned_data['results'] = $countArray;
        return ResponseWrapper::End($returned_data);

    }

    public function getAgentSearchResultList(Request $request, $keyword) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $query = SalesAgent::query();
        $query->leftJoin('users', 'users.id', '=', 'sales_agents.uid');
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid');
        $query->where('users.auth_id', '=', $keyword);
        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(['sales_agents.id','sales_agents.uid','user_profiles.full_name','user_profiles.email','user_profiles.mobile_number','user_profiles.wallet_amount', 'sales_agents.status','sales_agents.created_at']);

        return ResponseWrapper::End($returned_data);

    }

    public function getSalesAgentsList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keyword')));
        $district = $request->get('district');
        $status = $request->get('status');
        $c_status = $request->get('communication_status');

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        $query = SalesAgent::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid');
        $query->leftJoin('communications', function ($join) {
            $join->on('communications.customer_uid', '=', 'sales_agents.uid')
                ->where('communications.type', '=', 'sales_agent')
                ->whereRaw('communications.id = (SELECT id FROM communications WHERE customer_uid = sales_agents.uid AND type = "sales_agent" ORDER BY created_at DESC LIMIT 1)');
        });

        if ($keywords) {
            $query->where('user_profiles.mobile_number', '=', $keywords);
        }

        if ($status !== 'all' && $status !== null) {
            $query->where('sales_agents.status', '=', $status);
        }

        if ($district !== 'all' && $district !== null) {
            $query->where('user_profiles.district_id', '=', $district);
        }

        if ($c_status !== 'all' && $c_status !== null) {
            $query->whereExists(function ($query) use ($c_status) {
                $query->select(DB::raw(1))
                      ->from('communications as com2')
                      ->whereColumn('com2.customer_uid', 'sales_agents.uid')
                      ->where('com2.type', '=', 'sales_agent')
                      ->where('com2.status', '=', $c_status)
                      ->whereRaw('com2.customer_uid = sales_agents.uid AND com2.created_at = (SELECT MAX(created_at) FROM communications WHERE customer_uid = com2.customer_uid)');
            });
        }

        $query->orderBy('sales_agents.created_at', $sortBy);
        $query->skip($totalSkip)->take(25);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'sales_agents.id',
            'sales_agents.uid',
            'user_profiles.full_name',
            'user_profiles.email',
            'user_profiles.mobile_number',
            'user_profiles.wallet_amount',
            'sales_agents.status',
            'communications.status as c_status',
            'sales_agents.created_at'
        ]);

        return ResponseWrapper::End($returned_data);

    }

    public function getSalesAgentBasic(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = SalesAgent::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid');
        $query->where('sales_agents.id', '=', $id);

        $salesAgent = $query->first(['sales_agents.zone_id','sales_agents.status', 'user_profiles.full_name']);
        $salesAgent->zone_name = null;
        if(!empty($salesAgent['zone_id'])){
            $salesAgent->zone_name = NetworkSupportCenter::where('zone_id', '=', $salesAgent['zone_id'])->value('zone_name');
        }
        $returned_data['results'] = $salesAgent;

        return ResponseWrapper::End($returned_data);
    }

    public function getSalesAgentDetails(Request $request, $id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $salesAgent = SalesAgent::where('id', '=', $id)->first();
        if (!empty($salesAgent['data_object'])) {
            $salesAgent['data_object'] = json_decode($salesAgent['data_object'], true);
        }

        // get next previous================
        $prevQuery = SalesAgent::query();
        $prevQuery->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid');
        $prevQuery->where('sales_agents.id', '<', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $prevQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $prevQuery->where('user_profiles.district_id', '=', $request->get('district'));
        }
        $salesAgent['previous_id'] = $prevQuery->max('sales_agents.id');

        $nxtQuery = SalesAgent::query();
        $prevQuery->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid');
        $nxtQuery->where('sales_agents.id', '>', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $nxtQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $nxtQuery->where('user_profiles.district_id', '=', $request->get('district'));
        }
        $salesAgent['next_id'] = $nxtQuery->min('id');
        // get next previous================



        $returned_data['results']['profile'] = UserProfile::where('uid', '=', $salesAgent['uid'])->first();
        $returned_data['results']['data'] = $salesAgent;

        return ResponseWrapper::End($returned_data);
    }

    public function salesAgentStatusUpdate(Request $request, $id, $employee_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $result = SalesAgent::where('id', '=', $id)->update(['zone_id'=>$request->get('zone_id'), 'status'=>$request->get('status'), 'monthly_commission_rate'=>$request->get('monthly_commission_rate')]);
        if($result){

            // update user base_role
            $uid = SalesAgent::where('id', '=', $id)->value('uid');
            User::where('id', '=', $uid)->update(['base_role'=>'sales_agent']);
            $up = UserProfile::where('uid', $uid)->first();
            $password = User::where('id', $uid)->value('text_password');

            // Data pushing to Internet Users Table
            InternetUsers::updateOrCreate(
                ['uid' => $uid],
                [
                    'zone_id' => $request->get('zone_id'),
                    'added_by' => $uid,
                    'package_id' => 501,
                    'package_type' => 'broadband',
                    'latitude' => $up->latitude,
                    'longitude' => $up->longitude,
                    'password' => $password,
                    'password_broadband' => $password,
                    'user_type' => 'broadband',
                    'billing_address' => $up->address,
                    'serial_number' => $uid,
                    'installation_charge' => 0,
                    'connection_status' => 'active'
                ]
            );
            $returned_data['results'] = $result;
        }

        return ResponseWrapper::End($returned_data);
    }
}
