<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Http\Controllers\Controller;
use App\Classes\ResponseWrapper;
use App\Models\BroadbandDbZone;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\CorporateClientsSettings;
use App\Models\CorporateSubAgent;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUpazila;
use App\Models\InternetPackageCorporate;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\IspBusinessCenter;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Matrix\Operators\Division;
use Illuminate\Support\Facades\Log;

class PanelIspBusinessCenterController extends Controller
{
    public function getInternetUserListByDistrict($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $district_id = UserProfile::where('uid', $uid)->value('district_id');
        $internetUsers = InternetUsers::with(['user', 'userProfile','latestCommunication'])
            ->where('connection_status','pending')
            ->whereHas('userProfile', function ($query) use ($district_id) {
                $query->where('district_id', $district_id);
            })
            ->orderBy('created_at', 'DESC')
            ->get([
                'id',
                'uid',
                'connection_status',
                'created_at',
                'package_type',
                'package_id',
                'zone_id'
            ]);

        $returned_data['results']['total'] = $internetUsers->count();
        $returned_data['results']['list'] = $internetUsers->map(function ($user) {
            return [
                'user_mobile_number' => $user->user->auth_id ?? null, // Handle null user
                'id' => $user->id,
                'user_id' => $user->uid,
                'user_name' => $user->userProfile->full_name ?? 'N/A', // Handle null userProfile
                'email' => $user->userProfile->email ?? 'N/A', // Handle null userProfile
                'connection_status' => $user->connection_status,
                'communication_status' => $user->latestCommunication->status ?? 'N/A',
                'created_at' => $user->created_at,
                'package_type' => $user->package_type,
                'package_id' => $user->package_id ?? null,
                'package_name' => InternetPackageCorporate::where('id', $user->package_id)->value('package_name'),
                'zone_id' => $user->zone_id,
                'registration_date' => $user->created_at,
            ];
        });

        return ResponseWrapper::End($returned_data);
    }
    
    public function getInternetUserListByZone($zone_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        // union id of the zone_id
        $upazila_id = UserProfile::where('uid', $zone_id)->value('upazila_id');

        $internetUsers = InternetUsers::with(['user', 'userProfile','latestCommunication'])
        ->where('connection_status', 'pending')
        //->where('zone_id', $zone_id)
        ->where(function ($query) use ($zone_id, $upazila_id) {
            $query->where('zone_id', $zone_id)
                  ->orWhereHas('userProfile', function ($subQuery) use ($upazila_id) {
                      $subQuery->where('upazila_id', $upazila_id);
                  });
        })
        ->orderBy('created_at', 'DESC')
        ->get([
            'id',
            'uid',
            'connection_status',
            'created_at',
            'package_type',
            'package_id',
            'zone_id'
        ]);
        //dd($internetUsers);

        $returned_data['results']['total'] = $internetUsers->count();
        $returned_data['results']['list'] = $internetUsers->map(function ($user) {
            return [
                'user_mobile_number' => $user->user->auth_id ?? null, // Handle null user
                'id' => $user->id,
                'user_id' => $user->uid,
                'user_name' => $user->userProfile->full_name ?? 'N/A', // Handle null userProfile
                'email' => $user->userProfile->email ?? 'N/A', // Handle null userProfile
                'connection_status' => $user->connection_status,
                'communication_status' => $user->latestCommunication->status ?? 'N/A',
                'created_at' => $user->created_at,
                'package_type' => $user->package_type,
                'package_id' => $user->package_id ?? null,
                'package_name' => InternetPackageCorporate::where('id', $user->package_id)->value('package_name'),
                'zone_id' => $user->zone_id,
                'registration_date' => $user->created_at,
            ];
        });

        return ResponseWrapper::End($returned_data);
    }

    public function getInternetUsersByRadiation($radiation, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $radiusInMeter = $radiation / 1000;
        $userProfile = UserProfile::where('uid',$uid)->first();

        $internetUsers = InternetUsers::with(['user', 'userProfile','latestCommunication'])
                        ->select(DB::raw("
                            internet_users.id,
                            internet_users.uid,
                            internet_users.zone_id,
                            internet_users.package_type,
                            internet_users.package_id,
                            internet_users.connection_status,
                            internet_users.created_at,
                            internet_users.latitude,
                            internet_users.longitude,
                            ROUND((6371 * acos(cos(radians('$userProfile->latitude')) * cos(radians(latitude)) * cos(radians(longitude) - radians('$userProfile->longitude')) + sin(radians('$userProfile->latitude')) * sin(radians(latitude)))), 2) AS distance
                        "))
                        ->havingRaw('distance < ?', [$radiusInMeter])
                        ->orderBy('distance')
                        ->get();

        $returned_data['results']['list'] = $internetUsers->map(function ($user) {
            return [
                'user_mobile_number' => $user->user->auth_id ?? null,
                'id' => $user->id ?? null,
                'user_id' => $user->uid ?? null,
                'user_name' => $user->userProfile->full_name ?? 'N/A',
                'email' => $user->userProfile->email ?? 'N/A',
                'connection_status' => $user->connection_status ?? null,
                'communication_status' => $user->latestCommunication->status ?? 'N/A',
                'created_at' => $user->created_at ?? null,
                'package_type' => $user->package_type ?? null,
                'package_id' => $user->package_id ?? null,
                'package_name' => InternetPackageCorporate::where('id', $user->package_id)->value('package_name') ?? (InternetPackage::where('id', $user->package_id)->value('mikrotik_radius_group_name') ?? null),
                'zone_id' => $user->zone_id ?? null,
                'registration_date' => $user->created_at ?? null,
            ];
        });

        // success response
        $returned_data['status'] = 'success';
        return ResponseWrapper::End($returned_data);
    }
    
    public function getIspBusinessCenterList(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keyword')));
        $status = $request->get('status');
        $c_status = $request->get('communication_status');
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        $query = CorporateClient::query();
        $query->leftJoin("users", 'users.id', '=', 'corporate_clients.uid');
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'corporate_clients.uid');
        $query->leftJoin('communications', function ($join) {
            $join->on('communications.customer_uid', '=', 'corporate_clients.uid')
                ->where('communications.type', '=', 'isp_business')
                ->whereRaw('communications.id = (SELECT id FROM communications WHERE customer_uid = corporate_clients.uid AND type = "isp_business" ORDER BY created_at DESC LIMIT 1)');
        });

        if ($keywords) {
            $query->where('user_profiles.mobile_number', '=', $keywords);
        }

        if ($status !== 'all' && $status !== null) {
            $query->where('corporate_clients.status', '=', $status);
        }

        if ($c_status !== 'all' && $c_status !== null) {
            $query->whereExists(function ($query) use ($c_status) {
                $query->select(DB::raw(1))
                      ->from('communications as com2')
                      ->whereColumn('com2.customer_uid', 'corporate_clients.uid')
                      ->where('com2.type', '=', 'isp_business')
                      ->where('com2.status', '=', $c_status)
                      ->whereRaw('com2.customer_uid = corporate_clients.uid AND com2.created_at = (SELECT MAX(created_at) FROM communications WHERE customer_uid = com2.customer_uid)');
            });
        }

        $query->orderBy('corporate_clients.created_at', $sortBy);
        $query->skip($totalSkip)->take(25);

        $returned_data['results']['list'] = $query->get([
            'corporate_clients.id',
            'corporate_clients.uid',
            'users.text_password',
            'user_profiles.full_name',
            'user_profiles.email',
            'user_profiles.mobile_number',
            'corporate_clients.company_name',
            'corporate_clients.mikrotik_ip',
            'corporate_clients.hotspot_profile',
            'corporate_clients.balance',
            'corporate_clients.commission',
            'corporate_clients.status',
            'communications.status as c_status',
            'corporate_clients.created_at'
    	]);
        $returned_data['results']['total'] = $query->count();

        return ResponseWrapper::End($returned_data);
    }

    public function getIspBusinessCenterListAll(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = CorporateClient::where('status','active');
        $returned_data['results']['list'] = $query->get(['uid','zone_name']);
        $returned_data['results']['total'] = $query->count();
        return ResponseWrapper::End($returned_data);
    }

    public function getIspBusinessDetails(Request $request, $id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $ispBusiness = CorporateClient::where('uid', '=', $id)->first();
        if (!empty($ispBusiness['data_object'])) {
            $ispBusiness['data_object'] = json_decode($ispBusiness['data_object'], true);
        }

        // get next previous================
        $prevQuery = CorporateClient::query();
        $prevQuery->leftJoin('user_profiles', 'user_profiles.uid', '=', 'corporate_clients.uid');
        $prevQuery->where('corporate_clients.id', '<', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $prevQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $prevQuery->where('user_profiles.district_id', '=', $request->get('district'));
        }
        $ispBusiness['previous_id'] = $prevQuery->max('corporate_clients.id');

        $nxtQuery = CorporateClient::query();
        $prevQuery->leftJoin('user_profiles', 'user_profiles.uid', '=', 'corporate_clients.uid');
        $nxtQuery->where('corporate_clients.id', '>', $id);
        if($request->get('status') !== null && $request->get('status') !== 'all'){
            $nxtQuery->where('status', '=', $request->get('status'));
        }
        if($request->get('district') !== null && $request->get('district') !== 'all'){
            $nxtQuery->where('user_profiles.district_id', '=', $request->get('district'));
        }
        $ispBusiness['next_id'] = $nxtQuery->min('id');
        // get next previous================

        $returned_data['results']['profile'] = UserProfile::where('uid', '=', $ispBusiness['uid'])->first();
        $returned_data['results']['data'] = $ispBusiness;

        return ResponseWrapper::End($returned_data);
    }

    public function ispBusinessStatusUpdate(Request $request, $uid, $employee_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $uid_validation = User::where('id',$uid)->exists();
        if(!$uid_validation){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        // Validate the request input
        $request->validate([
            'zone_name' => 'required',
            'partner_name' => 'required',
            'thana' => 'required',
            'district' => 'required',
            'division' => 'required',
            'hotspot_profile' => 'required',
            'mikrotik_ip' => 'required',
            'client_type' => 'required',
            'status' => 'required'
        ]);

        $status_code = '';
        if($request->get('status') === 'active') {
            $status_code = 0;
        }elseif($request->get('status') === 'pending'){
            $status_code = 1;
        }elseif($request->get('status') === 'processing'){
            $status_code = 2;
        }elseif($request->get('status') === 'suspended'){
            $status_code = 3;
        }

        // create new profile
        // $zoneData = new BroadbandDbZone();
        // $zoneData->zone_name = $request->get('zone_name');
        // $zoneData->partner_name = $request->get('partner_name');
        // $zoneData->address = $request->get('address');
        // $zoneData->thana = GeoUpazila::where('id', $request->get('thana'))->value('en_name');
        // $zoneData->district = GeoDistrict::where('id', $request->get('district'))->value('en_name');
        // $zoneData->division = GeoDivision::where('id', $request->get('division'))->value('en_name');
        // $zoneData->hotspot_profile = $request->get('hotspot_profile');
        // $zoneData->mikrotik_ip = $request->get('mikrotik_ip');
        // $zoneData->username = 'webadmin';
        // $zoneData->password = 'Plexus$%Webadmin';
        // $zoneData->status = $status_code;
        // $zoneData->activation_date = Carbon::now();
        // $zoneData->save();
        // Update user profile

        InternetUsers::updateOrCreate(
            ['uid' => $uid],
            [
                'zone_id' => $uid,
                'package_id' => $request->get('package_id') ?? 00,
                'package_type' => $request->get('package_type') ?? 'broadband',
                'connection_status' => $request->get('connection_status') ?? 'pending',
            ]
        );

        $ccs = CorporateClientsSettings::where('client_uid', $uid)->first();
        CorporateClientsSettings::updateOrCreate(
            ['client_uid' => $uid],
            [
                'billing_cycle' => 'fixed_date',
                'manual_disable_day' => 5,
                'payment_method' => 'bkash',
                'bkash_username' => $ccs->bkash_username ?? '01971399998',
                'bkash_password' => $ccs->bkash_password ?? '?H7QP}eQa<A',
                'bkash_app_key' => $ccs->bkash_app_key ?? 'PBD0wucYMjDlgbw7lQNI6omctc',
                'bkash_app_secret_key' => $ccs->bkash_app_secret_key ?? 'RQefyX8FVwSTUPLNvAaweFg8CM84MLlhCVu5Q1be19EuiyJgfgcT',
                'created_at' => Carbon::now()
            ]
        );

        $package_check = InternetPackageCorporate::where('package_name', $request->get('hotspot_profile'))->exists();
        if($package_check){
            InternetPackageCorporate::where('package_name', $request->get('hotspot_profile'))->update([
                'package_name' => $request->get('hotspot_profile'),
                'package_type' => 'wifi',
                'is_active' => 1,
            ]);
        }else{
            $package = new InternetPackageCorporate();
            $package->package_name = $request->get('hotspot_profile');
            $package->package_type = 'wifi';
            $package->is_active = 1;
            $package->save();
        }

        $corporateClient = CorporateClient::where('uid', $uid)->first();
        if ($corporateClient) {
            $updateData = [
                'zone_name' => $request->get('zone_name'),
                'status' => $request->get('status'),
                'mikrotik_username' => $request->get('mikrotik_username', 'webadmin'),
                'mikrotik_password' => $request->get('mikrotik_password', 'Plexus$%Webadmin'),
                'updated_by' => $employee_id,
                'mikrotik_ip' => $request->get('mikrotik_ip'),
                'hotspot_profile' => $request->get('hotspot_profile'),
                // 'package_list' => json_encode(["5","6","501","502"]),
                'client_type' => $request->get('client_type')
            ];
            $corporateClient->update($updateData);
        }

        if($request->get('status') === 'active'){
            User::where('id', $uid)->update([
                'panel_access' => 1
            ]);
        }else{
            User::where('id', $uid)->update([
                'panel_access' => 0
            ]);
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'This user added as a client!';
        return ResponseWrapper::End($returned_data);
    }

    public function ispBusinessBalanceWallet($id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $id)->exists();
        $agent = CorporateAgent::where('uid', $id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $id)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        if($client){
            $balance = CorporateClient::where('uid', $id)->value('balance');
        }elseif($agent){
            $balance = CorporateAgent::where('uid', $id)->value('balance');
        }elseif($sub_agent){
            $balance = CorporateSubAgent::where('uid', $id)->value('balance');
        }

        $wallet = UserProfile::where('uid',$id)->value('wallet_amount');

        $returned_data['results']['list'] = [
            'balance' => $balance,
            'wallet' => $wallet,
        ];
        return ResponseWrapper::End($returned_data);
    }

    public function findUserForISP($mobile_number) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $checkUserExists = User::where('auth_id', $mobile_number)->exists();
        if(!$checkUserExists){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        // Get User Data
        $results = User::query();
        $results->where('auth_id',$mobile_number);
        $results->leftJoin('user_profiles', 'user_profiles.uid', '=', 'users.id');
        $results->leftJoin('internet_users', 'internet_users.uid', '=', 'users.id');

        $returned_data['status'] = 'success';
        $returned_data['results']['data'] = $results->get();

        return ResponseWrapper::End($returned_data);
    }
}
