<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\CorporateAgent;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoVillage;
use App\Models\MonthlyCommission;
use App\Models\NetworkSupportCenter;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\InternetUsers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PanelSalesPointController extends Controller
{
    public function getSalesPointUserCount(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = SalesPoint::query();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'sales_points.uid');

        // Initialize counters
        $total = 0;
        $active = 0;
        $pending = 0;

        // Check for each geographic level
        if (!empty($request->get('village')) && $request->get('village') != 'undefined') {
            $query->where('sales_points.village_id', $request->get('village'));
        } elseif (!empty($request->get('union')) && $request->get('union') != 'undefined') {
            $query->where('sales_points.union_id', $request->get('union'));
        } elseif (!empty($request->get('upazila')) && $request->get('upazila') != 'undefined') {
            $query->where('sales_points.upazila_id', $request->get('upazila'));
        } elseif (!empty($request->get('district')) && $request->get('district') != 'undefined') {
            $query->where('sales_points.district_id', $request->get('district'));
        } elseif (!empty($request->get('division')) && $request->get('division') != 'undefined') {
            $query->where('sales_points.division_id', $request->get('division'));
        }

        $total = $query->count();

        // Clone the query to count active and pending separately
        $activeQuery = clone $query;
        $pendingQuery = clone $query;

        $active = $activeQuery->where('sales_points.status', 'active')->count();
        $pending = $pendingQuery->where('sales_points.status', 'pending')->count();

        $returned_data['results']['total'] = $total;
        $returned_data['results']['active'] = $active;
        $returned_data['results']['pending'] = $pending;

        return ResponseWrapper::End($returned_data);
    }

    public function getAllLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 100;

        $query = SalesPoint::query();
        $total = $query->count();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'sales_points.uid');
        if (!empty($request->get('division')) && $request->get('division') != 'undefined') {
            $query->where('sales_points.division_id', $request->get('division'));
        }
        if (!empty($request->get('district')) && $request->get('district') != 'undefined') {
            $query->where('sales_points.district_id', $request->get('district'));
        }
        if (!empty($request->get('upazila')) && $request->get('upazila') != 'undefined') {
            $query->where('sales_points.upazila_id', $request->get('upazila'));
        }
        if (!empty($request->get('union')) && $request->get('union') != 'undefined') {
            $query->where('sales_points.union_id', $request->get('union'));
        }
        if (!empty($request->get('village')) && $request->get('village') != 'undefined') {
            $query->where('sales_points.village_id', $request->get('village'));
        }
        $query->whereNotNull('sales_points.latitude');
        $query->whereNotNull('sales_points.longitude');
        if(!empty($request->get('status'))){
            if($request->get('status') !== 'all'){
                $query->where('status',$request->get('status'));
            }
        }
        if(!empty($request->get('skip')) && $request->get('skip') !== 'none'){
            $query->skip($totalSkip)->take($totalLimit);
        }
        $query->skip($totalSkip)->take($totalLimit);

        $returned_data['results']['list'] = $query->get(['sales_points.id', 'sales_points.latitude', 'sales_points.longitude', 'sales_points.status','sales_points.store_name as full_name', 'user_profiles.mobile_number','user_profiles.email', DB::raw("'sales-points' as url_type")]);
        $returned_data['results']['total'] = $total;
        return ResponseWrapper::End($returned_data);
    }

    public function getSalesPointBasic(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = SalesPoint::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_points.uid');
        $query->where('sales_points.id', '=', $id);
        $salesPoint = $query->first(['sales_points.store_name', 'sales_points.zone_id','sales_points.status', 'user_profiles.full_name']);
        $salesPoint->zone_name = null;
        if(!empty($salesPoint['zone_id'])){
            $salesPoint->zone_name = NetworkSupportCenter::where('zone_id', '=', $salesPoint['zone_id'])->value('zone_name');
        }
        $returned_data['results'] = $salesPoint;
        return ResponseWrapper::End($returned_data);
    }

    public function getPointSearchResultList(Request $request, $keyword) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = SalesPoint::query();
        $query->leftJoin('users', 'users.id', '=', 'sales_points.uid');
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_points.uid');
        $query->where('users.auth_id', '=', $keyword);
        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'user_profiles.full_name',
            'user_profiles.email',
            'user_profiles.mobile_number',
            'sales_points.id',
            'sales_points.uid',
            'sales_points.zone_id',
            'sales_points.store_name',
            'sales_points.status',
            'sales_points.created_at'
        ]);

        return ResponseWrapper::End($returned_data);

    }

    public function getSalesPointSummary(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $activeCount = SalesPoint::join('user_profiles', 'user_profiles.uid', '=', 'sales_points.uid')
        ->leftJoin('communications', 'communications.customer_uid', '=', 'sales_points.uid')
        ->where('sales_points.status', 'active')
        ->count();

        $pendingCount = SalesPoint::join('user_profiles', 'user_profiles.uid', '=', 'sales_points.uid')
        ->leftJoin('communications', 'communications.customer_uid', '=', 'sales_points.uid')
        ->where('sales_points.status', 'pending')
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

    public function getSalesPointsList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keyword')));
        $district = $request->get('district');
        $status = $request->get('status');
        $c_status = $request->get('communication_status');

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        // get user list on partner area
        $query = SalesPoint::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_points.uid');
        $query->leftJoin('communications', function ($join) {
            $join->on('communications.customer_uid', '=', 'sales_points.uid')
                ->where('communications.type', '=', 'sales_point')
                ->whereRaw('communications.id = (SELECT id FROM communications WHERE customer_uid = sales_points.uid AND type = "sales_point" ORDER BY created_at DESC LIMIT 1)');
        });


        if($keywords) {
            $query->where('user_profiles.mobile_number', '=', $keywords);
        }
        if($status !== 'all'){
            $query->where('sales_points.status', '=', $status);
        }
        if($district !== 'all'){
            $query->where('sales_points.district_id', '=', $district);
        }

        if ($c_status !== 'all' && $c_status !== null) {
            $query->whereExists(function ($query) use ($c_status) {
                $query->select(DB::raw(1))
                      ->from('communications as com2')
                      ->whereColumn('com2.customer_uid', 'sales_points.uid')
                      ->where('com2.type', '=', 'sales_point')
                      ->where('com2.status', '=', $c_status)
                      ->whereRaw('com2.customer_uid = sales_points.uid AND com2.created_at = (SELECT MAX(created_at) FROM communications WHERE customer_uid = com2.customer_uid)');
            });
        }

        $query->orderBy('sales_points.created_at', $sortBy);
        $query->skip($totalSkip)->take(25);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'sales_points.id',
            'sales_points.uid',
            'user_profiles.full_name',
            'user_profiles.email',
            'user_profiles.mobile_number',
            'sales_points.store_name',
            'user_profiles.wallet_amount',
            'sales_points.status',
            'communications.status as c_status',
            'sales_points.created_at'
        ]);

        return ResponseWrapper::End($returned_data);

    }

    public function getSalesPointDetails(Request $request, $id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $salesPoint = SalesPoint::where('id', '=', $id)->first();
        if (!empty($salesPoint['data_object'])) {
            $data_object = json_decode($salesPoint['data_object'], true);
            $salesPoint['business_start'] = $data_object['business_start'];
            $salesPoint['ownership_type'] = $data_object['ownership_type'];
            $salesPoint['investment_type'] = $data_object['investment_type'];
        }


        // get next previous================
        $prevQuery = SalesPoint::query();
        $prevQuery->where('id', '<', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $prevQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $prevQuery->where('district_id', '=', $request->get('district'));
        }
        $salesPoint['previous_id'] = $prevQuery->max('id');

        $nxtQuery = SalesPoint::query();
        $nxtQuery->where('id', '>', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $nxtQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $nxtQuery->where('district_id', '=', $request->get('district'));
        }
        $salesPoint['next_id'] = $nxtQuery->min('id');
        // get next previous================


        $returned_data['results']['profile'] = UserProfile::where('uid', '=', $salesPoint['uid'])->first();
        $returned_data['results']['data'] = $salesPoint;

        return ResponseWrapper::End($returned_data);
    }

    public function salesPointStatusUpdate(Request $request, $id, $employee_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        //Log::info($request->all());
        if($request->get('type') === 'corporate'){
            $agent_uid = $request->get('uid');
            $agent_village_id = UserProfile::where('uid',$agent_uid)->value('village_id') ?? 'null';
            $agent_union_id = UserProfile::where('uid',$agent_uid)->value('union_id') ?? 'null';

            // village_name, union_name
            $village_name = GeoVillage::where('id', $agent_village_id)->value('en_name') ?? 'null';
            $union_name = GeoUnionPouroshova::where('id', $agent_union_id)->value('en_name') ?? 'null';

            $agent = new CorporateAgent;
            $agent->uid = $request->get('uid');
            $agent->client_id = $request->get('client_id');
            $agent->village_name = $village_name;
            $agent->union_name = $union_name;
            $agent->balance = 0.00;
            $agent->commission = $request->get('monthly_commission_rate');
            $agent->status = 1;
            $agent->activated_at = Carbon::now();
            $agent->created_at = Carbon::now();
            $agent->save();

            //Log::info($agent);
            if($agent){
                User::where('id', '=', $request->get('uid'))->update(['base_role'=>'agent']);
                SalesPoint::where('id', '=', $id)->delete();
                $returned_data['results'] = $agent;
            }

        }else{
            $result = SalesPoint::where('id', '=', $id)->update([
                'zone_id'=>$request->get('zone_id'),
                'status'=>$request->get('status'),
                'monthly_commission_rate'=>$request->get('monthly_commission_rate')
            ]);
            if($result){
                $uid = SalesPoint::where('id', '=', $id)->value('uid');
                User::where('id', '=', $uid)->update(['base_role'=>'sales_point']);
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
        }

        return ResponseWrapper::End($returned_data);
    }
}
