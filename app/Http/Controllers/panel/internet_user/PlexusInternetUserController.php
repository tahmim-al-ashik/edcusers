<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\BroadbandDbUsers;
use App\Models\CorporateClient;
use App\Models\InternetPackage;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WifiDbPayment;
use App\Models\WifiDbPaymentClient;
use App\Models\WifiDbRadAcct;
use App\Models\WifiDbRadAcctClient;
use App\Models\WifiDbRadCheck;
use App\Models\WifiDbRadCheckClient;
use App\Models\WifiDbRadReply;
use App\Models\WifiDbRadReplyClient;
use App\Models\WifiDbRadUserGroup;
use App\Models\WifiDbRadUserGroupClient;
use App\Models\WifiDbUserInfo;
use App\Models\WifiDbUserInfoClient;
use App\Models\CorporateInternetUsers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlexusInternetUserController extends Controller
{
    public function getInternetUserList(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keywords')));
        $status = $request->get('status');
        $c_status = $request->get('communication_status');
        $package_type = $request->get('package_type');
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

        $query = InternetUsers::query();
        $query->where('internet_users.user_type', 'corporate');
        $query->leftJoin('users as u', 'u.id', '=', 'internet_users.uid');
        $query->leftJoin('corporate_internet_users as cu', 'cu.id', '=', 'internet_users.uid');
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
        $query->skip($totalSkip)->take(50);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'u.auth_id as mobile_number',
            'internet_users.id',
            'internet_users.uid',
            'cu.service_type',
            'internet_users.connection_status',
            'communications.status as c_status',
            'internet_users.created_at',
            'internet_users.package_type',
            'internet_users.zone_id'
        ]);
        return ResponseWrapper::End($returned_data);
    }

    public function webRegisterCorporateInternetUserPlexus(Request $request)
    {
        // Validate required input
        $request->validate([
            'profile.full_name' => 'required|string',
            'profile.service_type' => 'nullable|string',
            'profile.mobile_number' => 'required|string',
            'profile.email' => 'nullable|email',
            'profile.company_name' => 'required|string',
            'profile.company_type' => 'required|string',
            'profile.profession' => 'required|string',
            'profile.division_id' => 'required|integer',
            'profile.district_id' => 'required|integer',
            'profile.upazila_id' => 'required|integer',
            'profile.union_id' => 'required|integer',
            'profile.village_id' => 'required|integer',
            'profile.address' => 'required|string',
            'profile.address_direction' => 'required|string',
            'profile.latitude' => 'nullable|numeric',
            'profile.longitude' => 'nullable|numeric',
            // 'data.corporate_package_id' => 'required|integer',
            'redirect' => 'required|url'
        ]);

        $profileData = $request->get('profile');
        $internetData = $request->get('data');
        $mobileNumber = $profileData['mobile_number'];

        // Check if user already exists
        $mobileCheck = User::where('auth_id', $mobileNumber)->exists();
        if ($mobileCheck) {
            return redirect($request->get('redirect') . '?status=error&error_type=already_applied');
        }

        DB::beginTransaction();
        try {
            // Create user
            $userData = (new \App\Classes\CustomHelpers)->create_new_user($mobileNumber, 'user', 'corporate');
            $uid = $userData['user']['id'];
            $password = $userData['password'];

            // Create user profile
            $userProfile = new UserProfile();
            $userProfile->uid = $uid;
            $userProfile->full_name = $profileData['full_name'];
            $userProfile->mobile_number = $mobileNumber;
            $userProfile->email = $profileData['email'];
            $userProfile->profession = $profileData['profession'];
            $userProfile->division_id = $profileData['division_id'];
            $userProfile->district_id = $profileData['district_id'];
            $userProfile->upazila_id = $profileData['upazila_id'];
            $userProfile->union_id = $profileData['union_id'];
            $userProfile->village_id = $profileData['village_id'];
            $userProfile->address = $profileData['address'];
            $userProfile->address_direction = $profileData['address_direction'];
            $userProfile->latitude = $profileData['latitude'];
            $userProfile->longitude = $profileData['longitude'];
            $userProfile->device_info = json_encode(["brand" => "website"]);
            $userProfile->save();

            // Create Internet user
            $internetUser = new InternetUsers();
            $internetUser->uid = $uid;
            $internetUser->zone_id = null;
            $internetUser->added_by = null;
            $internetUser->package_id = $internetData['corporate_package_id'] ?? 0;
            $internetUser->package_type = 'corporate';
            $internetUser->package_expire_date = null;
            $internetUser->latitude = $profileData['latitude'];
            $internetUser->longitude = $profileData['longitude'];
            $internetUser->password = $password;
            $internetUser->password_broadband = $password;
            $internetUser->user_type = 'corporate';
            $internetUser->billing_address = $profileData['address_direction'];
            $internetUser->serial_number = null;
            $internetUser->broadband_pop_id = null;
            $internetUser->connection_media = null;
            $internetUser->installation_charge = 0;
            $internetUser->connection_status = 'pending';
            $internetUser->save();

            // Create corporate internet user
            $corporateUser = new CorporateInternetUsers();
            $corporateUser->uid = $uid;
            $corporateUser->service_type = $profileData['service_type'] ?? null;
            $corporateUser->company_name = $profileData['company_name'] ?? null;
            $corporateUser->company_type = $profileData['company_type'] ?? null;
            $corporateUser->requirements = $profileData['requirements'] ?? null;
            $corporateUser->status = 'pending';
            $corporateUser->save();

            DB::commit();
            return redirect($request->get('redirect') . '?status=success&message_key=success');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Corporate internet user registration failed: ' . $e->getMessage());
            return redirect($request->get('redirect') . '?status=error&error_type=exception');
        }
    }

    public function internetUserDetails(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internetUsers = InternetUsers::where('id', '=', $id)->first();
        $internetUsers['package_data'] = InternetPackage::find($internetUsers['package_id']);
        $internetUsers['service_type'] = CorporateInternetUsers::where('uid', '=', $internetUsers['uid'])->value('service_type');
        $internetUsers['requirements'] = CorporateInternetUsers::where('uid', '=', $internetUsers['uid'])->value('requirements');
        if(!empty($internetUsers['zone_id'])){
            $internetUsers['zone_name'] = NetworkSupportCenter::where('zone_id', $internetUsers['zone_id'])->value('zone_name');
        }

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

    public function getAllLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 100;

        $query = InternetUsers::query();
        $query->where('internet_users.user_type', 'corporate');
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

        $returned_data['results']['list'] = $query->get([
            'internet_users.id',
            'internet_users.latitude',
            'internet_users.longitude',
            'internet_users.connection_status as status',
            'user_profiles.full_name',
            'user_profiles.mobile_number',
            'user_profiles.email',
            DB::raw("'internet-users/corporate' as url_type")
        ]);
        $returned_data['results']['total'] = $total;
        return ResponseWrapper::End($returned_data);
    }

    public function getInternetUserSummary(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $query = InternetUsers::where('internet_users.user_type', 'corporate')->get()->groupBy('connection_status')->map(function ($row) {
            return $row->count();
        });

        $countArray["active"] = !empty($query['active']) ? $query['active'] : 0;
        $countArray["pending"] = !empty($query['pending']) ? $query['pending'] : 0;
        $countArray["total"] = $countArray['active'] + $countArray['pending'];
        $returned_data['results'] = $countArray;
        return ResponseWrapper::End($returned_data);

    }

    public function searchInternetUser(Request $request, $keywords) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = InternetUsers::query();
        $query->where('internet_users.user_type', 'corporate');
        $query->leftJoin('users as u', 'u.id', '=', 'internet_users.uid');
        $query->leftJoin('user_profiles as up', 'up.uid', '=', 'internet_users.uid');

        $keywords = strtolower(trim($keywords));
        $query->where('u.auth_id', 'LIKE', '%' . $keywords . '%');
        $query->orWhere('internet_users.zone_id', 'LIKE', '%' . $keywords . '%');
        $query->orWhere('up.union_id', 'LIKE', '%' . $keywords . '%');

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'u.id as uid',
            'u.base_role',
            'u.panel_access',
            'up.full_name',
            'u.auth_id as mobile_number',
            'up.email',
            'up.union_id',
            'internet_users.id',
            'internet_users.connection_status',
            'internet_users.package_type',
            'internet_users.zone_id',
            'internet_users.created_at',
        ]);

        return ResponseWrapper::End($returned_data);
    }

    public function getInternetUserBasic(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $query = InternetUsers::query();
        $query->where('internet_users.user_type', 'corporate');
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

    public function getInternetUserCount(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = InternetUsers::query();
        $query->where('internet_users.user_type', 'corporate');
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
                        ->where('internet_users.user_type', 'corporate')
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

    public function internetUserLineChart($days, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Calculate the date x days ago
        $startDate = now()->subDays($days)->startOfDay();

        $query = InternetUsers::selectRaw(
            "DAY(created_at) AS day,
            MONTH(created_at) AS month,
            YEAR(created_at) AS year,
            COUNT(uid) AS total,
            COUNT(CASE WHEN connection_status = 'active' THEN 1 END) AS total_active,
            COUNT(CASE WHEN connection_status = 'pending' THEN 1 END) AS total_pending"
        )->where('internet_users.user_type', 'corporate')->where('created_at', '>=', $startDate);

        if($type !== 'all'){
            $query->where('package_type', $type);
        }

        $results = $query->groupBy(DB::raw('YEAR(created_at), MONTH(created_at), DAY(created_at)'))
            ->orderBy('created_at', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . $item->month . '-' . $item->day;
            });

        // Fill in missing days with zero counts
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;
            $key = $year . '-' . $month . '-' . $day;

            $data[] = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'total' => $results->get($key)->total ?? 0,
                'total_active' => $results->get($key)->total_active ?? 0,
                'total_pending' => $results->get($key)->total_pending ?? 0
            ];
        }

        // Reverse the data to have the most recent day first
        $data = array_reverse($data);

        $returned_data['results']['total'] = $data;
        return ResponseWrapper::End($returned_data);
    }
}