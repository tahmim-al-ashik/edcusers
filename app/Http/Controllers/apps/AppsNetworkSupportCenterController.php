<?php

namespace App\Http\Controllers\apps;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MessageAndNotificationController;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\Service;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppsNetworkSupportCenterController extends Controller
{

    public function registerNetworkSupportCenter(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $updated_by = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('uid'));

        if(SalesAgent::where('uid', '=', $userId)->exists() || SalesPoint::where('uid', '=', $userId)->exists()){
            $returned_data['error_type'] = "already_exist";
            return ResponseWrapper::End($returned_data);
        }

        if(!NetworkSupportCenter::where('uid', '=', $userId)->exists()){
            $newCenter = new NetworkSupportCenter();
            $newCenter->uid = $userId;
            $newCenter->zone_name = $request->get('zone_name');
            $newCenter->support_number = $request->get('support_number');
            $newCenter->center_type = $request->get('center_type');
            $newCenter->coverage_type = $request->get('coverage_type');
            $newCenter->coverage_ids = $request->get('coverage_ids');
            $newCenter->division_id = $request->get('division_id');
            $newCenter->district_id = $request->get('district_id');
            $newCenter->upazila_id = $request->get('upazila_id');
            $newCenter->union_id = $request->get('union_id');
            $newCenter->village_id = $request->get('village_id');
            $newCenter->latitude = $request->get('latitude');
            $newCenter->longitude = $request->get('longitude');
            $newCenter->address = $request->get('address');
            $newCenter->data_object = json_encode($request->get('data_object'));
            $newCenter->updated_by = $updated_by;
            $newCenter->save();

            if($newCenter->id){
                $returned_data['results'] = $newCenter;


                // get affiliate person
                $dataObject = $request->get('data_object');
                $agentAuthId = $dataObject['ref_person_mobile'];
                $service_package_id = $dataObject['service_package_id'];
                if(!empty($agentAuthId)){
                    $agentData = User::where('auth_id', '=', $agentAuthId)->first();
                    if(!empty($agentData)){
                        $agentType = $agentData['base_role'];
                        $servicePackages = Service::where('id', '=', $service_package_id)->first();
                        $commission_type = $servicePackages['commission_type'];
                        $package_price = $servicePackages['price'];

                        $commission_rate = 0;
                        $commission_amount = 0;
                        if($agentType === 'sales_point'){
                            $commission_rate = $servicePackages['sales_point_commission'];
                        } else if($agentType === 'sales_agent'){
                            $commission_rate = $servicePackages['sales_agent_commission'];
                        }
                        //commission rate to amount
                        if($commission_type === 'percentage' && $commission_rate > 0){
                            $commission_amount = ($package_price * $commission_rate) / 100;
                        } else if($commission_rate > 0) {
                            $commission_amount = $commission_rate;
                        }
                        if($commission_amount > 0){
                            (new \App\Classes\CustomHelpers)->create_new_affiliate_history($agentData['id'], 'service_package', $userId, $commission_amount);
                        }
                    }
                }


            }
        } else {
            $returned_data['results'] = "already_exist";
        }
        return ResponseWrapper::End($returned_data);
    }

    public function networkSupportCenterDashboard(Request $request, $partner_auth_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);
        $returned_data['results'] = NetworkSupportCenter::where('uid', '=', $userId)->first(['id', 'zone_name', 'address', 'status', 'created_at']);
        return ResponseWrapper::End($returned_data);

    }

    public function getNetworkSupportCenterUserList(Request $request, $partner_auth_id, $type) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);

        $applicant_data = NetworkSupportCenter::where('uid', '=', $userId)->where('center_type', '=', 'support_center')->first(['id','zone_id','zone_name', 'address', 'status', 'created_at', 'district_id','coverage_type','coverage_ids']);
        if(!empty($applicant_data)){

            $returned_data['results']['list'] = [];
            $returned_data['results']['center_status'] = $applicant_data['status'];
            $returned_data['results']['applicant'] = $applicant_data;
            $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

            // get user list on partner area
            $query = InternetUsers::query();
            $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'internet_users.uid');
            $query->leftJoin('internet_packages', 'internet_packages.id', '=', 'internet_users.package_id');
            $query->leftJoin('users', 'users.id', '=', 'user_profiles.uid');
            if($applicant_data['status'] !== 'active'){
                $query->where('user_profiles.district_id', '=', $applicant_data['district_id']);
            } else {
                $query->where('internet_users.zone_id', '=', $applicant_data['zone_id']);
//                if($applicant_data['coverage_type'] === 'village'){
//                    $query->orWhereIn('user_profiles.village_id', [$applicant_data['coverage_ids']]);
//                } else if($applicant_data['coverage_type'] === 'union'){
//                    $query->orWhereIn('user_profiles.union_id', [$applicant_data['coverage_ids']]);
//                } else if($applicant_data['coverage_type'] === 'upazila'){
//                    $query->orWhereIn('user_profiles.upazila_id', [$applicant_data['coverage_ids']]);
//                } else if($applicant_data['coverage_type'] === 'district'){
//                    $query->orWhereIn('user_profiles.district_id', [$applicant_data['coverage_ids']]);
//                } else if($applicant_data['coverage_type'] === 'division'){
//                    $query->orWhereIn('user_profiles.division_id', [$applicant_data['coverage_ids']]);
//                }
            }
            if($type !== 'all'){
                $query->where('internet_users.package_type', '=', $type);
            }
            $query->orderBy('internet_users.created_at', 'DESC');
            $query->skip($totalSkip)->take(10);

            $returned_data['results']['total'] = $query->count();

            $userList = $query->get([
                'user_profiles.uid',
                'user_profiles.full_name',
                'user_profiles.mobile_number',
                'user_profiles.email',
                'internet_users.package_type',
                'internet_users.zone_id',
                'internet_users.connection_status',
                'internet_packages.bn_title',
                'internet_packages.en_title',
                'users.auth_id',
            ]);

            if($applicant_data['status'] !== 'active'){
                foreach ($userList as $userItem){
                    $userItem->mobile_number = null;
                    $userItem->email = null;
                    $returned_data['results']['list'][] = $userItem;
                }
            } else {
                $returned_data['results']['list'] = $userList;
            }

        }

        return ResponseWrapper::End($returned_data);

    }
    public function getSearchedNetworkSupportCenterUserList(Request $request, $partner_auth_id, $type, $keywords) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

        $applicant_data = NetworkSupportCenter::where('uid', '=', $userId)->where('center_type', '=', 'support_center')->first(['id','zone_name','zone_id', 'address', 'status', 'created_at', 'district_id']);
        if(!empty($applicant_data)){

            // get user list on partner area
            $query = InternetUsers::query();
            $query->leftJoin('users', 'users.id', '=', 'internet_users.uid');
            $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'users.id');
            $query->leftJoin('internet_packages', 'internet_packages.id', '=', 'internet_users.package_id');
            if(is_numeric($keywords)){
                $query->where('users.auth_id', '=', $keywords);
            } else {
                $query->where(function ($query) use ($keywords, $applicant_data){
                    $query->orWhere('user_profiles.full_name', 'LIKE', '%' . $keywords . '%');
                    $query->orWhere('user_profiles.email', 'LIKE', '%' . $keywords . '%');
                });
            }
            if($applicant_data['status'] !== 'active'){
                $query->where('user_profiles.district_id', '=', $applicant_data['district_id']);
            } else {
                $query->where('internet_users.zone_id', '=', $applicant_data['zone_id']);
            }
            if($type !== 'all'){
                $query->where('internet_users.package_type', '=', $type);
            }
            $query->skip($totalSkip)->take(10);
            $query->orderBy('internet_users.created_at', 'DESC');
            $returned_data['results']['total'] = $query->count();
            $returned_data['results']['list'] = [];

            $userList = $query->get([
                'user_profiles.uid',
                'user_profiles.full_name',
                'user_profiles.mobile_number',
                'user_profiles.email',
                'internet_users.package_type',
                'internet_users.zone_id',
                'internet_users.connection_status',
                'internet_packages.bn_title',
                'internet_packages.en_title',
                'users.auth_id',
            ]);

            foreach ($userList as $userItem){
                if($applicant_data['status'] !== 'active'){
                    $userItem->mobile_number = 'hidden';
                    $userItem->email = 'hidden';
                }
                $returned_data['results']['list'][] = $userItem;
            }
        }

        $returned_data['results']['center_status'] = $applicant_data['status'];
        return ResponseWrapper::End($returned_data);

    }
    public function getNetworkSupportCenterUserDetails(Request $request, $partner_auth_id, $user_auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $partner_auth_uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($user_auth_id);

        if($userId === null){
            $returned_data['error_type'] = "user_not_found";
            return ResponseWrapper::End($returned_data);
        }

        $applicant_data = NetworkSupportCenter::where('uid', '=', $partner_auth_uid)->where('center_type', '=', 'support_center')->first(['id','zone_name', 'address', 'status', 'created_at', 'district_id']);

        $userProfile = UserProfile::where('uid', '=', $userId)->first();
        $InternetUser = InternetUsers::where('uid', '=', $userId)->first()->makeHidden(['password','password_broadband']);
        if (!empty($userProfile['device_info'])) {
            $userProfile['device_info'] = json_decode($userProfile['device_info']);
        }
        $InternetPackage = InternetPackage::select('id', 'en_title', 'bn_title', 'price', 'expiration', 'type')->where('id', '=', $InternetUser['package_id'])->first();
        $InternetPackage->expiration_days = ($InternetPackage['expiration'] / 1440);

        $userProfile->address = (new CustomHelpers())->generate_user_address($userId);

        if($applicant_data['status'] !== 'active'){
            $userProfile->mobile_number = 'hidden';
            $userProfile->whatsapp_number = 'hidden';
            $userProfile->email = 'hidden';
            $userProfile->address = 'hidden';
        }

        $returned_data['results']['center_status'] = $applicant_data['status'];
        $returned_data['results']['profile'] = $userProfile;
        $returned_data['results']['internet_data'] = $InternetUser;
        $returned_data['results']['package_data'] = $InternetPackage;

        return ResponseWrapper::End($returned_data);
    }

    public function getNetworkSupportCenterSalesPointsList(Request $request, $partner_auth_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);
        $applicant_data = NetworkSupportCenter::where('uid', '=', $userId)->where('center_type', '=', 'support_center')->first();

        $returned_data['results']['list'] = [];
        $returned_data['results']['center_status'] = $applicant_data['status'];
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

        // get user list on partner area
        $query = SalesPoint::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_points.uid');
        if($applicant_data['status'] !== 'active'){
            $returned_data['results']['center_status'] = $applicant_data['status'];
            if($applicant_data['coverage_type'] === 'division'){
                $query->where('sales_points.division_id', '=', $applicant_data['division_id']);
            } else if($applicant_data['coverage_type'] === 'district'){
                $query->where('sales_points.district_id', '=', $applicant_data['district_id']);
            } else if($applicant_data['coverage_type'] === 'upazila'){
                $query->where('sales_points.upazila_id', '=', $applicant_data['upazila_id']);
            } else if($applicant_data['coverage_type'] === 'union'){
                $query->where('sales_points.union_id', '=', $applicant_data['union_id']);
            } else if($applicant_data['coverage_type'] === 'village'){
                $query->where('sales_points.village_id', '=', $applicant_data['village_id']);
            }
        } else {
            $query->where('sales_points.zone_id', '=', $applicant_data['zone_id']);
        }
        $query->orderBy('sales_points.created_at', 'DESC');
        $query->skip($totalSkip)->take(50);

        $returned_data['results']['total'] = $query->count();

        $userList = $query->get([
            'user_profiles.uid',
            'user_profiles.full_name',
            'user_profiles.mobile_number',
            'user_profiles.email',
            'sales_points.store_name',
            'sales_points.logo_source',
            'sales_points.status',
            'sales_points.address',
        ]);

        foreach ($userList as $userItem){
            if($applicant_data['status'] !== 'active'){
                $userItem->mobile_number = 'hidden';
                $userItem->email = 'hidden';
                $userItem->address = 'hidden';
            }
            $returned_data['results']['list'][] = $userItem;
        }

        return ResponseWrapper::End($returned_data);

    }
    public function getNetworkSupportCenterSalesPointDetails(Request $request, $partner_auth_id, $sales_point_uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $partner_auth_uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);

        $applicant_data = NetworkSupportCenter::where('uid', '=', $partner_auth_uid)->where('center_type', '=', 'support_center')->first();

        $userProfile = UserProfile::where('uid', '=', $sales_point_uid)->first(['full_name','mobile_number','whatsapp_number','email']);
        $salesPointData = SalesPoint::where('uid', '=', $sales_point_uid)->first(['address','created_at','latitude','longitude','store_name']);
        if (!empty($salesPointData['data_object'])) {
            $salesPointData['data_object'] = json_decode($salesPointData['data_object']);
        }

        if($applicant_data['status'] !== 'active'){
            $userProfile->mobile_number = 'hidden';
            $userProfile->whatsapp_number = 'hidden';
            $userProfile->email = 'hidden';
            $salesPointData->address = 'hidden';
        }

        $returned_data['results']['center_status'] = $applicant_data['status'];
        $returned_data['results']['profile'] = $userProfile;
        $returned_data['results']['store'] = $salesPointData;

        return ResponseWrapper::End($returned_data);
    }

    public function getNetworkSupportCenterSalesAgentsList(Request $request, $partner_auth_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);

        $applicant_data = NetworkSupportCenter::where('uid', '=', $userId)->where('center_type', '=', 'support_center')->first();
        if(!empty($applicant_data)){

            $returned_data['results']['list'] = [];
            $returned_data['results']['center_status'] = $applicant_data['status'];
            $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

            // get user list on partner area
            $query = SalesAgent::query();
            $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid');
            if($applicant_data['status'] !== 'active'){
                $query->where('user_profiles.district_id', '=', $applicant_data['district_id']);
            } else {
                $query->where('sales_agents.zone_id', '=', $applicant_data['zone_id']);
            }
            $query->orderBy('sales_agents.created_at', 'DESC');
            $query->skip($totalSkip)->take(50);

            $returned_data['results']['total'] = $query->count();

            $userList = $query->get([
                'user_profiles.uid',
                'user_profiles.full_name',
                'user_profiles.mobile_number',
                'user_profiles.email',
                'sales_agents.status',
            ]);

            foreach ($userList as $userItem){
                if($applicant_data['status'] !== 'active'){
                    $userItem->mobile_number = null;
                    $userItem->email = null;
                }
                $returned_data['results']['list'][] = $userItem;
            }
        }

        return ResponseWrapper::End($returned_data);

    }
    public function getSearchedNetworkSupportCenterSalesAgentList(Request $request, $partner_auth_id, $keywords) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);

        $applicant_data = NetworkSupportCenter::where('uid', '=', $userId)->where('center_type', '=', 'support_center')->first(['id','zone_name', 'address', 'status', 'created_at', 'district_id']);
        if(!empty($applicant_data)){

            // get user list on partner area
            $query = SalesAgent::query();
            $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'sales_agents.uid');
            $query->where(function ($query) use ($keywords, $applicant_data){
                $query->orWhere('user_profiles.full_name', 'LIKE', '%' . $keywords . '%');
                $query->orWhere('user_profiles.mobile_number', 'LIKE', '%' . $keywords . '%');
                $query->orWhere('user_profiles.email', 'LIKE', '%' . $keywords . '%');
            });
            if($applicant_data['status'] !== 'active'){
                $query->where('user_profiles.district_id', '=', $applicant_data['district_id']);
            } else {
                $query->where('sales_agents.zone_id', '=', $applicant_data['zone_id']);
            }
            $query->orderBy('sales_agents.created_at', 'DESC');
            $returned_data['results']['total'] = $query->count();
            $userList = $query->get([
                'user_profiles.full_name',
                'user_profiles.mobile_number',
                'user_profiles.email',
                'sales_agents.status'
            ]);

            foreach ($userList as $userItem){
                if($applicant_data['status'] !== 'active'){
                    $userItem->mobile_number = 'hidden';
                    $userItem->email = 'hidden';
                }
                $returned_data['results']['list'][] = $userItem;
            }
        }

        $returned_data['results']['center_status'] = $applicant_data['status'];
        return ResponseWrapper::End($returned_data);

    }
    public function getNetworkSupportCenterSalesAgentDetails(Request $request, $partner_auth_id, $sales_agent_uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $partner_auth_uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($partner_auth_id);

        $applicant_data = NetworkSupportCenter::where('uid', '=', $partner_auth_uid)->where('center_type', '=', 'support_center')->first();
        $userProfile = UserProfile::where('uid', '=', $sales_agent_uid)->first(['full_name','mobile_number','address','whatsapp_number','email','latitude','longitude']);
        $salesAgentData = SalesAgent::where('uid', '=', $sales_agent_uid)->first(['created_at','status']);

        if($applicant_data['status'] !== 'active'){
            $userProfile->mobile_number = 'hidden';
            $userProfile->whatsapp_number = 'hidden';
            $userProfile->email = 'hidden';
            $salesAgentData->address = 'hidden';
        }

        $returned_data['results']['center_status'] = $applicant_data['status'];
        $returned_data['results']['profile'] = $userProfile;
        $returned_data['results']['agent'] = $salesAgentData;

        return ResponseWrapper::End($returned_data);
    }
}
