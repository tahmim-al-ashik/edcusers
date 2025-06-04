<?php

namespace App\Http\Controllers\panel\isp_client;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Imports\ISPClientHotspotUserBulkRegisterImport;
use App\Models\AffiliateHistory;
use App\Models\AgentCommissionBreakdown;
use App\Models\CorporateAgent;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUpazila;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\PaymentToken;
use App\Models\UserProfile;
use App\Models\WifiDbBkashInfo;
use App\Models\WifiDbBkashInfoClient;
use App\Models\WifiDbPayment;
use App\Models\WifiDbPaymentClient;
use App\Models\WifiDbRadCheck;
use App\Models\WifiDbRadCheckClient;
use App\Models\WifiDbRadReply;
use App\Models\WifiDbRadReplyClient;
use App\Models\WifiDbRadUserGroup;
use App\Models\WifiDbRadUserGroupClient;
use App\Models\WifiDbUserInfo;
use App\Models\WifiDbUserInfoClient;
use App\Models\WifiDbRadAcctClient;
use App\Models\WifiDbRadAcct;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\BroadbandDbSecret;

class ISPHotspotInternetUserController extends Controller
{
    public function getHotspotInternetUserListISP($uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        // Checking User
        $admin = User::where('id', $uid)->value('base_role');
        $client = CorporateClient::where('uid', $uid)->exists();
        $clientType = CorporateClient::where('uid', $uid)->value('client_type');
        $agent = CorporateAgent::where('uid', $uid)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $uid)->exists();

        if (!$admin && !$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        // Fetch usernames from the first database
        $usernames = [];
        if ($admin === 'admin') {
                $usernames = WifiDbUserInfoClient::whereNotNull('client_id')->pluck('username')->toArray();
        } elseif ($client) {
            if($clientType === 'corporate'){
                $usernames = WifiDbUserInfoClient::where('client_id', $uid)->pluck('username')->toArray();
            }else{
                $usernames = WifiDbUserInfoClient::where('client_id', $uid)->pluck('username')->toArray();
            }
        } elseif ($agent) {
            $clientUid = CorporateAgent::where('uid', $uid)->value('client_id');
            $clientType = CorporateClient::where('uid', $clientUid)->value('client_type');
            if($clientType === 'corporate'){
                $usernames = WifiDbUserInfoClient::where('agent_id', $uid)->pluck('username')->toArray();
            }else{
                $usernames = WifiDbUserInfoClient::where('agent_id', $uid)->pluck('username')->toArray();
            }
        } elseif ($sub_agent) {
            $clientUid = CorporateSubAgent::where('uid', $uid)->value('client_id');
            $clientType = CorporateClient::where('uid', $clientUid)->value('client_type');
            if($clientType === 'corporate'){
                $usernames = WifiDbUserInfoClient::where('sub_agent_id', $uid)->pluck('username')->toArray();
            }else{
                $usernames = WifiDbUserInfoClient::where('sub_agent_id', $uid)->pluck('username')->toArray();
            }
        }

        // Fetch hotspot Internet User List from the second database
        $hotspotInternetUserList = User::query()
            ->whereIn('auth_id', $usernames)
            ->join('user_profiles', 'users.id', '=', 'user_profiles.uid')
            ->join('internet_users', 'users.id', '=', 'internet_users.uid')
            ->select(
                'user_profiles.uid',
                'user_profiles.full_name',
                'users.text_password',
                'internet_users.package_id',
                DB::raw("(SELECT package_name FROM internet_package_corporates WHERE id = internet_users.package_id) as package_name"),
                'internet_users.added_by as added_by',
                DB::raw("(SELECT base_role FROM users WHERE users.id = internet_users.added_by) as owner_type"),
                'user_profiles.mobile_number',
                'user_profiles.whatsapp_number',
                'user_profiles.email',
                'user_profiles.nid',
                'user_profiles.division_id',
                'user_profiles.district_id',
                'user_profiles.upazila_id',
                'user_profiles.union_id',
                'user_profiles.village_id',
                'user_profiles.house_no',
                'user_profiles.address',
                'user_profiles.latitude',
                'user_profiles.longitude',
                'user_profiles.created_at',
                'user_profiles.address_direction',
                'internet_users.user_type as UserType'
            );

        // Apply additional filtering based on user type
        if($admin === 'admin'){
            $hotspotInternetUserList->whereNotNull('internet_users.zone_id');
        }elseif ($client) {
            $hotspotInternetUserList->where('internet_users.zone_id', $uid);
        } elseif ($agent) {
            $hotspotInternetUserList->where('internet_users.agent_id', $uid);
        } elseif ($sub_agent) {
            $hotspotInternetUserList->where('internet_users.sub_agent_id', $uid);
        }

        // Finalize the query
        $hotspotInternetUserList = $hotspotInternetUserList
            ->where('internet_users.package_type', 'wifi')
            ->groupBy('user_profiles.uid')
            ->orderBy('user_profiles.created_at', 'DESC')
            ->get();

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $hotspotInternetUserList;

        return ResponseWrapper::End($returned_data);
    }

    public function getPaidHotspotInternetUserListISP($uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $uid)->exists();
        $clientType = CorporateClient::where('uid', $uid)->value('client_type');
        $agent = CorporateAgent::where('uid', $uid)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $uid)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        // Fetching MikroTik info based on user type
        if ($client) {
            $mkInfo = CorporateClient::where('uid', $uid)->get();
        } elseif ($agent) {
            $client_id_from_agent = CorporateAgent::where('uid', $uid)->value('client_id');
            $mkInfo = CorporateClient::where('uid', $client_id_from_agent)->get();
        } elseif ($sub_agent) {
            $client_id_from_sub_agent = CorporateSubAgent::where('uid', $uid)->value('client_id');
            $mkInfo = CorporateClient::where('uid', $client_id_from_sub_agent)->get();
        }

        // API Variables
        $ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
        $mkUser = $mkInfo->implode('mikrotik_username', ', ');
        $mkPass = $mkInfo->implode('mikrotik_password', ', ');
        $API = new RouterOsApi();

        // Connecting to MikroTik API
        $pppoeItems = [];
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            $ARRAY = $API->comm('/ip/hotspot/active/print');
            if (isset($ARRAY['!trap'])) {
                foreach ($ARRAY['!trap'] as $error) {
                    Log::error('API Error: ' . ($error['message'] ?? 'Unknown error'));
                }
            } else {
                // Iterate over each item in the $ARRAY
                foreach ($ARRAY as $item) {
                    // Check if the service is 'pppoe'
                    if ($item['uptime'] !== 'null') {
                        // Initialize the uptime variable
                        $uptime = null;

                        // Iterate over each item in $ARRAY again to find a match by username
                        foreach ($ARRAY as $connectedItem) {
                            // Check if the username matches
                            if ($connectedItem['user'] === $item['user']) {
                                // If username matches, set the uptime
                                $uptime = $connectedItem['uptime'];
                                // No need to continue searching, break the loop
                                break;
                            }
                        }

                        // If service is 'pppoe', add it to $pppoeItems array with uptime
                        $pppoeItems[] = [
                            'name' => $item['user'],
                            'status' => $item['radius'],
                            'uptime' => $uptime,
                        ];
                    }
                }
            }
        }

        // Fetch usernames from the first database
        $usernames = [];
        if($client) {
            if($clientType === 'corporate'){
                $usernames = WifiDbUserInfoClient::where('client_id', $uid)->pluck('username')->toArray();
            }else{
                $usernames = WifiDbUserInfoClient::where('client_id', $uid)->pluck('username')->toArray();
            }
        } elseif($agent) {
            $clientUid = CorporateAgent::where('uid', $uid)->value('client_id');
            $clientType = CorporateClient::where('uid', $clientUid)->value('client_type');
            if($clientType === 'corporate'){
                $usernames = WifiDbUserInfoClient::where('agent_id', $uid)->pluck('username')->toArray();
            }else{
                $usernames = WifiDbUserInfoClient::where('agent_id', $uid)->pluck('username')->toArray();
            }
        } elseif($sub_agent) {
            $clientUid = CorporateSubAgent::where('uid', $uid)->value('client_id');
            $clientType = CorporateClient::where('uid', $clientUid)->value('client_type');
            if($clientType === 'corporate'){
                $usernames = WifiDbUserInfoClient::where('sub_agent_id', $uid)->pluck('username')->toArray();
            }else{
                $usernames = WifiDbUserInfoClient::where('sub_agent_id', $uid)->pluck('username')->toArray();
            }
        }

        // Fetch the latest payment date for each user
        $latestPayments = DB::table('payments')
            ->select('uid', DB::raw('MAX(created_at) as latest_payment_date'))
            ->groupBy('uid');

        // Fetch hotspot Internet User List from the second database
        $hotspotInternetUserList = User::query()
            ->whereIn('auth_id', $usernames)
            ->join('user_profiles', 'users.id', '=', 'user_profiles.uid')
            ->join('internet_users', 'users.id', '=', 'internet_users.uid')
            ->joinSub($latestPayments, 'latest_payments', function ($join) {
                $join->on('users.id', '=', 'latest_payments.uid');
            })
            ->join('payments', function ($join) {
                $join->on('users.id', '=', 'payments.uid')
                    ->on('latest_payments.latest_payment_date', '=', 'payments.created_at');
            })
            ->select(
                'user_profiles.uid',
                'user_profiles.full_name',
                'users.text_password',
                'internet_users.package_id',
                DB::raw("(SELECT package_name FROM internet_package_corporates WHERE id = internet_users.package_id) as package_name"),
                'internet_users.added_by as added_by',
                DB::raw("(SELECT base_role FROM users WHERE users.id = internet_users.added_by) as owner_type"),
                'user_profiles.mobile_number',
                'user_profiles.whatsapp_number',
                'user_profiles.nid',
                'user_profiles.email',
                'user_profiles.division_id',
                'user_profiles.district_id',
                'user_profiles.upazila_id',
                'user_profiles.union_id',
                'user_profiles.village_id',
                'user_profiles.house_no',
                'user_profiles.address',
                'user_profiles.latitude',
                'user_profiles.longitude',
                'user_profiles.address_direction',
                'internet_users.user_type as UserType',
                'payments.created_at as activation_date'
            );


        // Apply additional filtering based on user type
        if ($client) {
            $hotspotInternetUserList->where('internet_users.zone_id', $uid);
        } elseif ($agent) {
            $hotspotInternetUserList->where('internet_users.agent_id', $uid);
        } elseif ($sub_agent) {
            $hotspotInternetUserList->where('internet_users.sub_agent_id', $uid);
        }

        // Finalize the query
        $hotspotInternetUserList = $hotspotInternetUserList
            ->where('payments.transaction_status', 'Completed')
            ->where('internet_users.package_type', 'wifi')
            ->groupBy('user_profiles.uid')
            ->orderBy('user_profiles.created_at', 'DESC')
            ->get();

        // Adding uptime information to broadbandInternetUserList
        $hotspotInternetUserList->transform(function ($user) use ($pppoeItems) {
            $user->uptime = null;
            $user->status = 'Inactive';
            foreach ($pppoeItems as $pppoeItem) {
                if ($user->mobile_number == $pppoeItem['name']) {
                    $user->uptime = $pppoeItem['uptime'];
                    $user->status = 'Active';
                    break;
                }
            }
            return $user;
        });

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $hotspotInternetUserList;

        return ResponseWrapper::End($returned_data);
    }

    // create hotspot user ------
    public function createHotspotInternetUserISP(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $editor_id = $request->get('branch_uid');

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        // Validate the request input
        $validate = $request->validate([
            'full_name' => 'required',
            'mobile_number' => 'required',
        ]);

        if(!$validate){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went, Validation Error!";
            return ResponseWrapper::End($returned_data);
        }

        if($request->get('mobile_number') !== null){
            $mobileNumber = $request->get('mobile_number');
            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $auth_id)->exists()){
                $returned_data['status'] = 'error';
                $returned_data['message'] = "This mobile number is already in use.";
                return ResponseWrapper::End($returned_data);
            } else {
                //create new user
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                $editor_profile = UserProfile::where('uid',$editor_id)->first();

                // data for user_profile table ----------
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $request->get('full_name');
                $userProfile->mobile_number = $request->get('mobile_number');
                $userProfile->email = 'noemail@shadhinwifi.com';
                $userProfile->division_id = $editor_profile->division_id;
                $userProfile->district_id = $editor_profile->district_id;
                $userProfile->upazila_id = $editor_profile->upazila_id;
                $userProfile->union_id = $editor_profile->union_id;
                $userProfile->village_id = $editor_profile->village_id;
                $userProfile->house_no = $editor_profile->house_no;
                $userProfile->address = $editor_profile->address;
                $userProfile->address_direction = $editor_profile->address_direction;
                $userProfile->latitude = $editor_profile->latitude ?? "0.00000";
                $userProfile->longitude = $editor_profile->longitude ?? "0.00000";
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                // data for internet user table ---------
                $internetUser = new InternetUsers();
                $internetUser->uid = $uid;
                $package_name = '';
                $package_id = '';

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }elseif($client){
                    $package_name = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $agent = CorporateSubAgent::where('uid',$editor_id)->value('agent_id');
                    $internetUser->package_id = $package_id;
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $agent;
                    $internetUser->sub_agent_id = $editor_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $internetUser->package_id = $package_id;
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $editor_id;
                }elseif($client){
                    $internetUser->package_id = $package_id;
                    $internetUser->zone_id = $editor_id;
                }

                $internetUser->added_by = $editor_id;

                $internetUser->package_type = 'wifi';
                $internetUser->latitude = $editor_profile->latitude ?? "0.00000";
                $internetUser->longitude = $editor_profile->longitude ?? "0.00000";
                $internetUser->password = $password;
                $internetUser->user_type = 'wifi';
                $internetUser->billing_address = $editor_profile->address;
                $internetUser->save();

                // data for user info table radius database ----------------
                $user_info = new WifiDbUserInfoClient();
                $user_info->username = $request->get('mobile_number');
                $user_info->firstname = $request->get('full_name');
                $user_info->email = 'noemail@shadhinwifi.com';

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $ip_address = CorporateClient::where('uid',$from_sub_agent)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $ip_address = CorporateClient::where('uid',$from_agent)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }elseif($client){
                    $ip_address = CorporateClient::where('uid',$editor_id)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }else{
                    $user_info->ipaddress = '172.16.1.106';
                }

                $user_info->mobilephone = $request->get('mobile_number');
                $user_info->address = $editor_profile->address;

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_sub_agent;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_agent;
                }elseif($client){
                    $user_info->branch = $editor_id;
                }else{
                    $user_info->branch = $request->get('branch');
                }

                $user_info->thana = GeoUpazila::where('id',$editor_profile->upazila_id)->value('en_name');
                $user_info->district = GeoDistrict::where('id',$editor_profile->district_id)->value('en_name');
                $user_info->city = GeoDistrict::where('id',$editor_profile->district_id)->value('en_name');
                $user_info->state = GeoDivision::where('id',$editor_profile->division_id)->value('en_name');
                $user_info->country = 'Bangladesh';
                $user_info->country = '1340';

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $agent = CorporateSubAgent::where('uid',$editor_id)->value('agent_id');
                    $user_info->client_id = $client;
                    $user_info->agent_id = $agent;
                    $user_info->sub_agent_id = $editor_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->client_id = $client;
                    $user_info->agent_id = $editor_id;
                }elseif($client){
                    $user_info->client_id = $editor_id;
                }

                $user_info->creationdate = Carbon::now();
                $user_info->creationby = $editor_id;
                $user_info->updatedate = Carbon::now();
                $user_info->updateby = $editor_id;
                $user_info->save();

                // data for rad acct table radius database ----------------
                $radcheck = new WifiDbRadCheckClient();
                $radcheck->username = $request->get('mobile_number');
                $radcheck->attribute = "Cleartext-Password";
                $radcheck->op = ":=";
                $radcheck->value = $password;
                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $radcheck->branch = $from_sub_agent;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $radcheck->branch = $from_agent;
                }elseif($client){
                    $radcheck->branch = $editor_id;
                }else{
                    $radcheck->branch = $request->get('branch');
                }
                $radcheck->updatetime = Carbon::now();
                $radcheck->save();

                // data for Rad User Group table radius database ----------------
                $radUserGroup = new WifiDbRadUserGroupClient();
                $radUserGroup->username = $request->get('mobile_number');

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $radUserGroup->groupname = CorporateClient::where('uid',$from_sub_agent)->value('hotspot_profile');
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $radUserGroup->groupname = CorporateClient::where('uid',$from_agent)->value('hotspot_profile');
                }elseif($client){
                    $radUserGroup->groupname = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                }else{
                    $radUserGroup->groupname = $request->get('hotspot_profile');
                }

                $radUserGroup->priority = "0";
                $radUserGroup->save();
                
                $package = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');

                $expiryDate = '';
                if($expiration < 1440){
                    $expiryDate = Carbon::now()->addMinutes($expiration)->format('Y-m-d\TH:i:s');
                }else{
                    $expiryDate = Carbon::now()->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
                }

                // data for Rad User Group table radius database ----------------
                $radUserGroup = new WifiDbRadReplyClient();
                $radUserGroup->username = $request->get('mobile_number');
                $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
                $radUserGroup->op = ":=";
                $radUserGroup->value = $expiryDate;
                $radUserGroup->save();

                // For response ------
                $internet_table = InternetUsers::where('uid',$uid)->first();
                $user_pro_table = UserProfile::where('uid',$uid)->first();

                $mobile = $request->get('mobile_number');
                $smsText = "আপনার স্বাধীন ওয়াইফাই ইউজার আইডি: ".$mobile." এবং পাসওয়ার্ড: " . $password;
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);

                // Final Results ---------
                $returned_data['status'] = 'success';
                $returned_data['message'] = 'You added '.$request->get('mobile_number').' as a free hotspot internet user for 30 Minutes!';
                $returned_data['results'] = [
                    'uid' => $uid,
                    'full_name' => $request->get('full_name'),
                    'text_password' => User::where('id',$uid)->value('text_password'),
                    'package_id' => $package_id,
                    'package_name' => $package_name,
                    'added_by' => $internet_table->added_by,
                    'owner_type' => User::where('id',$editor_id)->value('base_role'),
                    'mobile_number' => $request->get('mobile_number'),
                    'whatsapp_number' => $user_pro_table->whatsapp_number,
                    'email' => $user_pro_table->email,
                    'nid' => $user_pro_table->nid,
                    'division_id' => $user_pro_table->division_id,
                    'district_id' => $user_pro_table->district_id,
                    'upazila_id' => $user_pro_table->upazila_id,
                    'union_id' => $user_pro_table->union_id,
                    'village_id' => $user_pro_table->village_id,
                    'house_no' => $user_pro_table->house_no,
                    'address' => $user_pro_table->address,
                    'latitude' => $user_pro_table->latitude,
                    'longitude' => $internet_table->longitude,
                    'created_at' => $user_pro_table->created_at,
                    'address_direction' => $user_pro_table->address_direction,
                    'UserType' => $internet_table->user_type,
                ];
                return ResponseWrapper::End($returned_data);
            }
        }
    }

    // update internet user -------------
    public function updateHotspotInternetUserISP(Request $request, $editor_id, $internet_user_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();
        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $internet_user = User::where('id', $internet_user_id)->exists();
        if (!$internet_user) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        $validated = $request->validate([
            'full_name' => 'required',
            'password' => 'required',
            // 'whatsapp_number' => 'required',
            // 'email' => 'required|email',
            // 'gender' => 'required',
            // 'profession' => 'required',
            // 'nid' => 'required',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'house' => 'required',
            // 'post_code' => 'required',
            // 'address_direction' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Update user in user table
        $user = User::where('id', $internet_user_id)->first();
        if ($user) {
            $user->update([
                'password' => Hash::make($request->get('password')),
                'text_password' => $request->get('password'),
            ]);
        }

        // Update user profile
        $userProfile = UserProfile::where('uid', $internet_user_id)->first();
        if ($userProfile) {
            $userProfile->update([
                'full_name' => $request->get('full_name'),
                'whatsapp_number' => $request->get('whatsapp_number'),
                'email' => $request->get('email'),
                'gender' => $request->get('gender'),
                'nid' => $request->get('nid'),
                // 'profession' => $request->get('profession'),
                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'union_id' => $request->get('union'),
                'village_id' => $request->get('village'),
                'house_no' => $request->get('house'),
                'address' => $request->get('address'),
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'address_direction' => $request->get('address_direction'),
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went wrong, Data not stored in User Profile table!";
            return ResponseWrapper::End($returned_data);
        }

        $internetUser = InternetUsers::where('uid', $internet_user_id)->first();
        if ($internetUser) {
            $internetUser->update([
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'password' => $request->get('password'),
                'billing_address' => $request->get('address')
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went wrong, Data not stored in Internet Users table!";
            return ResponseWrapper::End($returned_data);
        }

        $user_info = WifiDbUserInfoClient::where('username', $request->get('mobile_number'))->first();
        if ($user_info) {
            $user_info->username = $request->get('mobile_number');
            $user_info->firstname = $request->get('full_name');
            $user_info->email = $request->get('email');
            $user_info->thana = GeoUpazila::where('id',$request->get('upazila'))->value('en_name');
            $user_info->district = GeoDistrict::where('id',$request->get('district'))->value('en_name');
            $user_info->city = GeoDistrict::where('id',$request->get('district'))->value('en_name');
            $user_info->state = GeoDivision::where('id',$request->get('division'))->value('en_name');
            $user_info->address = $request->get('address');
            $user_info->updatedate = Carbon::now();
            $user_info->updateby = $editor_id;
            $user_info->save();
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went wrong, Data not stored in Subscriber Info table!";
            return ResponseWrapper::End($returned_data);
        }

        $radcheck = WifiDbRadCheckClient::where('username', $request->get('mobile_number'))->first();
        if ($radcheck) {
            $radcheck->value = $request->get('password');
            $radcheck->updatetime = Carbon::now();
            $radcheck->save();
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went wrong, Data not stored in RadCheck table!";
            return ResponseWrapper::End($returned_data);
        }

        $user_mobile = $request->get('mobile_number');
        $password = $request->get('password');

        $returned_data['status'] = 'success';
        $returned_data['message'] = "আপনি ইন্টারনেট ইউজারের তথ্য আপডেট করেছেন। ইন্টারনেট ইউজারের Username : " . $user_mobile . " এবং Password :" . $password;
        return ResponseWrapper::End($returned_data);
    }

    // delete internet user  -------------
    public function deleteHotspotInternetUserISP($editor_id, $internet_user_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();
        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $internet_user = User::where('id', $internet_user_id)->exists();
        if (!$internet_user) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        $internet_user_mobile_number = User::where('id',$internet_user_id)->value('auth_id');
        $is_access = WifiDbUserInfoClient::where('client_id',$editor_id)->where('username', $internet_user_mobile_number)->exists();
        if(!$is_access){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You are not allowed to delete this user!';
            return ResponseWrapper::End($returned_data);
        }

        $profileDeleted = UserProfile::where('uid', $internet_user_id)->delete();
        $internetUser = InternetUsers::where('uid', $internet_user_id)->delete();
        $userInfo = WifiDbUserInfoClient::where('username', $internet_user_mobile_number)->delete();
        $radCheck = WifiDbRadCheckClient::where('username', $internet_user_mobile_number)->delete();
        $radReply = WifiDbRadReplyClient::where('username', $internet_user_mobile_number)->delete();
        $radUserGroup = WifiDbRadUserGroupClient::where('username', $internet_user_mobile_number)->delete();
        $userDeleted = User::where('id', $internet_user_id)->delete();

        if ($userDeleted && $profileDeleted && $internetUser && $userInfo && $radCheck && $radReply && $radUserGroup) {
            $returned_data['results'] = true;
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Hotspot User deleted successfully!";
            return ResponseWrapper::End($returned_data);
        } else {
            $returned_data['results'] = false;
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Try again something went wrong!";
            return ResponseWrapper::End($returned_data);
        }
    }

    // bill entry & package update --------
    public function billEntryHotspotInternetUserISP(Request $request, $editor_id, $internet_user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $internet_user = User::where('id', $internet_user_id)->exists();
        if (!$internet_user) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        $paymentExists = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->exists();
        $expiryDate = '';
        if ($paymentExists) {
            // Get the latest payment date
            $latestPaymentDate = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('created_at');
            $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
            
            // $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s'); 
            //$expiration = round(InternetPackageCorporate::where('package_name',$package)->value('expiration') / 1440);
            $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
            if($expiration < 1440){
                $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
                $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
            }else{
                $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
            }
            

            // Add 30 days to the latest payment date
            //$expiryDate = Carbon::parse($latestPaymentDate)->addDays($expiration);

            // Check if today is before the expiry date
            //Log::info($expiryDate);
            

            if (Carbon::now()->lessThanOrEqualTo($expiryDate)) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "This user's package is not expired!";
                return ResponseWrapper::End($returned_data);
            }
        }

        $user_type = $client ? 'client' : ($agent ? 'agent' : 'sub_agent');

        // Panel Money check
        $user_model = $user_type === 'client' ? CorporateClient::class : ($user_type === 'agent' ? CorporateAgent::class : CorporateSubAgent::class);
        $pre_balance = $user_model::where('uid', $editor_id)->value('balance');
        // $package_price = InternetPackage::where('id', $request->get('package'))->value('price');
        $package_estimated_price = (int) $request->get('package_price');

        if ($pre_balance <= $package_estimated_price) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Insufficient Balance';
            return ResponseWrapper::End($returned_data);
        }

        $user_model::where('uid', $editor_id)->update([
            'balance' => $pre_balance - $package_estimated_price
        ]);

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');
        $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

        $internet_user = InternetUsers::where('uid',$internet_user_id)->first();
        if($internet_user){
            $internet_user->update([
                'package_id' => $request->get('package_id'),
                'connection_status' => 'Active',
            ]);
        }

        // Save Payment
        $paymentForBackend = new Payment();
        $paymentForBackend->uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
        if($client){
            $paymentForBackend->zone_id = $editor_id;
        }elseif($agent){
            $client_id = CorporateAgent::where('uid',$editor_id)->value('client_id');
            $paymentForBackend->zone_id = $client_id;
        }elseif($sub_agent){
            $client_id = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
            $paymentForBackend->zone_id = $client_id;
        }
        $paymentForBackend->vendor_name = 'panel_money';
        $paymentForBackend->trx_id = $trxID;
        $paymentForBackend->invoice_number = $invoiceID;
        $paymentForBackend->amount = $package_estimated_price;
        $paymentForBackend->payment_id = $paymentID;
        $paymentForBackend->process_status = '1';
        $paymentForBackend->purpose = 'hotspot_internet_bill_payment';
        $paymentForBackend->package = $request->get('package');
        $paymentForBackend->transaction_status = 'Completed';

        if (!$paymentForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Transaction
        $transactionForBackend = new Transaction();
        $transactionForBackend->trx_id = $trxID;
        $transactionForBackend->trx_type = 'payment';
        $transactionForBackend->plus_minus = 'minus';
        $transactionForBackend->sender_uid = $editor_id;
        $transactionForBackend->receiver_uid = $editor_id;
        $transactionForBackend->method = 'panel_money';
        $transactionForBackend->amount = $package_estimated_price;
        $transactionForBackend->purpose = 'hotspot_internet_bill_payment';

        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Affiliate History
        $affiliate_history = new AffiliateHistory();
        $affiliate_history->affiliator_uid = $editor_id;
        $affiliate_history->product_type = 'internet_package';
        $affiliate_history->product_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $affiliate_history->status = 'pending';

        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();

            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $agent_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($agent) {
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();

            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } else {
                $commission = CorporateAgent::where('uid', $editor_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $editor_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($sub_agent) {
            $sub_agent_commission = CorporateSubAgent::where('uid', $editor_id)->value('commission');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');

            // agent id
            $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
            $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

            // commission & commission amount for agent
            $agent_commission = $agent_main_commission - $sub_agent_commission;
            $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

            // commission & commission amount for sub-agent
            $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
            $affiliate_history->commission_amount = $sub_agent_commission_amount;

            UserProfile::where('uid', $editor_id)->update([
                'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
            ]);

            UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
            ]);
        }

        if (!$affiliate_history->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Agent Commission Breakdown
        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $sub_agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            }
        } elseif ($agent) {
            $agent_id = InternetUsers::where('uid',$internet_user_id)->value('agent_id');
            $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
            $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
            $new_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = $editor_id;
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        } elseif ($sub_agent) {
            $sub_agent_id = InternetUsers::where('uid',$internet_user_id)->value('sub_agent_id');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
            $commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
            $sub_agent_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        }

        // Save Radius Payment
        $paymentForRadius = new WifiDbPaymentClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $package_estimated_price;
        $paymentForRadius->created_at = Carbon::now();

        if (!$paymentForRadius->save()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // bkash info -----
        $paymentForRadius = new WifiDbBkashInfoClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $package_estimated_price;
        $paymentForRadius->payment_id = $paymentID;
        $paymentForRadius->transaction_id = $trxID;
        $paymentForRadius->status = 'Success';
        $paymentForRadius->created_at = Carbon::now();

        if (!$paymentForRadius->save()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $latestPaymentDate = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('created_at');
        $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
        $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
        if($expiration < 1440){
            $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
            $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
        }else{
            $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
        }
        

        //WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->update(['value' => $expiryDate]);
        // WifiDbRadUserGroupClient::where('username', '=', $mobile)->update(array('groupname' => $package));
        //WifiDbRadUserGroupClient::where('username', '=', $$request->get('mobile_number'))->update(array('groupname' => $request->get('package')));
        $expDateExist = WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->exists();
        if($expDateExist){
            WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->update(['value' => $expiryDate]);
        }else{
            $radUserGroup = new WifiDbRadReplyClient();
            $radUserGroup->username = $request->get('mobile_number');
            $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
            $radUserGroup->op = ":=";
            $radUserGroup->value = $expiryDate;
            $radUserGroup->save();
        }
        
        
        WifiDbRadUserGroupClient::where('username',$request->get('mobile_number'))->update(['groupname' => $package]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Payment processed successfully';
        $returned_data['results'] = [
            'transaction_id' => $trxID,
            'invoice_id' => $invoiceID,
            'payment_id' => $paymentID,
            'full_name' => UserProfile::where('uid',$internet_user_id)->value('full_name'),
            'mobile_number' => $request->get('mobile_number'),
            'package' => $request->get('package'),
            'final_price' => $request->get('package_price'),
            'payment_method' => 'Panel Money',
            'payment_date' => Carbon::now()
        ];
        return ResponseWrapper::End($returned_data);
    }

    // create hotspot user ------
    public function createHotspotInternetUserISPExisting(Request $request, $client_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $editor_id = $client_id;

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        if( !$client && !$agent && !$sub_agent){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You are not allowed!';
            return ResponseWrapper::End($returned_data);
        }

        // Validate the request input
        $validate = $request->validate([
            'full_name' => 'required',
            'mobile_number' => 'required',
        ]);

        if(!$validate){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went, Validation Error!";
            return ResponseWrapper::End($returned_data);
        }

        $uid = $request->get('uid');
        $generatedPassword = (new \App\Classes\CustomHelpers)->generate_new_password();

        $isUserActive = InternetUsers::where('uid',$uid)->value('connection_status');
        if($isUserActive === 'Active'){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "This user is under another zone!";
            return ResponseWrapper::End($returned_data);
        } else{
                // Variables ---
                $package_name = '';
                $package_id = '';
                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }elseif($client){
                    $package_name = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }

                // data for internet user table ---------
                $internetUser = InternetUsers::where('uid',$uid)->first();
                $internetUser->uid = $uid;
                $internetUser->package_id = $package_id;

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $agent = CorporateSubAgent::where('uid',$editor_id)->value('agent_id');
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $agent;
                    $internetUser->sub_agent_id = $editor_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $editor_id;
                }elseif($client){
                    $internetUser->zone_id = $editor_id;
                }

                $internetUser->added_by = $editor_id;
                $internetUser->package_type = 'wifi';
                $internetUser->latitude = $request->get('latitude') ?? "0.00000";
                $internetUser->longitude = $request->get('longitude') ?? "0.00000";
                $internetUser->password = $generatedPassword;
                $internetUser->user_type = 'wifi';
                $internetUser->billing_address = $request->get('address');
                $internetUser->save();

                // data for user info table radius database ----------------
                $user_info = new WifiDbUserInfoClient();
                $user_info->username = $request->get('mobile_number');
                $user_info->firstname = $request->get('full_name');
                $user_info->email = 'noemail@shadhinwifi.com';

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $ip_address = CorporateClient::where('uid',$from_sub_agent)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $ip_address = CorporateClient::where('uid',$from_agent)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }elseif($client){
                    $ip_address = CorporateClient::where('uid',$editor_id)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }else{
                    $user_info->ipaddress = '172.16.1.106';
                }

                $user_info->mobilephone = $request->get('mobile_number');
                $user_info->address = $request->get('address');

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_sub_agent;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_agent;
                }elseif($client){
                    $user_info->branch = $editor_id;
                }else{
                    $user_info->branch = $request->get('branch');
                }

                $user_info->thana = GeoUpazila::where('id', $request->get('upazila'))->value('en_name') ?? 'not listed';
                $user_info->district = GeoDistrict::where('id', $request->get('district'))->value('en_name');
                $user_info->city = GeoDistrict::where('id', $request->get('district'))->value('en_name');
                $user_info->state = GeoDivision::where('id', $request->get('division'))->value('en_name');
                $user_info->country = 'Bangladesh';
                $user_info->country = '1340';

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $agent = CorporateSubAgent::where('uid',$editor_id)->value('agent_id');
                    $user_info->client_id = $client;
                    $user_info->agent_id = $agent;
                    $user_info->sub_agent_id = $editor_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->client_id = $client;
                    $user_info->agent_id = $editor_id;
                }elseif($client){
                    $user_info->client_id = $editor_id;
                }

                $user_info->creationdate = Carbon::now();
                $user_info->creationby = $editor_id;
                $user_info->updatedate = Carbon::now();
                $user_info->updateby = $editor_id;
                $user_info->save();

                // data for rad acct table radius database ----------------
                $radcheck = new WifiDbRadCheckClient();
                $radcheck->username = $request->get('mobile_number');
                $radcheck->attribute = "Cleartext-Password";
                $radcheck->op = ":=";
                $radcheck->value = $generatedPassword;
                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_sub_agent;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_agent;
                }elseif($client){
                    $user_info->branch = $editor_id;
                }else{
                    $user_info->branch = $request->get('branch');
                }
                $radcheck->updatetime = Carbon::now();
                $radcheck->save();

                // data for Rad User Group table radius database ----------------
                $radUserGroup = new WifiDbRadUserGroupClient();
                $radUserGroup->username = $request->get('mobile_number');

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $radUserGroup->groupname = CorporateClient::where('uid',$from_sub_agent)->value('hotspot_profile');
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $radUserGroup->groupname = CorporateClient::where('uid',$from_agent)->value('hotspot_profile');
                }elseif($client){
                    $radUserGroup->groupname = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                }else{
                    $radUserGroup->groupname = $request->get('hotspot_profile');
                }

                $radUserGroup->priority = "0";
                $radUserGroup->save();


                // data for Rad User Group table radius database ----------------
                $package = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
                $expiryDate = '';
                if($expiration < 1440){
                    $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
                    $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
                }else{
                    $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
                }
        
                $radUserGroup = new WifiDbRadReplyClient();
                $radUserGroup->username = $request->get('mobile_number');
                $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
                $radUserGroup->op = ":=";
                $radUserGroup->value = $expiryDate;
                $radUserGroup->save();

                // Final Results ---------
                $returned_data['status'] = 'success';
                $returned_data['message'] = 'You added '.$request->get('mobile_number').' as a free hotspot internet user for 30 Minutes!';
                $returned_data['results'] = [
                    'user_id' => $uid,
                    'user_name' => $request->get('full_name'),
                    'user_mobile_number' => $request->get('mobile_number'),
                    'package_id' => $package_id,
                ];
                return ResponseWrapper::End($returned_data);
        }
    }

    // create hotspot user ------
    public function createHotspotInternetUserISPClient(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $editor_id = $request->get('branch_uid');

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        // Validate the request input
        $validate = $request->validate([
            'full_name' => 'required|string|max:50',
            'mobile_number' => 'required|numeric|digits:11',
        ]);

        if(!$validate){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went, Validation Error!";
            return ResponseWrapper::End($returned_data);
        }

        if($request->get('mobile_number') !== null){
            $mobileNumber = $request->get('mobile_number');
            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $mobileNumber)->exists()){
                $returned_data['status'] = 'error';
                $returned_data['message'] = "This mobile number is already in use.";
                return ResponseWrapper::End($returned_data);
            } else {
                //create new user
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                $editor_profile = UserProfile::where('uid',$editor_id)->first();

                

                // data for user_profile table ----------
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $request->get('full_name');
                $userProfile->mobile_number = $request->get('mobile_number');
                $userProfile->email = 'noemail@shadhinwifi.com';
                $userProfile->division_id = $editor_profile->division_id;
                $userProfile->district_id = $editor_profile->district_id;
                $userProfile->upazila_id = $editor_profile->upazila_id;
                $userProfile->union_id = $editor_profile->union_id;
                $userProfile->village_id = $editor_profile->village_id;
                $userProfile->house_no = $editor_profile->house_no;
                $userProfile->address = $editor_profile->address;
                $userProfile->address_direction = $editor_profile->address_direction;
                $userProfile->latitude = $editor_profile->latitude ?? "0.00000";
                $userProfile->longitude = $editor_profile->longitude ?? "0.00000";
                $userProfile->device_info = json_encode(["brand"=>"panel"]);
                $userProfile->save();

                // data for internet user table ---------
                $internetUser = new InternetUsers();
                $internetUser->uid = $uid;
                $package_name = '';
                $package_id = '';

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }elseif($client){
                    $package_name = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                    $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
                }

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $agent = CorporateSubAgent::where('uid',$editor_id)->value('agent_id');
                    $internetUser->package_id = $package_id;
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $agent;
                    $internetUser->sub_agent_id = $editor_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $internetUser->package_id = $package_id;
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $editor_id;
                }elseif($client){
                    $internetUser->package_id = $package_id;
                    $internetUser->zone_id = $editor_id;
                }

                $internetUser->added_by = $editor_id;
                $internetUser->package_type = 'wifi';
                $internetUser->latitude = $editor_profile->latitude ?? "0.00000";
                $internetUser->longitude = $editor_profile->longitude ?? "0.00000";
                $internetUser->password = $password;
                $internetUser->user_type = 'wifi';
                $internetUser->billing_address = $editor_profile->address;
                $internetUser->save();

                // data for user info table radius database ----------------
                $user_info = new WifiDbUserInfoClient();
                $user_info->username = $request->get('mobile_number');
                $user_info->firstname = $request->get('full_name');
                $user_info->email = 'noemail@shadhinwifi.com';

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $ip_address = CorporateClient::where('uid',$from_sub_agent)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $ip_address = CorporateClient::where('uid',$from_agent)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }elseif($client){
                    $ip_address = CorporateClient::where('uid',$editor_id)->value('mikrotik_ip');
                    $user_info->ipaddress = $ip_address;
                }else{
                    $user_info->ipaddress = '172.16.1.106';
                }

                $user_info->mobilephone = $request->get('mobile_number');
                $user_info->address = $editor_profile->address;

                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_sub_agent;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->branch = $from_agent;
                }elseif($client){
                    $user_info->branch = $editor_id;
                }else{
                    $user_info->branch = $request->get('branch');
                }

                $user_info->thana = GeoUpazila::where('id',$editor_profile->upazila_id)->value('en_name');
                $user_info->district = GeoDistrict::where('id',$editor_profile->district_id)->value('en_name');
                $user_info->city = GeoDistrict::where('id',$editor_profile->district_id)->value('en_name');
                $user_info->state = GeoDivision::where('id',$editor_profile->division_id)->value('en_name');
                $user_info->country = 'Bangladesh';
                $user_info->country = '1340';

                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $agent = CorporateSubAgent::where('uid',$editor_id)->value('agent_id');
                    $user_info->client_id = $client;
                    $user_info->agent_id = $agent;
                    $user_info->sub_agent_id = $editor_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $user_info->client_id = $client;
                    $user_info->agent_id = $editor_id;
                }elseif($client){
                    $user_info->client_id = $editor_id;
                }

                $user_info->creationdate = Carbon::now();
                $user_info->creationby = $editor_id;
                $user_info->updatedate = Carbon::now();
                $user_info->updateby = $editor_id;
                $user_info->save();

                // data for rad acct table radius database ----------------
                $radcheck = new WifiDbRadCheckClient();
                $radcheck->username = $request->get('mobile_number');
                $radcheck->attribute = "Cleartext-Password";
                $radcheck->op = ":=";
                $radcheck->value = $password;
                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $radcheck->branch = $from_sub_agent;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $radcheck->branch = $from_agent;
                }elseif($client){
                    $radcheck->branch = $editor_id;
                }else{
                    $radcheck->branch = $request->get('branch');
                }
                $radcheck->updatetime = Carbon::now();
                $radcheck->save();

                // data for Rad User Group table radius database ----------------
                $radUserGroup = new WifiDbRadUserGroupClient();
                $radUserGroup->username = $request->get('mobile_number');
                if($sub_agent){
                    $from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                    $group_name = CorporateClient::where('uid',$from_sub_agent)->value('hotspot_profile');
                    $radUserGroup->groupname = $group_name;
                }elseif($agent){
                    $from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
                    $group_name = CorporateClient::where('uid',$from_agent)->value('hotspot_profile');
                    $radUserGroup->groupname = $group_name;
                }elseif($client){
                    $group_name = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                    $radUserGroup->groupname = $group_name;
                }else{
                    $radUserGroup->groupname = $request->get('hotspot_profile');
                }
                $radUserGroup->priority = "0";
                $radUserGroup->save();

                $package = CorporateClient::where('uid',$editor_id)->value('hotspot_profile');
                $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
                $expiryDate = '';
                if($expiration < 1440){
                    $expiryDate = Carbon::now()->addMinutes($expiration)->format('Y-m-d\TH:i:s');
                }else{
                    $expiryDate = Carbon::now()->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
                }

                // data for Rad User Group table radius database ----------------
                $radUserGroup = new WifiDbRadReplyClient();
                $radUserGroup->username = $request->get('mobile_number');
                $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
                $radUserGroup->op = ":=";
                $radUserGroup->value = $expiryDate;
                $radUserGroup->save();

                // For response ------
                $internet_table = InternetUsers::where('uid',$uid)->first();
                $user_pro_table = UserProfile::where('uid',$uid)->first();

                $mobile = $request->get('mobile_number');
                Log::info($editor_id);

                if($editor_id === '59339') {
                    $smsText = "Fly Far WiFi Access! Your WiFi Access Details are ID: ".$mobile.", Password: ".$password.". Validity: 6 hours from activation. Enjoy your time with us!";
                    $send_sms = (new \App\Classes\CustomHelpers)->sendSmsNovocom($smsText, $mobile);
                } else {
                    $smsText = "আপনার স্বাধীন ওয়াইফাই ইউজার আইডি: ".$mobile." এবং পাসওয়ার্ড: " . $password;
                    $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);
                }
                
                // Final Results ---------
                $returned_data['status'] = 'success';
                $returned_data['message'] = 'You added '.$request->get('mobile_number').' as a free hotspot internet user for 30 Minutes!';
                $returned_data['results'] = [
                    'uid' => $uid,
                    'full_name' => $request->get('full_name'),
                    'text_password' => User::where('id',$uid)->value('text_password'),
                    'package_id' => $package_id,
                    'package_name' => $package_name,
                    'added_by' => $internet_table->added_by,
                    'owner_type' => User::where('id',$editor_id)->value('base_role'),
                    'mobile_number' => $request->get('mobile_number'),
                    'whatsapp_number' => $user_pro_table->whatsapp_number,
                    'email' => $user_pro_table->email,
                    'nid' => $user_pro_table->nid,
                    'division_id' => $user_pro_table->division_id,
                    'district_id' => $user_pro_table->district_id,
                    'upazila_id' => $user_pro_table->upazila_id,
                    'union_id' => $user_pro_table->union_id,
                    'village_id' => $user_pro_table->village_id,
                    'house_no' => $user_pro_table->house_no,
                    'address' => $user_pro_table->address,
                    'latitude' => $user_pro_table->latitude,
                    'longitude' => $internet_table->longitude,
                    'created_at' => $user_pro_table->created_at,
                    'address_direction' => $user_pro_table->address_direction,
                    'UserType' => $internet_table->user_type,
                ];
                return ResponseWrapper::End($returned_data);
            }
        }
    }

    // bkash info table data for frc/ucl
    public function createBkashInfo(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // bkash info -----
        $paymentForRadius = new WifiDbBkashInfoClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $request->get('pacakge_price');
        $paymentForRadius->payment_id = $request->get('payment_id');
        $paymentForRadius->transaction_id = $request->get('trx_id');
        $paymentForRadius->status = 'Pending';
        $paymentForRadius->created_at = Carbon::now();

        if (!$paymentForRadius->save()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Final Results ---------
        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    // hotspot payment from frc/ucl - transactionUpdate.php
    public function createPaymentFromPaymentPortal(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internet_user_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $editor_id = InternetUsers::where('uid',$internet_user_id)->value('added_by');

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $internet_user = User::where('id', $internet_user_id)->exists();
        if (!$internet_user) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');
        $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

        $paymentIdBkash = $request->get('payment_id');
        $trxIDBkash = $request->get('trx_id');
        $package_estimated_price = WifiDbBkashInfoClient::where('payment_id',$paymentIdBkash)->value('amount');
        $package_id = InternetPackageCorporate::where('price', $package_estimated_price)->value('id');
        $package = InternetPackageCorporate::where('price', $package_estimated_price)->value('package_name');

        // bkash info -----
        $wifiBkash = WifiDbBkashInfoClient::where('payment_id',$paymentIdBkash)->first();
        if($wifiBkash){
            $wifiBkash->update([
                'transaction_id' => $trxIDBkash,
                'status' => 'Success'
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong, Try again!';
            return ResponseWrapper::End($returned_data);
        }

        if($internet_user){
            $internet_user->update([
                'package_id' => $package_id,
                'connection_status' => 'Active',
            ]);
        }

        // Save Payment
        $paymentForBackend = new Payment();
        $paymentForBackend->uid = $internet_user_id;
        if($client){
            $paymentForBackend->zone_id = $editor_id;
        }elseif($agent){
            $client_id = CorporateAgent::where('uid',$editor_id)->value('client_id');
            $paymentForBackend->zone_id = $client_id;
        }elseif($sub_agent){
            $client_id = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
            $paymentForBackend->zone_id = $client_id;
        }
        $paymentForBackend->vendor_name = 'panel_money';
        $paymentForBackend->trx_id = $trxID;
        $paymentForBackend->invoice_number = $invoiceID;
        $paymentForBackend->amount = $package_estimated_price;
        $paymentForBackend->payment_id = $paymentID;
        $paymentForBackend->process_status = '1';
        $paymentForBackend->purpose = 'hotspot_internet_bill_payment';
        $paymentForBackend->package = $package;
        $paymentForBackend->transaction_status = 'Completed';

        if (!$paymentForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Transaction
        $transactionForBackend = new Transaction();
        $transactionForBackend->trx_id = $trxID;
        $transactionForBackend->trx_type = 'payment';
        $transactionForBackend->plus_minus = 'minus';
        $transactionForBackend->sender_uid = $editor_id;
        $transactionForBackend->receiver_uid = $editor_id;
        $transactionForBackend->method = 'portal_payment';
        $transactionForBackend->amount = $package_estimated_price;
        $transactionForBackend->purpose = 'hotspot_internet_bill_payment';

        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Affiliate History
        $affiliate_history = new AffiliateHistory();
        $affiliate_history->affiliator_uid = $editor_id;
        $affiliate_history->product_type = 'internet_package';
        $affiliate_history->product_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $affiliate_history->status = 'pending';

        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $agent_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($agent) {
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } else {
                $commission = CorporateAgent::where('uid', $editor_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $editor_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($sub_agent) {
            $sub_agent_commission = CorporateSubAgent::where('uid', $editor_id)->value('commission');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');

            // agent id
            $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
            $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

            // commission & commission amount for agent
            $agent_commission = $agent_main_commission - $sub_agent_commission;
            $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

            // commission & commission amount for sub-agent
            $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
            $affiliate_history->commission_amount = $sub_agent_commission_amount;

            UserProfile::where('uid', $editor_id)->update([
                'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
            ]);

            UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
            ]);
        }

        if (!$affiliate_history->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Agent Commission Breakdown
        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $sub_agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            }
        } elseif ($agent) {
            $agent_id = InternetUsers::where('uid',$internet_user_id)->value('agent_id');
            $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
            $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
            $new_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = $editor_id;
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        } elseif ($sub_agent) {
            $sub_agent_id = InternetUsers::where('uid',$internet_user_id)->value('sub_agent_id');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
            $commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
            $sub_agent_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        }

        // Save Radius Payment
        $paymentForRadius = new WifiDbPaymentClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $package_estimated_price;
        $paymentForRadius->created_at = Carbon::now();

        if (!$paymentForRadius->save()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $latestPaymentDate = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('created_at');
        $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
        $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
        $expiryDate = '';
        if($expiration < 1440){
            $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
            $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
        }else{
            $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
        }

        $expDateExist = WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->exists();
        if($expDateExist){
            WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->update(['value' => $expiryDate]);
        }else{
            $radUserGroup = new WifiDbRadReplyClient();
            $radUserGroup->username = $request->get('mobile_number');
            $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
            $radUserGroup->op = ":=";
            $radUserGroup->value = $expiryDate;
            $radUserGroup->save();
        }
        WifiDbRadUserGroupClient::where('username',$request->get('mobile_number'))->update(['groupname' => $package]);

        $mobile = $request->get('mobile_number');

        $smsText = "আপনার ইন্টারনেটের মেয়াদ ". Carbon::now()->addDays(30) . " পর্যন্ত বাড়ানো হয়েছে!";
        $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Payment processed successfully';
        return ResponseWrapper::End($returned_data);
    }

    // payment bkash tokenize - create
    public function bkashTokenizePaymentCreate(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internet_user_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $editor_id = InternetUsers::where('uid',$internet_user_id)->value('added_by') ?? 0000;

        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $package_estimated_price = $request->get('amount');
        $package = $request->get('package');
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = $request->get('invoice_number');
        $paymentID = $request->get('payment_id');

        $existToken = PaymentToken::where('invoice_number', $invoiceID)->where('vendor_name', 'bkash')->first();
        if(empty($existToken)){
            $paymentToken = new PaymentToken();
            $paymentToken->vendor_name = 'bkash';
            $paymentToken->invoice_number = $invoiceID;
            $paymentToken->token = $request->get('token');
            $paymentToken->save();
        } else {
            $paymentToken = PaymentToken::find($existToken['id']);
            $paymentToken->token = $request->get('token');
            $paymentToken->invoice_number = $invoiceID;
            $paymentToken->update();
        }

        // ---------------------- db save data ----------------------------
        $payment = new Payment();
        $payment->uid = $internet_user_id;
        if($client){
            $payment->zone_id = $editor_id;
        }elseif($agent){
            $client_id = CorporateAgent::where('uid',$editor_id)->value('client_id');
            $payment->zone_id = $client_id;
        }elseif($sub_agent){
            $client_id = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
            $payment->zone_id = $client_id;
        }
        $payment->is_test_mode = 0;
        $payment->vendor_name = 'bkash';
        $payment->trx_id = $trxID;
        $payment->invoice_number = $invoiceID;
        $payment->amount = $package_estimated_price;
        $payment->payment_id = $paymentID;
        $payment->process_status = '1';
        $payment->purpose = $request->get('purpose') ?? 'hotspot_internet_bill_payment_client_bkash';
        $payment->package = $package;
        $payment->transaction_status = 'pending';
        $payment->save();

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    // bkash payment tokennize
    public function createPaymentFromPaymentPortalBkashTokenize(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internet_user_id = Payment::where('payment_id', $request->get('payment_id'))->value('uid');
        if (!$internet_user_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        $editor_id = InternetUsers::where('uid',$internet_user_id)->value('added_by');

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        // Generate the transaction ID and invoice ID
        $trxID = $request->get('trx_id');
        $paymentID = $request->get('payment_id');

        $package_estimated_price = $request->get('package_price');
        $package_id = InternetPackageCorporate::where('price', $package_estimated_price)->value('id');
        $package = InternetPackageCorporate::where('price', $package_estimated_price)->value('package_name');

        // bkash info -----
        $paymentForRadius = new WifiDbBkashInfoClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $request->get('package_price');
        $paymentForRadius->payment_id = $request->get('payment_id');
        $paymentForRadius->transaction_id = $request->get('trx_id');
        $paymentForRadius->status = 'Success';
        $paymentForRadius->created_at = Carbon::now();

        if (!$paymentForRadius->save()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $internet_users = InternetUsers::where('uid', $internet_user_id)->first();
        if($internet_users){
            $internet_users->update([
                'package_id' => $package_id,
                'connection_status' => 'active',
                'updated_at' => Carbon::now(),
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        // Update Payment
        $paymentForBackend = Payment::where('payment_id', $paymentID)->first();
        if($paymentForBackend){
            $paymentForBackend->update([
                'trx_id' => $trxID,
                'transaction_status' => 'Completed',
                'updated_at' => Carbon::now(),
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Payment not found!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Transaction
        $transactionForBackend = new Transaction();
        $transactionForBackend->trx_id = $trxID;
        $transactionForBackend->trx_type = 'payment';
        $transactionForBackend->plus_minus = 'minus';
        $transactionForBackend->sender_uid = $editor_id;
        $transactionForBackend->receiver_uid = $editor_id;
        $transactionForBackend->method = 'portal_payment';
        $transactionForBackend->amount = $package_estimated_price;
        $transactionForBackend->purpose = 'hotspot_internet_bill_payment';

        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Affiliate History
        $affiliate_history = new AffiliateHistory();
        $affiliate_history->affiliator_uid = $editor_id;
        $affiliate_history->product_type = 'internet_package';
        $affiliate_history->product_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $affiliate_history->status = 'pending';

        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $agent_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($agent) {
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } else {
                $commission = CorporateAgent::where('uid', $editor_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $editor_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($sub_agent) {
            $sub_agent_commission = CorporateSubAgent::where('uid', $editor_id)->value('commission');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');

            // agent id
            $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
            $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

            // commission & commission amount for agent
            $agent_commission = $agent_main_commission - $sub_agent_commission;
            $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

            // commission & commission amount for sub-agent
            $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
            $affiliate_history->commission_amount = $sub_agent_commission_amount;

            UserProfile::where('uid', $editor_id)->update([
                'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
            ]);

            UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
            ]);
        }

        if (!$affiliate_history->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Agent Commission Breakdown
        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $sub_agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            }
        } elseif ($agent) {
            $agent_id = InternetUsers::where('uid',$internet_user_id)->value('agent_id');
            $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
            $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
            $new_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = $editor_id;
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        } elseif ($sub_agent) {
            $sub_agent_id = InternetUsers::where('uid',$internet_user_id)->value('sub_agent_id');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
            $commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
            $sub_agent_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        }

        // Save Radius Payment
        $paymentForRadius = new WifiDbPaymentClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $package_estimated_price;
        $paymentForRadius->created_at = Carbon::now();

        if (!$paymentForRadius->save()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $latestPaymentDate = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('created_at');
        $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
        $expiration = InternetPackageCorporate::where('package_name', $package)->value('expiration');
        $smsDate = '';
        if($expiration < 1440 && $latestPaymentDate != null){
            $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
            $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
            $smsDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
        }else{
            $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
            $smsDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
        }
        // $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
        // $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');

        $expDateExist = WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->exists();
        if($expDateExist){
            WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->update(['value' => $expiryDate]);
        }else{
            $radUserGroup = new WifiDbRadReplyClient();
            $radUserGroup->username = $request->get('mobile_number');
            $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
            $radUserGroup->op = ":=";
            $radUserGroup->value = $expiryDate;
            $radUserGroup->save();
        }
        WifiDbRadUserGroupClient::where('username',$request->get('mobile_number'))->update(['groupname' => $package]);

        $mobile = $request->get('mobile_number');
        
        $smsText = "আপনার ইন্টারনেটের মেয়াদ ". $smsDate . " দিন পর্যন্ত বাড়ানো হয়েছে!";
        $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Payment processed successfully';
        return ResponseWrapper::End($returned_data);
    }

    // check in rad check for login
    public function checkRadcheckLogin(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Variables --------
        $mobile_number = $request->get('mobile_number');
        $password = $request->get('password');

        // Checking in Rad Check -------
        $numberCheck = WifiDbRadCheckClient::where('username', $mobile_number)->first();

        // Mobile number check -------
        if (!$numberCheck){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You have not register yet!';
            return ResponseWrapper::End($returned_data);
        }

        // Password Check -------
        if($password !== $numberCheck->value){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Password not match!';
            return ResponseWrapper::End($returned_data);
        }

        // Final Results ------
        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    // check in rad check for login
    public function checkingExpireDate(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Variables ---
        $package = $request->get('package');
        $mobile_number = $request->get('mobile_number');
        $internet_user_id = User::where('auth_id', $mobile_number)->value('id');

        $paymentExists = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->exists();
        $expiryDate = '';
        if ($paymentExists) {
            // Get the latest payment date
            $latestPaymentDate = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('created_at');
            $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
            $expiration = round(InternetPackageCorporate::where('package_name',$package)->value('expiration') / 1440);
            $expiryDate = Carbon::parse($latestPaymentDate)->addDays($expiration - 2);

            // Check if today is before the expiry date
            if (Carbon::now()->lessThanOrEqualTo($expiryDate)) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "আপনার প্যাকেজের মেয়াদ এখনো শেষ হয়নি।";
                return ResponseWrapper::End($returned_data);
            }
        }

        // Final Results ------
        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    // check in rad check for login
    public function changePassword(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $mobile = $request->get('mobile_number');
        $radCheck = WifiDbRadCheckClient::where('username', $mobile)->first();
        $password = '';
        if(!$radCheck){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You are not registered!';
            return ResponseWrapper::End($returned_data);
        }else{
            $userData = (new \App\Classes\CustomHelpers)->update_user_password($mobile);
            $password = $userData['password'];
            $radCheck->update(['value' => $password]);
        }

        $uid = User::where('auth_id', $mobile)->value('id');
        $zone_id = InternetUsers::where('uid', $uid)->value('zone_id');

        if($zone_id === '59339') {
            $smsText = "Your Fly Far WiFi Password is : ".$password.". Enjoy your time with us!";
            $send_sms = (new \App\Classes\CustomHelpers)->sendSmsNovocom($smsText, $mobile);
        } else {
            $smsText = "আপনার স্বাধীন ওয়াইফাই ইউজার পাসওয়ার্ড: " . $password;
            $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);
        }



        // get branch name ---
        $branch = CorporateClient::where('uid', $radCheck->branch)->value('zone_name');
        $returned_data['branch'] = $branch;
        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    // Bulk Upload Hello---
    public function bulkUploadHotspotUserRegister(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Validate file upload
        $validated = $request->validate([
            'file' => 'required|mimes:xlsx',
        ], [
            'file.required' => 'Please upload a file.',
            'file.mimes' => 'The file must be a valid XLSX file.',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Handle the file upload and processing
        try {
            Excel::import(new ISPClientHotspotUserBulkRegisterImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }

    // hotspot payment from frc/ucl - transactionUpdate.php
    public function createPaymentFromPaymentPortalBySSLCommerz(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internet_user_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $editor_id = InternetUsers::where('uid',$internet_user_id)->value('added_by');

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();
        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $internet_user = User::where('id', $internet_user_id)->first();
        if (!$internet_user) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');
        $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

        $package_estimated_price = $request->get('amount');
        $package_id = InternetPackageCorporate::where('price', $package_estimated_price)->value('id');
        $package = InternetPackageCorporate::where('price', $package_estimated_price)->value('package_name');

        // bkash info -----
        $paymentForRadius = new WifiDbBkashInfoClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $request->get('amount');
        $paymentForRadius->payment_id = $request->get('trx_id');
        $paymentForRadius->transaction_id = $request->get('trx_id');
        $paymentForRadius->status = 'Active';
        $paymentForRadius->created_at = Carbon::now();
        if($internet_user){
            $internet_user->update([
                'package_id' => $package_id,
                'connection_status' => 'Active',
            ]);
        }

        // Save Payment
        $paymentForBackend = new Payment();
        $paymentForBackend->uid = $internet_user_id;
        if($client){
            $paymentForBackend->zone_id = $editor_id;
        }elseif($agent){
            $client_id = CorporateAgent::where('uid',$editor_id)->value('client_id');
            $paymentForBackend->zone_id = $client_id;
        }elseif($sub_agent){
            $client_id = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
            $paymentForBackend->zone_id = $client_id;
        }
        $paymentForBackend->vendor_name = 'ssl_payment';
        $paymentForBackend->trx_id = $trxID;
        $paymentForBackend->invoice_number = $invoiceID;
        $paymentForBackend->amount = $package_estimated_price;
        $paymentForBackend->payment_id = $paymentID;
        $paymentForBackend->process_status = '1';
        $paymentForBackend->purpose = 'hotspot_internet_bill_payment';
        $paymentForBackend->package = $package;
        $paymentForBackend->transaction_status = 'Completed';
        if (!$paymentForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Transaction
        $transactionForBackend = new Transaction();
        $transactionForBackend->trx_id = $trxID;
        $transactionForBackend->trx_type = 'payment';
        $transactionForBackend->plus_minus = 'minus';
        $transactionForBackend->sender_uid = $editor_id;
        $transactionForBackend->receiver_uid = $editor_id;
        $transactionForBackend->method = 'ssl_payment';
        $transactionForBackend->amount = $package_estimated_price;
        $transactionForBackend->purpose = 'hotspot_internet_bill_payment';
        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Affiliate History
        $affiliate_history = new AffiliateHistory();
        $affiliate_history->affiliator_uid = $editor_id;
        $affiliate_history->product_type = 'internet_package';
        $affiliate_history->product_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $affiliate_history->status = 'pending';

        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $agent_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($agent) {
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                // agent id
                $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                // commission & commission amount for agent
                $agent_commission = $agent_main_commission - $sub_agent_commission;
                $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

                // commission & commission amount for sub-agent
                $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $sub_agent_commission_amount;

                UserProfile::where('uid', $sub_agent_id)->update([
                    'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                ]);

                UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                    'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                ]);
            } else {
                $commission = CorporateAgent::where('uid', $editor_id)->value('commission');
                $previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');
                $new_commission_amount = ($commission / 100) * $package_estimated_price;
                $affiliate_history->commission_amount = $new_commission_amount;

                UserProfile::where('uid', $editor_id)->update([
                    'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                ]);
            }
        } elseif ($sub_agent) {
            $sub_agent_commission = CorporateSubAgent::where('uid', $editor_id)->value('commission');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');

            // agent id
            $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
            $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

            // commission & commission amount for agent
            $agent_commission = $agent_main_commission - $sub_agent_commission;
            $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;

            // commission & commission amount for sub-agent
            $sub_agent_commission_amount = ($sub_agent_commission / 100) * $package_estimated_price;
            $affiliate_history->commission_amount = $sub_agent_commission_amount;

            UserProfile::where('uid', $editor_id)->update([
                'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
            ]);

            UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
            ]);
        }

        if (!$affiliate_history->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
            return ResponseWrapper::End($returned_data);
        }

        // Save Agent Commission Breakdown
        if ($client) {
            $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
            $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
            if ($checkIfSubAgent) {
                $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $sub_agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            } elseif ($checkIfAgent) {
                $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                $agentCommissionForBackend = new AgentCommissionBreakdown();
                $agentCommissionForBackend->agent_uid = $agent_id;
                $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
                $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
                $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

                if (!$agentCommissionForBackend->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                    return ResponseWrapper::End($returned_data);
                }
            }
        } elseif ($agent) {
            $agent_id = InternetUsers::where('uid',$internet_user_id)->value('agent_id');
            $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
            $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
            $new_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = $editor_id;
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        } elseif ($sub_agent) {
            $sub_agent_id = InternetUsers::where('uid',$internet_user_id)->value('sub_agent_id');
            $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
            $commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
            $sub_agent_commission_amount = ($commission / 100) * $package_estimated_price;
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
            $agentCommissionForBackend->user_uid = User::where('auth_id', $request->get('mobile_number'))->value('id');
            $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }
        }

        // Save Radius Payment
        $paymentForRadius = new WifiDbPaymentClient();
        $paymentForRadius->username = $request->get('mobile_number');
        $paymentForRadius->amount = $package_estimated_price;
        $paymentForRadius->created_at = Carbon::now();
        if (!$paymentForRadius->save()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $latestPaymentDate = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('created_at');
        $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
        $expiryDate = '';
        if($expiration < 1440){
            $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
            $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
        }else{
            $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
        }
        
        $expDateExist = WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->exists();
        if($expDateExist){
            WifiDbRadReplyClient::where('username',$request->get('mobile_number'))->update(['value' => $expiryDate]);
        }else{
            $radUserGroup = new WifiDbRadReplyClient();
            $radUserGroup->username = $request->get('mobile_number');
            $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
            $radUserGroup->op = ":=";
            $radUserGroup->value = $expiryDate;
            $radUserGroup->save();
        }
        WifiDbRadUserGroupClient::where('username',$request->get('mobile_number'))->update(['groupname' => $package]);

        WifiDbRadUserGroupClient::updateOrCreate(
            ['username' => $mobile],
            ['groupname' => $package]
        );

        $mobile = $request->get('mobile_number');
        $smsText = "আপনার ইন্টারনেটের মেয়াদ ". Carbon::now()->addDays(30) . " পর্যন্ত বাড়ানো হয়েছে!";
        $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Payment processed successfully';
        return ResponseWrapper::End($returned_data);
    }

    // public function packageUpdate($zone_id) : JsonResponse {
    //     $returned_data = ResponseWrapper::Start();
    //     //Log::info($mobile);
    //     //$usergroup = WifiDbRadUserGroupClient::where('username', $mobile)->firstOrFail();
        
    //     // WifiDbRadUserGroupClient::where('username', '=', $mobile)->update(array('groupname' => $package));
        
    //     // $returned_data['status'] = 'success';
    //     // $returned_data['message'] = 'Payment processed successfully';
    //     // return ResponseWrapper::End($returned_data);
    //     $zone_id = 45289;

    //     $branchInfo = CorporateClient::where('uid', '=', $zone_id)->get();

    //     // Check if branch information exists
    //     if (!$branchInfo) {
    //         $returned_data['status'] = 'error';
    //         $returned_data['message'] = 'Zone information not found!';
    //         return ResponseWrapper::End($returned_data);
    //     }
    //     Log::info($branchInfo);
    //     // Api Variables
    //     $ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
    //     $mkUser = $branchInfo->implode('mikrotik_username', ', ');
    //     $mkPass = $branchInfo->implode('mikrotik_password', ', ');
    //     $oldUser = BroadbandDbSecret::where('username', $mobile_number)->exists();
    //     $API = new RouterOsApi();

    //     // Connect to MikroTik Router
    //     if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            
    //             $arrID = $API->comm("/ppp/secret/print");
    //             Log::info($arrID['name']);
    //             // $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
    //             $API->disconnect();

            
    //     } else {
    //         $returned_data['status'] = 'error';
    //         $returned_data['message'] = 'Failed to connect to MikroTik Router!';
    //         return ResponseWrapper::End($returned_data);
    //     }

                
    // }

    public function packageUpdate($zone_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $branchInfo = CorporateClient::where('uid', '=', $zone_id)->get();
        $ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
        $mkUser = $branchInfo->implode('mikrotik_username', ', ');
        $mkPass = $branchInfo->implode('mikrotik_password', ', ');
        $API = new RouterOsApi();

        // Connect to MikroTik Router
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            $arrID = $API->comm("/ppp/secret/print");
            //Log::info($arrID[0]['name']);
            $API->disconnect();
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Failed to connect to MikroTik Router!';
            return ResponseWrapper::End($returned_data);
        }

        $returned_data['results'] = $arrID;
        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Payment processed successfully';
        return ResponseWrapper::End($returned_data);
    }

    public function checkRadReplySession(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Variables --------
        $mobile_number = $request->get('mobile_number');
        $password = $request->get('password');

        // Checking in Rad Check -------
        $numberCheck = WifiDbRadCheckClient::where('username', $mobile_number)->first();

        // Mobile number check -------
        if (!$numberCheck){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You have not register yet!';
            return ResponseWrapper::End($returned_data);
        }

        // Password Check -------
        if($password !== $numberCheck->value){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Password not match!';
            return ResponseWrapper::End($returned_data);
        }

        // Checking in Rad Check -------
        $expirityCheck = WifiDbRadReplyClient::where('username', $mobile_number)->value('value');
        $expirityFormat = Carbon::parse($expirityCheck)->format('Y-m-d H:i:s');
        // Mobile number check -------
        if ($expirityFormat < now()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Your session is expired!';
            return ResponseWrapper::End($returned_data);
        }

        $connected = WifiDbRadAcctClient::where('username', '=', $mobile_number)->whereNull('acctstoptime')->exists();
        if($connected){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Your internet session already active';
        }
        // Final Results ------
        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }


    public function checkRadReplySessionShadhin(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Variables --------
        $mobile_number = $request->get('mobile_number');
        $password = $request->get('password');

        // Checking in Rad Check -------
        $numberCheck = WifiDbRadCheck::where('username', $mobile_number)->first();

        // Mobile number check -------
        if (!$numberCheck){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You have not register yet!';
            return ResponseWrapper::End($returned_data);
        }

        // Password Check -------
        if($password !== $numberCheck->value){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Password not match!';
            return ResponseWrapper::End($returned_data);
        }

        // Checking in Rad Check -------
        $expirityCheck = WifiDbRadReply::where('username', $mobile_number)->value('value');
        $expirityFormat = Carbon::parse($expirityCheck)->format('Y-m-d H:i:s');
        // Mobile number check -------
        if ($expirityFormat < now()){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Your session is expired!';
            return ResponseWrapper::End($returned_data);
        }

        $connected = WifiDbRadAcct::where('username', '=', $mobile_number)->whereNull('acctstoptime')->exists();
        if($connected){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Your internet session already active';
        }
        // Final Results ------
        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }
}
