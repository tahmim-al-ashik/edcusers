<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\EnBnValueList;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\InternetPackage;
use App\Models\MonthlyCommissionBreakdownZone;
use App\Models\NetworkSupportCenter;
use App\Models\NetworkSupportCenterPackage;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\InternetUsers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PanelSupportNetworkCenterController extends Controller
{
    public function getSupportCenterUserCount(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = NetworkSupportCenter::query();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'network_support_centers.uid');

        // Initialize counters
        $total = 0;
        $active = 0;
        $pending = 0;

        // Check for each geographic level
        if (!empty($request->get('village')) && $request->get('village') != 'undefined') {
            $query->where('network_support_centers.village_id', $request->get('village'));
        } elseif (!empty($request->get('union')) && $request->get('union') != 'undefined') {
            $query->where('network_support_centers.union_id', $request->get('union'));
        } elseif (!empty($request->get('upazila')) && $request->get('upazila') != 'undefined') {
            $query->where('network_support_centers.upazila_id', $request->get('upazila'));
        } elseif (!empty($request->get('district')) && $request->get('district') != 'undefined') {
            $query->where('network_support_centers.district_id', $request->get('district'));
        } elseif (!empty($request->get('division')) && $request->get('division') != 'undefined') {
            $query->where('network_support_centers.division_id', $request->get('division'));
        }

        $total = $query->count();

        // Clone the query to count active and pending separately
        $activeQuery = clone $query;
        $pendingQuery = clone $query;

        $active = $activeQuery->where('network_support_centers.status', 'active')->count();
        $pending = $pendingQuery->where('network_support_centers.status', 'pending')->count();

        $returned_data['results']['total'] = $total;
        $returned_data['results']['active'] = $active;
        $returned_data['results']['pending'] = $pending;

        return ResponseWrapper::End($returned_data);
    }

    public function getAllLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 100;

        $query = NetworkSupportCenter::query();
        $total = $query->count();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'network_support_centers.uid');
        if (!empty($request->get('division')) && $request->get('division') != 'undefined') {
            $query->where('network_support_centers.division_id', $request->get('division'));
        }
        if (!empty($request->get('district')) && $request->get('district') != 'undefined') {
            $query->where('network_support_centers.district_id', $request->get('district'));
        }
        if (!empty($request->get('upazila')) && $request->get('upazila') != 'undefined') {
            $query->where('network_support_centers.upazila_id', $request->get('upazila'));
        }
        if (!empty($request->get('union')) && $request->get('union') != 'undefined') {
            $query->where('network_support_centers.union_id', $request->get('union'));
        }
        if (!empty($request->get('village')) && $request->get('village') != 'undefined') {
            $query->where('network_support_centers.village_id', $request->get('village'));
        }
        $query->whereNotNull('network_support_centers.latitude');
        $query->whereNotNull('network_support_centers.longitude');
        if(!empty($request->get('status'))){
            if($request->get('status') !== 'all'){
                $query->where('status',$request->get('status'));
            }
        }
        if(!empty($request->get('skip')) && $request->get('skip') !== 'none'){
            $query->skip($totalSkip)->take($totalLimit);
        }
        $query->skip($totalSkip)->take($totalLimit);

        $returned_data['results']['list'] = $query->get(['network_support_centers.id', 'network_support_centers.latitude', 'network_support_centers.longitude', 'network_support_centers.status','user_profiles.full_name', 'user_profiles.mobile_number','user_profiles.email', DB::raw("'support-centers' as url_type")]);
        $returned_data['results']['total'] = $total;
        return ResponseWrapper::End($returned_data);
    }

    public function getSupportCenterSummary(Request $request, $center_type): JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $activeCount = NetworkSupportCenter::join('user_profiles', 'user_profiles.uid', '=', 'network_support_centers.uid')
            ->leftJoin('communications', 'communications.customer_uid', '=', 'network_support_centers.uid')
            ->where('network_support_centers.status', 'active')
            ->count();

        $pendingCount = NetworkSupportCenter::join('user_profiles', 'user_profiles.uid', '=', 'network_support_centers.uid')
            ->leftJoin('communications', 'communications.customer_uid', '=', 'network_support_centers.uid')
            ->where('network_support_centers.status', 'pending')
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

    public function getSearchResultList(Request $request, $center_type, $keyword) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = NetworkSupportCenter::query();
        $query->leftJoin('users', 'users.id', '=', 'network_support_centers.uid');
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'network_support_centers.uid');
        $query->where('network_support_centers.center_type', '=', $center_type);
        $query->where(function ($query) use ($keyword){
            if(is_numeric($keyword)){
                $query->orWhere('users.auth_id', '=', $keyword);
                $query->orWhere('network_support_centers.zone_id', '=', $keyword);
            } else {
                $query->orWhere('network_support_centers.zone_name', '=', strtolower($keyword));
            }
        });
        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(['user_profiles.full_name','user_profiles.email','user_profiles.mobile_number', 'network_support_centers.id','network_support_centers.uid','network_support_centers.zone_id','network_support_centers.zone_name','network_support_centers.status','network_support_centers.created_at']);

        return ResponseWrapper::End($returned_data);
    }

    public function getCenterList(Request $request, $center_type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keyword')));
        $district = $request->get('district');
        $status = $request->get('status');
        $c_status = $request->get('communication_status');

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        $query = NetworkSupportCenter::query();
        $query->join('user_profiles', 'user_profiles.uid', '=', 'network_support_centers.uid');
        $query->leftJoin('communications', function ($join) {
            $join->on('communications.customer_uid', '=', 'network_support_centers.uid')
                ->where('communications.type', '=', 'support_center')
                ->whereRaw('communications.id = (SELECT id FROM communications WHERE customer_uid = network_support_centers.uid AND type = "support_center" ORDER BY created_at DESC LIMIT 1)');
        });

        if($keywords) {
            $query->where(function($qr) use ($keywords){
                $qr->where('user_profiles.mobile_number', '=', $keywords)
                ->orWhere('network_support_centers.zone_id', '=', $keywords)
                ->orWhere('network_support_centers.zone_name', '=', $keywords);
            });
        }

        if ($district !== 'all' && $district !== null) {
            $query->where('network_support_centers.district_id', '=', $district);
        }

        if ($status !== 'all' && $status !== null) {
            $query->where('network_support_centers.status', '=', $status);
        }

        if ($c_status !== 'all' && $c_status !== null) {
            $query->whereExists(function ($query) use ($c_status) {
                $query->select(DB::raw(1))
                      ->from('communications as com2')
                      ->whereColumn('com2.customer_uid', 'network_support_centers.uid')
                      ->where('com2.type', '=', 'support_center')
                      ->where('com2.status', '=', $c_status)
                      ->whereRaw('com2.customer_uid = network_support_centers.uid AND com2.created_at = (SELECT MAX(created_at) FROM communications WHERE customer_uid = com2.customer_uid)');
            });
        }

        $query->where('network_support_centers.center_type', '=', $center_type);
        $query->orderBy('network_support_centers.created_at', $sortBy);

        if(empty($request->get('limit')) || $request->get('limit') !== 'no-limit'){
            $query->skip($totalSkip)->take(25);
        }

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(
            [
                'user_profiles.full_name',
                'user_profiles.email',
                'user_profiles.mobile_number',
                'network_support_centers.id',
                'network_support_centers.uid',
                'network_support_centers.zone_id',
                'network_support_centers.zone_name',
                'network_support_centers.status',
                'communications.status as c_status',
                'network_support_centers.created_at'
            ]);
        return ResponseWrapper::End($returned_data);
    }

    public function getCenterListByStatus(Request $request, $center_type, $status) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = NetworkSupportCenter::where('center_type', '=', $center_type)->where('status', '=', $status)->orderBy('zone_name')->get(['id','uid','zone_id','zone_name']);
        return ResponseWrapper::End($returned_data);
    }

    public function getCenterBasic(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = NetworkSupportCenter::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'network_support_centers.uid');
        $query->where('network_support_centers.id', '=', $id);
        $supportCenter = $query->first(['network_support_centers.zone_name', 'network_support_centers.zone_id','network_support_centers.status', 'user_profiles.full_name']);
        $returned_data['results'] = $supportCenter;
        return ResponseWrapper::End($returned_data);
    }

    public function getNetworkSupportCenterDetails(Request $request, $id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $centerData = NetworkSupportCenter::where('id', '=', $id)->first()->makeHidden(['id', 'uid','division_id','district_id','upazila_id','union_id','village_id']);

        // get next previous================
        $prevQuery = NetworkSupportCenter::query();
        $prevQuery->where('id', '<', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $prevQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $prevQuery->where('district_id', '=', $request->get('district'));
        }
        $centerData['previous_id'] = $prevQuery->max('id');

        $nxtQuery = NetworkSupportCenter::query();
        $nxtQuery->where('id', '>', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $nxtQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $nxtQuery->where('district_id', '=', $request->get('district'));
        }
        $centerData['next_id'] = $nxtQuery->min('id');
        // get next previous================


        if (!empty($centerData['data_object'])) {
            $data_object = json_decode($centerData['data_object'], true);
            $centerData['existing_facilities'] = EnBnValueList::whereIn('id', explode(',', $data_object['existing_facilities']))->pluck('bn') ?? null;
            $centerData['current_business'] = !empty($data_object['current_business']) ? $data_object['current_business'] : '';

            $centerData['interest_reason'] = $data_object['interest_reason'];
            $centerData['forward_months'] = $data_object['forward_months'];
            $centerData['ref_person_name'] = $data_object['ref_person_name'];
            $centerData['ref_person_mobile'] = $data_object['ref_person_mobile'];
            $centerData['ref_person_relation'] = $data_object['ref_person_relation'];
            unset($centerData['data_object']);
        }
        $centerData['center_type'] = str_replace('_', ' ', $centerData['center_type']);
        if($centerData['coverage_ids'] !== null){
            if($centerData['coverage_type'] === 'village'){
                $centerData['coverage_ids'] = GeoVillage::whereIn('id', explode(',', $centerData['coverage_ids']))->pluck('bn_name');
            } else if($centerData['coverage_type'] === 'union'){
                $centerData['coverage_ids'] = GeoUnionPouroshova::whereIn('id', explode(',', $centerData['coverage_ids']))->pluck('bn_name');
            } else if($centerData['coverage_type'] === 'upazila'){
                $centerData['coverage_ids'] = GeoUpazila::whereIn('id', explode(',', $centerData['coverage_ids']))->pluck('bn_name');
            } else if($centerData['coverage_type'] === 'district'){
                $centerData['coverage_ids'] = GeoDistrict::whereIn('id', explode(',', $centerData['coverage_ids']))->pluck('bn_name');
            } else if($centerData['coverage_type'] === 'division'){
                $centerData['coverage_ids'] = GeoDivision::whereIn('id', explode(',', $centerData['coverage_ids']))->pluck('bn_name');
            }
        }

        $profile = UserProfile::where('uid', '=', $centerData['uid'])->first(['uid','full_name','mobile_number','whatsapp_number','email','profession','address']);
        $returned_data['results']['profile'] = $profile;
        $returned_data['results']['data'] = $centerData;
        $returned_data['results']['packages'] = NetworkSupportCenterPackage::where('zone_id', '=', $centerData['zone_id'])->pluck('package_id');

        return ResponseWrapper::End($returned_data);
    }

    public function networkSupportCenterStatusUpdate(Request $request, $id, $employee_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $zoneId = $request->get('zone_id');

        // execute current month commission rate if not calculate already
        if($request->get('commission_rate_type') === 'fixed'){
            $monthlyCommission = MonthlyCommissionBreakdownZone::where('zone_id', '=', $zoneId)->whereMonth('date_month', '=', Carbon::now()->month)->whereYear('date_month', '=', Carbon::now()->year)->first();
            $commissionRateBroadband = $request->get('broadband_commission_rate');
            $commissionRateWifi = $request->get('wifi_commission_rate');
            if($monthlyCommission === null){
                MonthlyCommissionBreakdownZone::where('zone_id', '=', $zoneId)->create(['zone_id'=>$request->get('zone_id'), 'date_month'=>Carbon::now(), 'commission_rate_wifi'=>$commissionRateWifi, 'commission_rate_broadband'=>$commissionRateBroadband]);
            } else {
                $monthlyCommission['commission_rate_broadband'] = $commissionRateBroadband;
                $monthlyCommission['commission_rate_wifi'] = $commissionRateWifi;
                MonthlyCommissionBreakdownZone::where('zone_id', '=', $zoneId)->whereMonth('date_month', '=', Carbon::now()->month)->whereYear('date_month', '=', Carbon::now()->year)->update(['commission_rate_wifi'=>$commissionRateWifi, 'commission_rate_broadband'=>$commissionRateBroadband]);
            }
        }


        $result = NetworkSupportCenter::where('id', '=', $id)->update(
            [
                'zone_id'=>$zoneId,
                'zone_ip'=>$request->get('zone_ip'),
                'zone_name'=>$request->get('zone_name'),
                'total_desh_package'=>$request->get('desh_package_limitation'),
                // 'commission_rate_type'=>$request->get('commission_rate_type'),
                // 'broadband_commission_rate'=>$request->get('broadband_commission_rate'),
                // 'wifi_commission_rate'=>$request->get('wifi_commission_rate'),
                'zone_username'=>'webadmin',
                'zone_password'=>'Plexus$%Webadmin',
                'status'=>$request->get('status')
            ]
        );
        if($result){

            // update user base_role
            $uid = NetworkSupportCenter::where('id', '=', $id)->value('uid');
            User::where('id', '=', $uid)->update(['base_role'=>'support_center']);
            $up = UserProfile::where('uid', $uid)->first();
            $password = User::where('id', $uid)->value('text_password');

            // Data pushing to Internet Users Table
            InternetUsers::updateOrCreate(
                ['uid' => $uid],
                [
                    'zone_id' => $zoneId,
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

    public function networkSupportCenterSkippedPackages(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = InternetPackage::where('is_active', '=', 1)->where('skip_from_display', '=', 1)->orderBy('weight')->get(['id','en_title','bn_title','type']);
        return ResponseWrapper::End($returned_data);
    }

    public function networkSupportCenterPackageAssign(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $zone_id = $request->get('zone_id');
        $package_id = $request->get('package_id');

        if(NetworkSupportCenterPackage::where('zone_id', '=', $zone_id)->where('package_id', '=', $package_id)->exists()){
            if(NetworkSupportCenterPackage::where('zone_id', '=', $zone_id)->where('package_id', '=', $package_id)->delete()){
                $returned_data['results'] = 'deleted';
            }
        } else {
            if(NetworkSupportCenterPackage::create(['zone_id'=> $zone_id, 'package_id'=> $package_id])){
                $returned_data['results'] = 'created';
            }
        }

        return ResponseWrapper::End($returned_data);
    }

    public function goNextPrevious(Request $request, $type, $current_id, $sort_by, $status, $district) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
//
//        $query = NetworkSupportCenter::query();
//        if($type === 'next'){
//            $query->where('id', '<', $current_id)->min('id');
//        } else {
//            $query->where('id', '>', $current_id)->max('id');
//        }
//
//        if($status !== 'all'){
//            $query->where('status', '=', $status);
//        }
//        if($district !== 'all'){
//            $query->where('district_id', '=', $district);
//        }
//        $returned_data['results'] = $query->value('id');
        return ResponseWrapper::End($returned_data);
    }
}
