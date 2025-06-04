<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Models\BroadbandDbOperator;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbZone;
use App\Models\WifiDbPayment;
use App\Models\WifiDbRadCheck;
use App\Models\CorporateClient;
use App\Models\CorporateClientsSettings;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\Payment;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PanelBranchInfoController extends Controller
{
    // Branch List --------------
    public function getBranchList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = BroadbandDbOperator::query();
        $query->leftJoin('zone as z', 'z.zone_name', '=', 'operator.zone_name');
        $query->orderBy('operator.created_at', 'DESC');

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'operator.id',
            'operator.zone_name as branch_name',
            'z.mikrotik_ip',
            'z.address',
            'z.current_month_user_disable_status',
            'operator.created_at',
        ]);
        return ResponseWrapper::End($returned_data);
    }

    // Broadband Comission Calculation -------------
    public function broadbandCommissionCalculation($branch_name, $broadbandUserCount) {
        $fourtyPercent = ['sayedpur','katarkona','jogonnathpur','kolkolia','satgaon'];
        $thirtyFivePercent = ['barishal'];

        if (in_array($branch_name, $fourtyPercent)) {
            $broadbandCommission = '40';
        } else if(in_array($branch_name, $thirtyFivePercent)){
            $broadbandCommission = '35';
        } else if($broadbandUserCount <= 199){
            $broadbandCommission = '30';
        } else if($broadbandUserCount > 199 && $broadbandUserCount <=499){
            $broadbandCommission = '35';
        } else if($broadbandUserCount > 499 && $broadbandUserCount <= 1000){
            $broadbandCommission = '40';
        } else if($broadbandUserCount > 1000){
            $broadbandCommission = '42';
        }
        return $broadbandCommission;
    }

    // Broadband Statement -----------------
    public function broadbandStatement($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Variables
        $month = date('Y-n-d');
		$lmonth = date( ('Y-n-d'), strtotime($month.'-1 month'));
		$last_month= date('n',strtotime($lmonth));
		$year = date('Y', strtotime($lmonth));

        // Broadband User Count For Broadband Commission
        $broadbandUserCount = BroadbandDbPayment::select('username')
            ->join('secret', 'payment.username', '=', 'secret.username')
            ->where('secret.zone', '=', $branch_name)
            ->where('payment.month', '=', $last_month)
            ->whereYear('payment.payment_date', '=', $year)
            ->count('payment.username');

        // Broadband Commission Calculation
        $broadbandCommission = $this->broadbandCommissionCalculation($branch_name, $broadbandUserCount);

        $statements = BroadbandDbSecret::query()
            ->join('payment', 'secret.username', '=', 'payment.username')
            ->leftJoin('subscriber_info', 'secret.username', '=', 'subscriber_info.numAsID')
            ->leftJoin('profile', 'secret.profile', '=', 'profile.name')
            ->select(DB::raw('secret.username,
                            secret.password,
                            secret.status,
                            secret.profile,
                            profile.price,
                            secret.created_at,
                            payment.payment_date,
                            payment.month,
                            subscriber_info.customerName,
                            subscriber_info.popId,
                            CONCAT(subscriber_info.village, ", ",subscriber_info.post_office, ", ",subscriber_info.police_station, ", ",subscriber_info.district) As address,
                            payment.amount'))
            ->where('secret.zone', '=', $branch_name)
            ->groupBy('payment.payment_date')
            ->orderBy('payment.payment_date', 'DESC');


        $returned_data['results']['commission'] = $broadbandCommission;
        $returned_data['results']['list'] = $statements->get([
            'secret.username',
            'secret.password',
            'secret.profile',
            'profile.price as package_price',
            'secret.created_at',
            'payment.payment_date',
            'payment.month',
            'subscriber_info.customerName',
            'subscriber_info.popId',
            'CONCAT(subscriber_info.village, ', ', subscriber_info.post_office, ', ', subscriber_info.police_station, ', ', subscriber_info.district) AS address',
            'payment.amount'
        ]);

        // Cast status to string in the result array
        foreach ($returned_data['results']['list'] as $result) {
            $result->status = (string) $result->status;
        }

        return ResponseWrapper::End($returned_data);
    }

    // WiFi/Hotspot Commission Calculation -------------
    public function wifiCommissionCalculation($branch_name, $wifiUserCount) {
        $fourtyPercent = ['sayedpur','katarkona','jogonnathpur','kolkolia','satgaon'];
        $thirtyFivePercent = ['barishal'];

        if (in_array($branch_name, $fourtyPercent)) {
            $wifiCommission = '40';
        } else if(in_array($branch_name, $thirtyFivePercent)){
            $wifiCommission = '35';
        } else if($wifiUserCount <= 199){
            $wifiCommission = '30';
        } else if($wifiUserCount > 199 && $wifiUserCount <=499){
            $wifiCommission = '35';
        } else if($wifiUserCount > 499 && $wifiUserCount <= 1000){
            $wifiCommission = '40';
        } else if($wifiUserCount > 1000){
            $wifiCommission = '42';
        }
        return $wifiCommission;
    }

    // Wi-Fi/Hotspot Statement --------------
    public function wifiStatement($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        // Variables
        $month = date('Y-n-d');
		$lmonth = date( ('Y-n-d'), strtotime($month.'-1 month'));
		$last_month= date('n',strtotime($lmonth));
		$year = date('Y', strtotime($lmonth));

        // Wifi User Count For Wifi Commission
        $wifiUserCount = WifiDbPayment::select('username')
            ->join('radcheck', 'payment.username', '=', 'radcheck.username')
            ->where('radcheck.branch', '=', $branch_name)
            ->whereMonth('payment.created_at', '=', $last_month)
            ->whereYear('payment.created_at', '=', $year)
            ->count('payment.username');

        // Wifi Commission Calculation
        $wifiCommission = $this->wifiCommissionCalculation($branch_name, $wifiUserCount);

        $statements = WifiDbRadCheck::query()
            ->join('radusergroup', 'radcheck.username', '=', 'radusergroup.username')
            ->join('payment', 'radcheck.username', '=', 'payment.username')
            ->leftJoin('userinfo', 'radcheck.username', '=', 'userinfo.username')
            ->select([
                'radcheck.value',
                'radusergroup.groupname',
                'userinfo.firstname',
                'payment.username',
                'payment.amount',
                DB::raw("CONCAT(userinfo.thana, ', ', userinfo.district, ', ', userinfo.state, ', ', userinfo.country) AS address"),
                'payment.created_at',
            ])
            ->where('radcheck.branch', '=', $branch_name)
            ->groupBy('payment.created_at')
            ->orderBy('payment.created_at', 'DESC');

        $returned_data['results']['commission'] = $wifiCommission;
        $returned_data['results']['list'] = $statements->get([
            'radcheck.value',
            'radusergroup.groupname',
            'userinfo.firstname',
            'payment.username',
            'payment.amount',
            DB::raw("CONCAT(userinfo.thana, ', ', userinfo.district, ', ', userinfo.state, ', ', userinfo.country) AS address"),
            'payment.created_at',
        ]);

        return ResponseWrapper::End($returned_data);
    }

    // Broadband Mikrotik Total User List -----------
	public function broadbandMikrotikTotalUsers($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking the branch in the branchlist
		$mkInfo = BroadbandDbZone::where('zone_name', '=', $branch_name)->get();

        // Api Variables
		$ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
		$mkUser = $mkInfo->implode('username', ', ');
		$mkPass = $mkInfo->implode('password', ', ');
		$API = new RouterOsApi();

        // Connecting api
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            $ARRAY = $API->comm('/ppp/secret/print');
            $ARRAYCONNECTED = $API->comm('/ppp/active/print');
            $pppoeItems = [];

            // Iterate over each item in the $ARRAY
            foreach ($ARRAY as $item) {
                // Check if the service is 'pppoe'
                if ($item['service'] === 'pppoe') {
                    // Initialize the uptime variable
                    $uptime = null;

                    // Iterate over each item in $ARRAYCONNECTED to find a match by username
                    foreach ($ARRAYCONNECTED as $connectedItem) {
                        // Check if the username matches
                        if ($connectedItem['name'] === $item['name']) {
                            // If username matches, set the uptime
                            $uptime = $connectedItem['uptime'];
                            // No need to continue searching, break the loop
                            break;
                        }
                    }

                    // If service is 'pppoe', add it to $pppoeItems array with uptime
                    $pppoeItems[] = [
                        'name' => $item['name'],
                        'password' => $item['password'],
                        'profile' => $item['profile'],
                        'status' => $item['disabled'],
                        'uptime' => $uptime, // Add the uptime
                    ];
                }
            }

            // Prepare the response data with only pppoe items and uptime
            $returned_data['results']['list'] = $pppoeItems;

            // Return the response
            return ResponseWrapper::End($returned_data);
        }

        // API Disconnect
        $API->disconnect();
    }

    // Broadband Mikrotik Active User List -----------
	public function broadbandMikrotikActiveUsers($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking the branch in the branchlist
		$mkInfo = BroadbandDbZone::where('zone_name', '=', $branch_name)->get();

        // Api Variables
		$ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
		$mkUser = $mkInfo->implode('username', ', ');
		$mkPass = $mkInfo->implode('password', ', ');
		$API = new RouterosAPI();

        // Connecting api
		if ($API->connect($ipAddr, $mkUser, $mkPass)) {
		   $ARRAY = $API->comm("/ppp/secret/print", array (
				  "?disabled"=> "no",
			));

           $ppopeItems = [];

           // Iterate over each item in the $ARRAY
           foreach($ARRAY as $item){
            // Check if the service is 'pppoe'
            if($item['service'] === 'pppoe'){
                // If service is 'pppoe', add it to $pppoeItems array
                $ppopeItems[] = [
                    'name' => $item['name'],
                    'password' => $item['password'],
                    'profile' => $item['profile']
                ];
            }
           }

           // Prepare the response data with only pppoe items
           $returned_data['results']['list'] = $ppopeItems;

           // Return the response
           return ResponseWrapper::End($returned_data);
		}

        // API Disconnect
        $API->disconnect();
    }

    // Broadband Mikrotik Inactive User List -----------
	public function broadbandMikrotikInactiveUsers($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking the branch in the branchlist
        $zone = $branch_name;
		$mkInfo = BroadbandDbZone::where('zone_name', '=', $zone)->get();

        // Api Variables
		$ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
		$mkUser = $mkInfo->implode('username', ', ');
		$mkPass = $mkInfo->implode('password', ', ');
		$API = new RouterosAPI();

        // Connecting api
		if ($API->connect($ipAddr, $mkUser, $mkPass)) {
		   $ARRAY = $API->comm("/ppp/secret/print", array (
				  "?disabled"=> "yes",
			));
            dd($ARRAY);
		   $API->disconnect();

           $ppopeItems = [];
           // Iterate over each item in the $ARRAY
           foreach($ARRAY as $item){
            // Check if the service is 'pppoe'
            if($item['service'] === 'pppoe'){
                // If service is 'pppoe', add it to $pppoeItems array
                $ppopeItems[] = [
                    'name' => $item['name'],
                    'password' => $item['password'],
                    'profile' => $item['profile']
                ];
            }
           }

           // Prepare the response data with only pppoe items
           $returned_data['results']['list'] = $ppopeItems;
		}

        return ResponseWrapper::End($returned_data);
    }

    // Broadband Mikrotik Connected User List -----------
	public function broadbandMikrotikConnectedUsers($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking the branch in the branchlist
		$mkInfo = BroadbandDbZone::where('zone_name', '=', $branch_name)->get();

        // Api Variables
		$ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
		$mkUser = $mkInfo->implode('username', ', ');
		$mkPass = $mkInfo->implode('password', ', ');
		$API = new RouterosAPI();

        // Connecting api
		if ($API->connect($ipAddr, $mkUser, $mkPass)) {
           $ARRAY = $API->comm('/ppp/active/print');

           $ppopeItems = [];
           // Iterate over each item in the $ARRAY
           foreach($ARRAY as $item){
            // Check if the service is 'pppoe'
            if($item['service'] === 'pppoe'){
                // If service is 'pppoe', add it to $pppoeItems array
                $ppopeItems[] = [
                    'name' => $item['name'],
                    'uptime' => $item['uptime'],
                ];
            }
           }

           // Prepare the response data with only pppoe items
           $returned_data['results']['list'] = $ppopeItems;

           // Return the response
           return ResponseWrapper::End($returned_data);
		}

        // API Disconnect
        $API->disconnect();
    }

    // Hotspot Mikrotik Total User List -----------
	public function hotspotMikrotikTotalUsers($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        // Checking the branch in the branchlist
        $mkInfo = BroadbandDbZone::where('zone_name', '=', $branch_name)->get();

        // Api Variables
        $ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
        $mkUser = $mkInfo->implode('username', ', ');
        $mkPass = $mkInfo->implode('password', ', ');
        $API = new RouterOsApi();

        // Connecting api
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            $ARRAY = $API->comm('/ip/hotspot/active/print');
            // dd($ARRAY);
            $totalHotspotUser =	WifiDbRadCheck::query()
                ->join('radusergroup','radcheck.username', '=', 'radusergroup.username')
                ->join('radreply','radcheck.username', '=', 'radreply.username')
                ->select([DB::RAW('DISTINCT(radcheck.username)'),'radcheck.value','radusergroup.groupname','radcheck.branch','radcheck.updatetime','radreply.value as expire_date'])
                ->where('radcheck.branch', '=', $branch_name)
                ->groupBy('radcheck.username')
                ->orderBy('radcheck.updatetime','DESC')
                ->get(); // Fetch results

            $pppoeItems = [];

            // Iterate over each item in the $ARRAY
            foreach ($totalHotspotUser as $item) {
                // Initialize the uptime variable
                $uptime = null;

                // Iterate over each item in $ARRAY to find a match by username
                foreach ($ARRAY as $connectedItem) {
                    // Check if the username matches
                    if ($connectedItem['user'] === $item['username']) {
                        // If username matches, set the uptime
                        $uptime = $connectedItem['uptime'];
                        // No need to continue searching, break the loop
                        break;
                    }
                }

                // If service is 'pppoe', add it to $pppoeItems array with uptime
                $pppoeItems[] = [
                    'name' => $item['username'],
                    'password' => $item['value'],
                    'profile' => $item['groupname'],
                    'uptime' => $uptime, // Add the uptime
                    'branch' => $item['branch'], // Add branch info
                    'expire_date' => $item['expire_date'],
                ];
            }

            // Prepare the response data with pppoe items and additional info
            $returned_data['results']['list'] = $pppoeItems;

            // Return the response
            return ResponseWrapper::End($returned_data);
        }

        // API Disconnect
        $API->disconnect();
    }

    // Hotspot Mikrotik Paid User List -----------
	public function hotspotMikrotikPaidUsers($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

		$hotspotPaidUser = WifiDbPayment::query()
					->join('radcheck', 'payment.username', '=', 'radcheck.username')
					->join('radusergroup','payment.username', '=', 'radusergroup.username')
					->select([DB::RAW('DISTINCT(payment.username)'),'radcheck.value','payment.amount', 'payment.created_at'])
                    ->where('radcheck.branch', '=', $branch_name)
                    ->groupBy('payment.username');

        $returned_data['results']['list'] = $hotspotPaidUser->get([
            'radcheck.username',
            'radcheck.value',
            'payment.amount',
            'payment.created_at'
        ]);

        return ResponseWrapper::End($returned_data);
    }

    // Hotspot Mikrotik Connected User List -----------
	public function hotspotMikrotikConnectedUsers($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking the branch in the branchlist
		$mkInfo = BroadbandDbZone::where('zone_name', '=', $branch_name)->get();

        // Api Variables
		$ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
		$mkUser = $mkInfo->implode('username', ', ');
		$mkPass = $mkInfo->implode('password', ', ');
		$API = new RouterosAPI();

        // Connecting api
		if ($API->connect($ipAddr, $mkUser, $mkPass)) {
           $ARRAY = $API->comm('/ip/hotspot/active/print');

           $ppopeItems = [];
           // Iterate over each item in the $ARRAY
           foreach($ARRAY as $item){
                // If service is 'pppoe', add it to $pppoeItems array
                $ppopeItems[] = [
                    'name' => $item['user'],
                    'uptime' => $item['uptime'],
                ];
           }

           // Prepare the response data with only pppoe items
           $returned_data['results']['list'] = $ppopeItems;

           // Return the response
           return ResponseWrapper::End($returned_data);
		}

        // API Disconnect
        $API->disconnect();
    }

    public function broadbandUserDisable($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Fetch branch information
        $branchInfo = BroadbandDbZone::where('zone_name', '=', $branch_name)->get();

        // Check if branch information exists
        if (!$branchInfo) {
            $returned_data['results']['message'] = 'Zone information not found';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        // Api Variables
		$ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
		$mkUser = $branchInfo->implode('username', ', ');
		$mkPass = $branchInfo->implode('password', ', ');
		$API = new RouterosAPI();

        // Connect to MikroTik Router
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {

            // Get current month and year
            $currentMonth = date('n');
            $currentYear = now()->format('Y');

            // Fetch usernames that need to be disabled
            $usernames = BroadbandDbSecret::where('zone', $branch_name)
            ->where('status', 0)
            ->whereNotIn('username', function ($query) use ($currentMonth, $currentYear) {
                $query->select('username')
                    ->from('payment')
                    ->where('month', $currentMonth)
                    ->whereYear('payment_date', $currentYear);
                    
            })
            ->pluck('username')
            ->toArray();

            //Log::info($usernames);

           

            foreach ($usernames as $username) {
                if ($API->connect($ipAddr, $mkUser, $mkPass)) {
                    $arrID = $API->comm("/ppp/secret/print", [
                        ".proplist" => ".id",
                        "?name" => $username,
                    ]);

                    // Check if $arrID has any result
                    if (!empty($arrID)) {
                        $API->comm("/ppp/secret/disable", [
                            ".id" => $arrID[0][".id"]
                        ]);
                    }

                    $id = $API->comm("/ppp/active/getall", [
                        ".proplist" => ".id",
                        "?name" => $username,
                    ]);

                    // Check if $id has any result
                    if (!empty($id)) {
                        $API->comm("/ppp/active/remove", [
                            ".id" => $id[0][".id"]
                        ]);
                    }

                    $API->disconnect();
                }

                // Update the status and dateOf_Inactive in the BroadbandDbSecret table
                BroadbandDbSecret::where('username', $username)
                    ->update(['status' => 1, 'dateOf_Inactive' => now()]);
            }

            // Update the status and dateOf_Inactive in the BroadbandDbSecret table
            BroadbandDbZone::where('zone_name', $branch_name)->update(['current_month_user_disable_status' => 1]);

            // Prepare the response data
            $returned_data['results']['success'] = true;

            // Return the response
            return ResponseWrapper::End($returned_data);
        } else {
            $returned_data['results']['message'] = 'Failed to connect to MikroTik Router';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
    }

    public function broadbandUserEnable($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Fetch branch information
        $branchInfo = BroadbandDbZone::query()->select('mikrotik_ip', 'username', 'password')->where('zone_name', '=', $branch_name)->first();

        // Check if branch information exists
        if (!$branchInfo) {
            $returned_data['results']['message'] = 'Zone information not found';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        // Initialize RouterOS API
        $API = new RouterosAPI();

        // Connect to MikroTik Router
        if (!$API->connect($branchInfo->mikrotik_ip, $branchInfo->username, $branchInfo->password)) {
            $returned_data['results']['message'] = 'Failed to connect to MikroTik Router';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        $currentMonth = now()->format('m');
        $previousMonth = $currentMonth - 1;
        $currentYear = now()->format('Y');

        // If current month is January, set previous month to December and adjust year accordingly
        if ($currentMonth === '01') {
            $previousMonth = '12';
            $currentYear = now()->subYear()->format('Y');
        }

        // Fetch usernames that need to be enabled
        $usernames = BroadbandDbPayment::select('payment.username')
            ->join('secret', 'payment.username', '=', 'secret.username')
            ->where('secret.zone', $branch_name)
            ->where('secret.status', 1)
            ->whereMonth('payment.payment_date', $previousMonth)
            ->whereYear('payment.payment_date', $currentYear)
            ->whereIn('payment.payment_date', function ($query) {
                $query->selectRaw('MAX(payment_date)')
                    ->from('payment')
                    ->groupBy('username');
            })
            ->pluck('username')
            ->toArray();


        // Iterate over each username
        foreach ($usernames as $username) {

            if($API->connect($branchInfo->mikrotik_ip, $branchInfo->username, $branchInfo->password)){
                $arrID = $API->comm("/ppp/secret/print", array(".proplist" => ".id", "?name" => $username));
                $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                // Disconnect from the MikroTik router
                $API->disconnect();
            }

            // Update the status and dateOf_Inactive in the BroadbandDbSecret table
            BroadbandDbSecret::where('username', $username)
                ->update(['status' => 0, 'dateOf_Inactive' => now()->subMonth()]);
        }

        // Update the status and dateOf_Inactive in the BroadbandDbSecret table
        BroadbandDbZone::where('zone_name', $branch_name)->update(['current_month_user_disable_status' => 0]);

        // Disconnect from the MikroTik Router
        $API->disconnect();

        // Prepare the response data
        $returned_data['results']['success'] = true;

        // Return the response
        return ResponseWrapper::End($returned_data);
    }

    public function resetCurrentMonthZoneStatus() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Reset Status Of all Zones
        BroadbandDbZone::query()->update(['current_month_user_disable_status' => 0]);

        // Prepare the response data
        $returned_data['results']['success'] = true;

        // Return the response
        return ResponseWrapper::End($returned_data);
    }

    // Broadband Statement ISP Client -----------------
    public function broadbandStatementISPClient($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        // Variables
        $month = date('Y-n-d');
		$lmonth = date( ('Y-n-d'), strtotime($month.'-1 month'));
		$last_month= date('n',strtotime($lmonth));
		$year = date('Y', strtotime($lmonth));

        // Checking User
        if ($client = CorporateClient::where('uid', $user_id)->exists()) {
            $client_id = $user_id;
        } elseif ($agent = CorporateAgent::where('uid', $user_id)->exists()) {
            $agent_id = $user_id;
        } elseif ($sub_agent = CorporateSubAgent::where('uid', $user_id)->exists()) {
            $sub_agent_id = $user_id;
        }

        $statements = InternetUsers::query()->where('package_type','broadband')
            ->join('users', 'internet_users.uid', '=', 'users.id')
            ->join('user_profiles', 'internet_users.uid', '=', 'user_profiles.uid')
            ->join('payments', 'internet_users.uid', '=', 'payments.uid');

        $selectColumns = [
            'user_profiles.mobile_number as username',
            'users.text_password as password',
            'internet_users.connection_status as status',
            DB::raw("(SELECT package_name FROM internet_package_corporates WHERE payments.package = internet_package_corporates.package_name) as profile"),
            DB::raw("(SELECT price FROM internet_package_corporates WHERE payments.package = internet_package_corporates.package_name) as price"),
            'user_profiles.created_at',
            'payments.created_at as payment_date',
            DB::raw('MONTH(payments.created_at) as month'),
            'user_profiles.full_name as customerName',
            'internet_users.broadband_pop_id as popId',
            'payments.amount',
            'internet_users.added_by as added_by',
            DB::raw("(SELECT base_role FROM users WHERE internet_users.added_by = users.id) as base_role"),
            'user_profiles.address',
        ];

        $statements->leftJoin('corporate_clients', 'internet_users.zone_id', '=', 'corporate_clients.uid');
        $statements->leftJoin('corporate_agents', 'internet_users.agent_id', '=', 'corporate_agents.uid');
        $statements->leftJoin('corporate_sub_agents', 'internet_users.sub_agent_id', '=', 'corporate_sub_agents.uid');

        // Add agent_commission - sub_agent_commission as the final commission for the agent
        $selectColumns[] = 'corporate_agents.commission as original_agent_commission';
        $selectColumns[] = 'corporate_sub_agents.commission as sub_agent_commission';
        $selectColumns[] = DB::raw('(corporate_agents.commission - corporate_sub_agents.commission) as agent_commission');


        if ($client) {
            $statements->where('internet_users.zone_id', '=', $client_id);
        } elseif ($agent || $client) {
            $statements->where('internet_users.agent_id', '=', $agent_id);
        } elseif ($sub_agent || $client) {
            $statements->where('internet_users.sub_agent_id', '=', $sub_agent_id);
        }

        $statements->where('payments.transaction_status' , 'Completed')->groupBy('payments.created_at')
            ->orderBy('payments.created_at', 'DESC');

        $returned_data['results']['list'] = $statements->get($selectColumns);


        // Cast status to string in the result array
        foreach ($returned_data['results']['list'] as $result) {
            $result->status = (string) $result->status;
        }
        return ResponseWrapper::End($returned_data);
    }

    // Wi-Fi Statement ISP Client -----------------
    public function wifiStatementISPClient($user_id) : JsonResponse{
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        // Variables
        $month = date('Y-n-d');
		$lmonth = date( ('Y-n-d'), strtotime($month.'-1 month'));
		$last_month= date('n',strtotime($lmonth));
		$year = date('Y', strtotime($lmonth));

        // Checking User
        if ($client = CorporateClient::where('uid', $user_id)->exists()) {
            $client_id = $user_id;
        } elseif ($agent = CorporateAgent::where('uid', $user_id)->exists()) {
            $agent_id = $user_id;
        } elseif ($sub_agent = CorporateSubAgent::where('uid', $user_id)->exists()) {
            $sub_agent_id = $user_id;
        }

        $statements = InternetUsers::query()->where('package_type','wifi')
            ->join('users', 'internet_users.uid', '=', 'users.id')
            ->join('user_profiles', 'internet_users.uid', '=', 'user_profiles.uid')
            ->join('payments', 'internet_users.uid', '=', 'payments.uid');

        $selectColumns = [
            'user_profiles.mobile_number as username',
            'users.text_password as password',
            'internet_users.connection_status as status',
            DB::raw("(SELECT package_name FROM internet_package_corporates WHERE payments.package = internet_package_corporates.package_name) as profile"),
            DB::raw("(SELECT expiration FROM internet_package_corporates WHERE payments.package = internet_package_corporates.package_name) as expiration"),
            DB::raw("(SELECT price FROM internet_package_corporates WHERE payments.package = internet_package_corporates.package_name) as price"),
            'user_profiles.created_at',
            'payments.created_at as payment_date',
            DB::raw('MONTH(payments.created_at) as month'),
            'user_profiles.full_name as customerName',
            'internet_users.broadband_pop_id as popId',
            'payments.amount',
            'internet_users.added_by as added_by',
            DB::raw("(SELECT base_role FROM users WHERE internet_users.added_by = users.id) as base_role"),
            'user_profiles.address',
        ];

        $statements->leftJoin('corporate_clients', 'internet_users.zone_id', '=', 'corporate_clients.uid');
        $statements->leftJoin('corporate_agents', 'internet_users.agent_id', '=', 'corporate_agents.uid');
        $statements->leftJoin('corporate_sub_agents', 'internet_users.sub_agent_id', '=', 'corporate_sub_agents.uid');

        // Add agent_commission - sub_agent_commission as the final commission for the agent
        $selectColumns[] = 'corporate_agents.commission as original_agent_commission';
        $selectColumns[] = 'corporate_sub_agents.commission as sub_agent_commission';
        $selectColumns[] = DB::raw('(corporate_agents.commission - corporate_sub_agents.commission) as agent_commission');

        if ($client) {
            $statements->where('internet_users.zone_id', '=', $client_id);
        } elseif ($agent) {
            $statements->where('internet_users.agent_id', '=', $agent_id);
        } elseif ($sub_agent) {
            $statements->where('internet_users.sub_agent_id', '=', $sub_agent_id);
        }

        $statements->where('payments.transaction_status' , 'Completed')->groupBy('payments.created_at')
            ->orderBy('payments.created_at', 'DESC');

        $returned_data['results']['list'] = $statements->get($selectColumns);


        // Cast status to string in the result array
        foreach ($returned_data['results']['list'] as $result) {
            $result->status = (string) $result->status;
        }

        return ResponseWrapper::End($returned_data);
    }

    public function getAllLatLongPanel(Request $request): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);
        // Helper function to create and execute the query for a given model and parameters
        $fetchData = function ($model, $joinField, $fields, $filters, $request, $urlType, $tableName) {
            $query = $model::query();
            $query->leftJoin("user_profiles", 'user_profiles.uid', '=', $joinField);

            // Apply filters dynamically
            foreach ($filters as $field => $param) {
                if (!empty($request->get($param)) && $request->get($param) != 'undefined') {
                    $query->where($field, $request->get($param));
                }
            }
            // Filter for non-null latitude and longitude
            $query->whereNotNull(
                $tableName == 'sales_agents' ? 'user_profiles.latitude' :
                ($tableName == 'sales_points' ? 'user_profiles.longitude' : "$tableName.latitude")
            )->whereNotNull(
                $tableName == 'sales_agents' ? 'user_profiles.longitude' :
                ($tableName == 'sales_points' ? 'user_profiles.longitude' : "$tableName.longitude")
            );

            // Apply status filter if provided
            if (!empty($request->get('status'))) {
                if ($request->get('status') !== 'all') {
                    $query->where($request->get('status_field'), $request->get('status'));
                }
            }

            // Apply skip and limit if provided
            $totalSkip = $request->get('skip') ?? 0;
            $totalLimit = $request->get('limit') ?? 200;
            $query->orderBy('id', 'desc')->skip($totalSkip)->take($totalLimit);

            // Fetch data and attach `url_type`
            return $query->get(array_merge($fields, [DB::raw("'$urlType' as url_type")]));
        };

        // Define configurations for each model
        $configurations = [
            'network_support_centers' => [
                'model' => NetworkSupportCenter::class,
                'joinField' => 'network_support_centers.uid',
                'fields' => [
                    'network_support_centers.id',
                    'network_support_centers.latitude',
                    'network_support_centers.longitude',
                    'network_support_centers.status',
                    'user_profiles.full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'network_support_centers.division_id' => 'division',
                    'network_support_centers.district_id' => 'district',
                    'network_support_centers.upazila_id' => 'upazila',
                    'network_support_centers.union_id' => 'union',
                    'network_support_centers.village_id' => 'village'
                ],
                'status_field' => 'network_support_centers.status',
                'urlType' => 'support-centers',
                'tableName' => 'network_support_centers'
            ],
            'internet_user' => [
                'model' => InternetUsers::class,
                'joinField' => 'internet_users.uid',
                'fields' => [
                    'internet_users.id',
                    'internet_users.latitude',
                    'internet_users.longitude',
                    'internet_users.connection_status as status',
                    'user_profiles.full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'user_profiles.division_id' => 'division',
                    'user_profiles.district_id' => 'district',
                    'user_profiles.upazila_id' => 'upazila',
                    'user_profiles.union_id' => 'union',
                    'user_profiles.village_id' => 'village'
                ],
                'status_field' => 'internet_users.connection_status',
                'urlType' => 'internet-users',
                'tableName' => 'internet_users'
            ],
            'sales_agent' => [
                'model' => SalesAgent::class,
                'joinField' => 'sales_agents.uid',
                'fields' => [
                    'sales_agents.id',
                    'user_profiles.latitude',
                    'user_profiles.longitude',
                    'sales_agents.status',
                    'user_profiles.full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'user_profiles.division_id' => 'division',
                    'user_profiles.district_id' => 'district',
                    'user_profiles.upazila_id' => 'upazila',
                    'user_profiles.union_id' => 'union',
                    'user_profiles.village_id' => 'village'
                ],
                'status_field' => 'sales_agents.status',
                'urlType' => 'sales-agents',
                'tableName' => 'sales_agents'
            ],
            'sales_point' => [
                'model' => SalesPoint::class,
                'joinField' => 'sales_points.uid',
                'fields' => [
                    'sales_points.id',
                    'sales_points.latitude',
                    'sales_points.longitude',
                    'sales_points.status',
                    'sales_points.store_name as full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'sales_points.division_id' => 'division',
                    'sales_points.district_id' => 'district',
                    'sales_points.upazila_id' => 'upazila',
                    'sales_points.union_id' => 'union',
                    'sales_points.village_id' => 'village'
                ],
                'status_field' => 'sales_points.status',
                'urlType' => 'sales-points',
                'tableName' => 'sales_points'
            ]
        ];

        // Fetch and structure data
        foreach ($configurations as $key => $config) {
            $returned_data['results'][$key] = $fetchData(
                $config['model'],
                $config['joinField'],
                $config['fields'],
                $config['filters'],
                $request,
                $config['urlType'],
                $config['tableName']
            );
        }

        return ResponseWrapper::End($returned_data);
    }

    public function getAllStatusSummaryPanel(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);
        // Helper function to create and execute the query for a given model and parameters

        // Helper function to calculate total, active, and pending counts
        $fetchCounts = function ($model, $joinField, $filters, $statusField, $request) {
            $query = $model::query();
            $query->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN $statusField = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN $statusField = 'pending' THEN 1 ELSE 0 END) as pending
            ");
            $query->leftJoin("user_profiles", 'user_profiles.uid', '=', $joinField);

            // Apply filters dynamically
            foreach ($filters as $field => $param) {
                if (!empty($request->get($param)) && $request->get($param) != 'undefined') {
                    $query->where($field, $request->get($param));
                }
            }

            // Fetch the aggregated counts
            return $query->first();
        };


        // Define configurations for each model
        $configurations = [
            'network_support_centers' => [
                'model' => NetworkSupportCenter::class,
                'joinField' => 'network_support_centers.uid',
                'fields' => [
                    'network_support_centers.id',
                    'network_support_centers.latitude',
                    'network_support_centers.longitude',
                    'network_support_centers.status',
                    'user_profiles.full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'network_support_centers.division_id' => 'division',
                    'network_support_centers.district_id' => 'district',
                    'network_support_centers.upazila_id' => 'upazila',
                    'network_support_centers.union_id' => 'union',
                    'network_support_centers.village_id' => 'village'
                ],
                'status_field' => 'status',
                'urlType' => 'support-centers',
                'tableName' => 'network_support_centers'
            ],
            'internet_user' => [
                'model' => InternetUsers::class,
                'joinField' => 'internet_users.uid',
                'fields' => [
                    'internet_users.id',
                    'internet_users.latitude',
                    'internet_users.longitude',
                    'internet_users.connection_status as status',
                    'user_profiles.full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'user_profiles.division_id' => 'division',
                    'user_profiles.district_id' => 'district',
                    'user_profiles.upazila_id' => 'upazila',
                    'user_profiles.union_id' => 'union',
                    'user_profiles.village_id' => 'village'
                ],
                'status_field' => 'connection_status',
                'urlType' => 'internet-users',
                'tableName' => 'internet_users'
            ],
            'sales_agent' => [
                'model' => SalesAgent::class,
                'joinField' => 'sales_agents.uid',
                'fields' => [
                    'sales_agents.id',
                    'user_profiles.latitude',
                    'user_profiles.longitude',
                    'sales_agents.status',
                    'user_profiles.full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'user_profiles.division_id' => 'division',
                    'user_profiles.district_id' => 'district',
                    'user_profiles.upazila_id' => 'upazila',
                    'user_profiles.union_id' => 'union',
                    'user_profiles.village_id' => 'village'
                ],
                'status_field' => 'status',
                'urlType' => 'sales-agents',
                'tableName' => 'sales_agents'
            ],
            'sales_point' => [
                'model' => SalesPoint::class,
                'joinField' => 'sales_points.uid',
                'fields' => [
                    'sales_points.id',
                    'sales_points.latitude',
                    'sales_points.longitude',
                    'sales_points.status',
                    'sales_points.store_name as full_name',
                    'user_profiles.mobile_number',
                    'user_profiles.email'
                ],
                'filters' => [
                    'sales_points.division_id' => 'division',
                    'sales_points.district_id' => 'district',
                    'sales_points.upazila_id' => 'upazila',
                    'sales_points.union_id' => 'union',
                    'sales_points.village_id' => 'village'
                ],
                'status_field' => 'status',
                'urlType' => 'sales-points',
                'tableName' => 'sales_points'
            ]
        ];

        // Fetch and structure data for each model
        foreach ($configurations as $key => $config) {
            $returned_data['results'][$key] = [
                'counts' => $fetchCounts(
                    $config['model'],
                    $config['joinField'],
                    $config['filters'],
                    $config['status_field'],
                    $request
                )
            ];
        }

        return ResponseWrapper::End($returned_data);
    }

    public function getExpiredUsers(): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        $activationThreshold = now()->subDays(30);

        // Step 1: Get auto-billing client UIDs
        $clientUIDs = CorporateClientsSettings::where('billing_cycle', 'auto')
            ->pluck('client_uid')
            ->toArray();
        Log::info($clientUIDs);
        if (empty($clientUIDs)) {
            Log::info('No clients activated for auto billing.');
            $returned_data['count'] = 0;
            $returned_data['uids'] = [];
            $returned_data['message'] = 'No clients activated for auto billing.';
            return ResponseWrapper::End($returned_data);
        }

        // Step 2: Get eligible users from InternetUsers
        $eligibleUsers = InternetUsers::whereIn('zone_id', $clientUIDs)
            ->where('package_type', 'broadband')
            ->where('activation_date', '<=', $activationThreshold)
            ->get(['uid', 'zone_id']);

        $eligibleUIDs = $eligibleUsers->pluck('uid');

        // Step 3: Get users whose last completed payment is 30+ days ago
        $expiredPayments = Payment::select('uid', DB::raw('MAX(created_at) as last_paid_at'))
            ->whereIn('uid', $eligibleUIDs)
            ->where('transaction_status', 'Completed')
            ->groupBy('uid')
            ->havingRaw('MAX(created_at) <= ?', [$activationThreshold])
            ->get()
            ->keyBy('uid');

        // Step 4: Filter users that are expired and build user + client data
        $expiredUsers = $eligibleUsers->filter(function ($user) use ($expiredPayments) {
            return $expiredPayments->has($user->uid);
        });

        // Get auth IDs in one go
        $authIds = User::whereIn('id', $expiredUsers->pluck('uid'))->pluck('auth_id', 'id');

        // Group expired users by zone_id
        $groupedByZone = $expiredUsers->groupBy('zone_id');

        // Get MikroTik info for all clients
        $mikrotikClients = CorporateClient::whereIn('uid', array_keys($groupedByZone->toArray()))
            ->get(['uid', 'mikrotik_ip', 'mikrotik_username', 'mikrotik_password'])
            ->keyBy('uid');

        $disabledUsernames = [];

        foreach ($groupedByZone as $zoneId => $users) {
            $client = $mikrotikClients->get($zoneId);
            Log::info($client);
            if (!$client) {
                continue; // Skip this zone if no MikroTik info
            }

            // $API = new RouterosAPI();
            // if (!$API->connect($client->mikrotik_ip, $client->mikrotik_username, $client->mikrotik_password)) {
            //     continue; // Skip if connection fails
            // }

            foreach ($users as $user) {
                $username = $authIds->get($user->uid);
                Log::info($username);
                if (!$username) continue;

                // // Disable PPP Secret
                // $arrID = $API->comm("/ppp/secret/print", [
                //     ".proplist" => ".id",
                //     "?name" => $username,
                // ]);

                // if (!empty($arrID)) {
                //     $API->comm("/ppp/secret/disable", [
                //         ".id" => $arrID[0][".id"]
                //     ]);
                // }

                // // Remove from active session
                // $activeID = $API->comm("/ppp/active/getall", [
                //     ".proplist" => ".id",
                //     "?name" => $username,
                // ]);

                // if (!empty($activeID)) {
                //     $API->comm("/ppp/active/remove", [
                //         ".id" => $activeID[0][".id"]
                //     ]);
                // }

                // // Update database
                // BroadbandDbSecret::where('username', $username)
                //     ->update(['status' => 1, 'dateOf_Inactive' => now()]);

                // InternetUsers::where('uid', $user->uid)
                //     ->update([
                //         'connection_status' => 'inactive',
                //         'updated_at' => now(),
                //     ]);

                $disabledUsernames[] = $username;
            }

            // $API->disconnect(); // Disconnect only once per router
        }

        $returned_data['count'] = count($disabledUsernames);
        $returned_data['uids'] = $disabledUsernames;

        return ResponseWrapper::End($returned_data);
    }
}
