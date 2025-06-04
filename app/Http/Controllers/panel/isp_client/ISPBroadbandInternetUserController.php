<?php

namespace App\Http\Controllers\panel\isp_client;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Models\AffiliateHistory;
use App\Imports\ISPClientBroadbandUserBulkRegisterImport;
use App\Models\AgentCommissionBreakdown;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\BroadbandDbUsers;
use App\Models\BroadbandDbZone;
use App\Models\CorporateAgent;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\MessageAndNotification;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ISPBroadbandInternetUserController extends Controller
{
    public function getBroadbandInternetUserListISP($uid) : JsonResponse  {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $uid)->exists();
        $agent = CorporateAgent::where('uid', $uid)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $uid)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        if($sub_agent){
            $client_id_from_sub_agent = CorporateSubAgent::where('uid', '=', $uid)->value('client_id');
            $mkInfo = CorporateClient::where('uid', '=', $client_id_from_sub_agent)->get();
        }elseif($agent){
            $client_id_from_agent = CorporateAgent::where('uid', '=', $uid)->value('client_id');
            $mkInfo = CorporateClient::where('uid', '=', $client_id_from_agent)->get();
        }elseif($client){
            $mkInfo = CorporateClient::where('uid', '=', $uid)->get();
        }

        // API Variables
        $ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
        $mkUser = $mkInfo->implode('mikrotik_username', ', ');
        $mkPass = $mkInfo->implode('mikrotik_password', ', ');
        $API = new RouterOsApi();

        // Connecting API
        $pppoeItems = [];
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            $ARRAY = $API->comm('/ppp/secret/print');
            $ARRAYCONNECTED = $API->comm('/ppp/active/print');

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
                        'status' => $item['disabled'] === "false" ? 'Active':'Inactive',
                        'uptime' => $uptime,
                    ];
                }
            }
        }

        // Fetching broadband Internet User List
        $broadbandInternetUserList = UserProfile::query()
        ->join('internet_users', 'user_profiles.uid', '=', 'internet_users.uid')
        ->select(
            'user_profiles.uid',
            'user_profiles.full_name',
            'internet_users.package_id',
            DB::raw("(SELECT package_name FROM internet_package_corporates WHERE id = internet_users.package_id) as package_name"),
            'internet_users.added_by as added_by',
            DB::raw("(SELECT base_role FROM users WHERE users.id = internet_users.added_by) as owner_type"),
            'user_profiles.wallet_amount',
            'user_profiles.mobile_number',
            'user_profiles.whatsapp_number',
            'user_profiles.email',
            'user_profiles.profession',
            'user_profiles.nid',
            'user_profiles.gender',
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
            'internet_users.connection_media',
            'internet_users.connection_status',
            'internet_users.broadband_pop_id as popId',
            'internet_users.user_type as UserType',
            'internet_users.created_at as activation_date'
        );
        $broadbandInternetUserList = $broadbandInternetUserList->where('internet_users.package_type', 'broadband');
        //$broadbandInternetUserList->where('internet_users.added_by', '=', $uid);
        // if($sub_agent){
        //     $broadbandInternetUserList->where('internet_users.sub_agent_id', '=', $uid);
        // }elseif($agent){
        //     $broadbandInternetUserList->where('internet_users.agent_id', '=', $uid);
        // }elseif($client){
        //     $broadbandInternetUserList->where('internet_users.zone_id', '=', $uid);
        // }

        if ($client) {
            $client_id = $uid;
            $agent_ids = CorporateAgent::where('client_id', $client_id)->pluck('uid')->toArray();

            $sub_agent_ids = CorporateSubAgent::whereIn('client_id', [$client_id])->orWhereIn('agent_id', $agent_ids)->pluck('uid')->toArray();
            $broadbandInternetUserList->where('internet_users.added_by', '=', $client_id)
                ->orWhereIn('internet_users.added_by', $agent_ids)
                ->orWhereIn('internet_users.added_by', $sub_agent_ids)->get();
        } elseif ($agent) {
            $agent_id = $uid;
            $sub_agent_ids = CorporateSubAgent::where('agent_id', $agent_id)->pluck('uid')->toArray();
            $broadbandInternetUserList->where('internet_users.added_by', '=', $agent_id)
                ->orWhereIn('internet_users.added_by', $sub_agent_ids);
        } elseif ($sub_agent) {
            $sub_agent_id = $uid;
            $broadbandInternetUserList->where('internet_users.added_by', '=', $sub_agent_id);
        }

        // $addedByIds = [];

        // if ($client) {
        //     $client_id = $uid;
        
        //     $agent_ids = CorporateAgent::where('client_id', $client_id)->pluck('uid');
        //     $sub_agent_ids = CorporateSubAgent::whereIn('client_id', [$client_id])
        //                         ->orWhereIn('agent_id', $agent_ids)->pluck('uid');
        
        //     $addedByIds = collect([$client_id])
        //         ->merge($agent_ids)
        //         ->merge($sub_agent_ids)
        //         ->unique()
        //         ->toArray();
        
        // } elseif ($agent) {
        //     $agent_id = $uid;
        
        //     $sub_agent_ids = CorporateSubAgent::where('agent_id', $agent_id)->pluck('uid');
        
        //     $addedByIds = collect([$agent_id])
        //         ->merge($sub_agent_ids)
        //         ->unique()
        //         ->toArray();
        
        // } elseif ($sub_agent) {
        //     $addedByIds = [$uid];
        // }
        
        // if (!empty($addedByIds)) {
        //     $broadbandInternetUserList->whereIn('internet_users.added_by', $addedByIds);
        // }

    
        $broadbandInternetUserList = $broadbandInternetUserList
            ->groupBy('user_profiles.uid')
            ->orderBy('user_profiles.created_at', 'DESC')
            ->get();

        // Adding uptime information to broadbandInternetUserList
        $broadbandInternetUserList->transform(function ($user) use ($pppoeItems) {
            $user->uptime = null;
            $user->status = 'Inactive';
            foreach ($pppoeItems as $pppoeItem) {
                if ($user->mobile_number == $pppoeItem['name']) {
                    $user->uptime = $pppoeItem['uptime'];
                    $user->status = $pppoeItem['status'];
                    $user->password = $pppoeItem['password'];
                    break;
                }
            }
            return $user;
        });

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $broadbandInternetUserList;
        return ResponseWrapper::End($returned_data);
    }

    // create broadband user ------
    public function createBroadbandInternetUserISP(Request $request, $client_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $client_id)->exists();
        $agent = CorporateAgent::where('uid', $client_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $client_id)->exists();

        // Validate the request input
        $request->validate([
            'full_name' => 'required',
            'mobile_number' => 'required',
            'nid' => 'required',
            'gender' => 'required',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'house' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'package' => 'required',
            'pop' => 'required',
            'ins_cost' => 'required',
            'connection_media' => 'required',
            'user_type' => 'required',
            'division_name' => 'required',
            'district_name' => 'required',
            'upazila_name' => 'required',
        ]);

        if($sub_agent){
            $client_id_from_sub_agent = CorporateSubAgent::where('uid', '=', $client_id)->value('client_id');
            $mkInfo = CorporateClient::where('uid', '=', $client_id_from_sub_agent)->get();
        }elseif($agent){
            $client_id_from_agent = CorporateAgent::where('uid', '=', $client_id)->value('client_id');
            $mkInfo = CorporateClient::where('uid', '=', $client_id_from_agent)->get();
        }elseif($client){
            $mkInfo = CorporateClient::where('uid', '=', $client_id)->get();
        }

        // Check if branch information exists
        if (!$mkInfo) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Zone information not found!';
            return ResponseWrapper::End($returned_data);
        }

        // API Variables
        $ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
        $mkUser = $mkInfo->implode('mikrotik_username', ', ');
        $mkPass = $mkInfo->implode('mikrotik_password', ', ');
        $API = new RouterOsApi();

        // Connect to MikroTik Router
        if (!$API->connect($ipAddr, $mkUser, $mkPass)) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "You zone is down.";
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
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user', 'broadband');
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                // create new profile
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $request->get('full_name');
                $userProfile->mobile_number = $request->get('mobile_number');
                $userProfile->whatsapp_number = $request->get('whatsapp_number');
                $userProfile->email = $request->get('email');
                $userProfile->profession = $request->get('profession');
                $userProfile->nid = $request->get('nid');
                $userProfile->gender = $request->get('gender');
                $userProfile->division_id = $request->get('division');
                $userProfile->district_id = $request->get('district');
                $userProfile->upazila_id = $request->get('upazila');
                $userProfile->union_id = $request->get('union');
                $userProfile->village_id = $request->get('village');
                $userProfile->house_no = $request->get('house');
                $userProfile->address = $request->get('address');
                $userProfile->address_direction = $request->get('address_direction');
                $userProfile->latitude = $request->get('latitude');
                $userProfile->longitude = $request->get('longitude');
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                // data for internet user table --------------------
                $internetUser = new InternetUsers();
                $internetUser->uid = $uid;
                if($sub_agent){
                    $client = CorporateSubAgent::where('uid',$client_id)->value('client_id');
                    $agent = CorporateSubAgent::where('uid',$client_id)->value('agent_id');
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $agent;
                    $internetUser->sub_agent_id = $client_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$client_id)->value('client_id');
                    $internetUser->zone_id = $client;
                    $internetUser->agent_id = $client_id;
                }elseif($client){
                    $internetUser->zone_id = $client_id;
                }
                $internetUser->added_by = $client_id;
                $internetUser->package_id = $request->get('package');
                $internetUser->package_type = 'broadband';
                $internetUser->latitude = $request->get('latitude');
                $internetUser->longitude = $request->get('longitude');
                $internetUser->password_broadband = $password;
                $internetUser->user_type = $request->get('user_type');
                $internetUser->billing_address = $request->get('address');
                $internetUser->broadband_pop_id = $request->get('pop');
                $internetUser->connection_media = $request->get('connection_media');
                $internetUser->installation_charge = $request->get('ins_cost');
                $internetUser->connection_status = 'pending';
                $internetUser->save();

                // package data fetching ----
                $packages = InternetPackageCorporate::where('id',$request->get('package'))->first();

                // data for subscriber info table ----------------
                $subscriber_info = BroadbandDbSubscriberInfo::where('numAsId', $request->get('mobile_number'))->exists();
                if (!$subscriber_info) {
                    $subscriber_info = new BroadbandDbSubscriberInfo();
                    $subscriber_info->serial = $uid;
                    $subscriber_info->popId = $request->get('pop');
                    $subscriber_info->date = date('Y-m-d');

                    if($sub_agent){
                        $sub_agent_client_id = CorporateSubAgent::where('uid',$client_id)->value('client_id');
                        $subscriber_info->zone_name = CorporateClient::where('uid', $sub_agent_client_id)->value('zone_name');
                    }elseif($agent){
                        $agent_client_id = CorporateAgent::where('uid',$client_id)->value('client_id');
                        $subscriber_info->zone_name = CorporateClient::where('uid', $agent_client_id)->value('zone_name');
                    }elseif($client){
                        $subscriber_info->zone_name = CorporateClient::where('uid', $client_id)->value('zone_name');
                    }

                    $subscriber_info->numAsId = $request->get('mobile_number');
                    $subscriber_info->packageId = $packages->package_name;
                    $subscriber_info->customerName = $request->get('full_name');
                    $subscriber_info->gender = $request->get('gender');
                    $subscriber_info->instcost = $request->get('ins_cost');
                    $subscriber_info->home = $request->get('house');
                    $subscriber_info->village = $request->get('village');
                    $subscriber_info->police_station = $request->get('upazila_name');
                    $subscriber_info->district = $request->get('district_name');
                    $subscriber_info->division = $request->get('division_name');
                    $subscriber_info->billingAddress = $request->get('address');
                    $subscriber_info->nid = $request->get('nid');
                    $subscriber_info->numOne = $request->get('mobile_number');
                    $subscriber_info->email = $request->get('email');
                    $subscriber_info->UserType = $request->get('user_type');
                    $subscriber_info->connectionMedia = $request->get('connection_media');
                    $subscriber_info->acativation_date = Carbon::now();
                    $subscriber_info->save();
                }

                // Finding the days left of the current month
                $currentDayOfMonth = date('j');
                $totalDaysInMonth = date('t');
                $daysleft = ($totalDaysInMonth-$currentDayOfMonth)+1;

                // Calculating package price
                $price = $packages->price;
                $packageFinalFloat = $daysleft*($price / $totalDaysInMonth);
                $finalPackagePrice = (int)$packageFinalFloat;

                $returned_data['status'] = 'success';
                $returned_data['results'] = [
                    'uid' => $uid,
                    'full_name' => $request->get('full_name'),
                    'mobile_number' => $request->get('mobile_number'),
                    'package' => $packages->package_name,
                    'days_left' => $daysleft,
                    'final_price' => $finalPackagePrice
                ];
            }
        }
        return ResponseWrapper::End($returned_data);
    }

    // confirm payment --------
    public function confirmBroadbandInternetUserISPPayment(Request $request, $uid): JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Variables
        $user_id = $uid;
        $mobile = $request->get('mobile_number');
        $package = $request->get('package');
        $internet_user_id = User::where('auth_id', $mobile)->value('id');
        $password = InternetUsers::where('uid', $internet_user_id)->value('password_broadband');

        // Checking User
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();
        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        // Fetch branch information && Check if branch information exists
        if($sub_agent){
            $client_id_from_sub_agent = CorporateSubAgent::where('uid', '=', $user_id)->value('client_id');
            $mkInfo = CorporateClient::where('uid', '=', $client_id_from_sub_agent)->get();
        }elseif($agent){
            $client_id_from_agent = CorporateAgent::where('uid', '=', $user_id)->value('client_id');
            $mkInfo = CorporateClient::where('uid', '=', $client_id_from_agent)->get();
        }elseif($client){
            $mkInfo = CorporateClient::where('uid', '=', $user_id)->get();
        }

        $currentYear = Date('Y');
        $currentMonth = Date('m');
        $checkPayment = Payment::where('uid', $internet_user_id)->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->where('transaction_status','Completed')->exists();
        
        //dd();
        if ($checkPayment) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Paid for this month!';
            return ResponseWrapper::End($returned_data);
        }

        if (!$mkInfo) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Zone information not found!';
            return ResponseWrapper::End($returned_data);
        }

        // API Variables
        $ipAddr = $mkInfo->implode('mikrotik_ip', ', ');
        $mkUser = $mkInfo->implode('mikrotik_username', ', ');
        $mkPass = $mkInfo->implode('mikrotik_password', ', ');
        $API = new RouterOsApi();

        // checking user is old
        $oldUser = BroadbandDbSecret::where('username', $mobile)->exists();

          // Connect to MikroTik Router
          if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            if($oldUser){
                $arrID=$API->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $mobile));
                $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                $API->disconnect();
            }else{
                $ARRAY = $API->comm('/ppp/secret/add',
                array('name' => $mobile,
                        'password' => $password,
                        'service' => 'pppoe',
                        'profile' => $package));
                $API->disconnect();
            }

        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Failed to connect to MikroTik Router!';
            return ResponseWrapper::End($returned_data);
        }

        // Panel Money check
        $user_type = $client ? 'client' : ($agent ? 'agent' : 'sub_agent');
        $user_model = $user_type === 'client' ? CorporateClient::class : ($user_type === 'agent' ? CorporateAgent::class : CorporateSubAgent::class);
        $pre_balance = $user_model::where('uid', $user_id)->value('balance');
        $package_estimated_price = (int) $request->get('final_price');
        if ($pre_balance <= $package_estimated_price) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Insufficient Balance';
            return ResponseWrapper::End($returned_data);
        }
        $user_model::where('uid', $user_id)->update([
            'balance' => $pre_balance - $package_estimated_price
        ]);

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');
        $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

        // Save Payment
        $paymentForBackend = new Payment();
        $paymentForBackend->uid = $internet_user_id;
        $paymentForBackend->zone_id = $user_id;
        $paymentForBackend->vendor_name = 'panel_money';
        $paymentForBackend->trx_id = $trxID;
        $paymentForBackend->invoice_number = $invoiceID;
        $paymentForBackend->amount = $package_estimated_price;
        $paymentForBackend->payment_id = $paymentID;
        $paymentForBackend->process_status = '1';
        $paymentForBackend->purpose = 'new_user_internet_bill_payment';
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
        $transactionForBackend->sender_uid = $user_id;
        $transactionForBackend->receiver_uid = $user_id;
        $transactionForBackend->method = 'panel_money';
        $transactionForBackend->amount = $package_estimated_price;
        $transactionForBackend->purpose = 'new_user_internet_bill_payment';

        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $added_by = InternetUsers::where('uid', $internet_user_id)->value('added_by');
        $added_by_user_table = User::where('id', $added_by)->first();
        if ($added_by_user_table->base_role === 'agent' || $added_by_user_table->base_role === 'sub_agent') {
            $previous_wallet_amount = UserProfile::where('uid', $added_by)->value('wallet_amount');
            //$commission_amount = CustomHelpers::getCommissionAmount($added_by_user_table, $package_estimated_price);

            // Save Affiliate History
            $affiliate_history = new AffiliateHistory();
            $affiliate_history->affiliator_uid = $added_by;
            $affiliate_history->product_type = 'internet_package';
            $affiliate_history->product_id = $internet_user_id;
            $affiliate_history->status = 'approved';
            $affiliate_history->commission_amount = $package_estimated_price * (20 / 100);
            if (!$affiliate_history->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
                return ResponseWrapper::End($returned_data);
            }

            // Save Agent Commission Breakdown
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = $added_by;
            $agentCommissionForBackend->user_uid = $internet_user_id;
            $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $package_estimated_price * (20 / 100);;
            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }

            UserProfile::where('uid', $added_by)->update([
                'wallet_amount' => $previous_wallet_amount + $package_estimated_price * (20 / 100)
            ]);
        }

        // Save Mikrotik Payment
        $paymentForMikrotik = new BroadbandDbPayment();
        $paymentForMikrotik->username = $mobile;
        $paymentForMikrotik->amount = $package_estimated_price;
        $paymentForMikrotik->type = 'panelMoney';
        $paymentForMikrotik->month = date('n');
        $paymentForMikrotik->submited_by = $user_id;
        $paymentForMikrotik->payment_date = Carbon::now();
        $paymentForMikrotik->last_update = Carbon::now();

        if (!$paymentForMikrotik->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Mikrotik Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $secretCheck = BroadbandDbSecret::where('username', $mobile)->exists();
        if($secretCheck){
            $paymentForMikrotikSecret = BroadbandDbSecret::where('username', $mobile)->first();
            $paymentForMikrotikSecret->profile = $request->get('package');
            $paymentForMikrotikSecret->type = 'corporate_payment';

            $updateData = [];
            if ($agent || $sub_agent) {
                if ($agent) {
                    $clientId = CorporateAgent::where('uid', $user_id)->value('client_id');
                    $agentId = $user_id;
                    $subAgentId = null;
                } else {
                    $clientId = CorporateSubAgent::where('uid', $user_id)->value('client_id');
                    $agentId = CorporateSubAgent::where('uid', $user_id)->value('agent_id');
                    $subAgentId = $user_id;
                }
                $updateData = ['client_id' => $clientId,'agent_id' => $agentId,'sub_agent_id' => $subAgentId];
            } else {
                $updateData = [
                    'client_id' => $user_id,
                ];
            }

            $paymentForMikrotikSecret->update($updateData);
        }else{
            // Save Mikrotik Secret
            $paymentForMikrotikSecret = new BroadbandDbSecret();
            $paymentForMikrotikSecret->username = $mobile;
            $paymentForMikrotikSecret->password = $password;
            $paymentForMikrotikSecret->profile = $request->get('package');
            $paymentForMikrotikSecret->service = 'pppoe';

            if($sub_agent){
                $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$user_id)->value('client_id');
                $paymentForMikrotikSecret->zone = CorporateClient::where('uid', $editor_id_from_sub_agent)->value('zone_name');
            }elseif($agent){
                $editor_id_from_agent = CorporateAgent::where('uid',$user_id)->value('client_id');
                $paymentForMikrotikSecret->zone = CorporateClient::where('uid', $editor_id_from_agent)->value('zone_name');
            }elseif($client){
                $paymentForMikrotikSecret->zone = CorporateClient::where('uid', $user_id)->value('zone_name');
            }

            $paymentForMikrotikSecret->status = '0';
            $paymentForMikrotikSecret->type = 'corporate_payment';
            $paymentForMikrotikSecret->dateOf_Inactive = Carbon::now()->endOfMonth();

            if ($agent || $sub_agent) {
                $paymentForMikrotikSecret->client_id = $agent ? CorporateAgent::where('uid', $user_id)->value('client_id') : CorporateSubAgent::where('uid', $user_id)->value('client_id');
                $paymentForMikrotikSecret->agent_id = $sub_agent ? CorporateSubAgent::where('uid', $user_id)->value('agent_id') : $user_id;
                $paymentForMikrotikSecret->sub_agent_id = $sub_agent ? $user_id : null;
            } else {
                $paymentForMikrotikSecret->client_id = $user_id;
            }

            if (!$paymentForMikrotikSecret->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Mikrotik Secret Storing!';
                return ResponseWrapper::End($returned_data);
            }
        }

        InternetUsers::where('uid', $internet_user_id)->update([
            'connection_status' => 'active'
        ]);

        // Save Mikrotik User
        $mikrotikUser = new BroadbandDbUsers();
        $mikrotikUser->username = $mobile;
        $mikrotikUser->mobileno = $mobile;
        $mikrotikUser->password = $mobile;

        if (!$mikrotikUser->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Mikrotik Users Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $notification = new MessageAndNotification();
        $notification->uid = $internet_user_id;
        $notification->title = 'ISP Broadband Internet User Create';
        $notification->description = 'স্বাগতম! স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।';
        $notification->sender_uid = $user_id;
        $notification->is_read = 0;
        $notification->save();

        $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$mobile." এবং পাসওয়ার্ড: " . $password;
        $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile, $smsText);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Payment processed successfully';
        $returned_data['results'] = [
            'transaction_id' => $trxID,
            'invoice_id' => $invoiceID,
            'payment_id' => $paymentID,
            'full_name' => $request->get('full_name'),
            'mobile_number' => $mobile,
            'package' => $request->get('package'),
            'days_left' => $request->get('days_left'),
            'final_price' => $request->get('final_price'),
            'payment_method' => 'Panel Money',
            'payment_date' => Carbon::now()
        ];
        return ResponseWrapper::End($returned_data);
    }

    // update internet user -------------
    public function updateBroadbandInternetUserISP(Request $request, $editor_id, $internet_user_id) : JsonResponse {
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
            'email' => 'required|email',
            'gender' => 'required',
            // 'profession' => 'required',
            'nid' => 'required',
            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'house' => 'required',
            // 'post_code' => 'required',
            'address_direction' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'pop' => 'required',
            'division_name' => 'required',
            'district_name' => 'required',
            'upazila_name' => 'required'
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
                'profession' => $request->get('profession'),
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
                'broadband_pop_id' => $request->get('pop')
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went wrong, Data not stored in Internet Users table!";
            return ResponseWrapper::End($returned_data);
        }

        $subscriber_info = BroadbandDbSubscriberInfo::where('serial', $internet_user_id)->first();
        if ($subscriber_info) {
            $subscriber_info->update([
                'popId' => $request->get('pop'),
                'customerName' => $request->get('full_name'),
                'gender' => $request->get('gender'),
                'home' => $request->get('house'),
                'village' => $request->get('village'),
                // 'post_office' => $request->get('post_code'),
                'police_station' => $request->get('upazila_name'),
                'district' => $request->get('district_name'),
                'division' => $request->get('division_name'),
                'billingAddress' => $request->get('address'),
                'nid' => $request->get('nid'),
                'email' => $request->get('email')
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went wrong, Data not stored in Subscriber Info table!";
            return ResponseWrapper::End($returned_data);
        }

        $user_mobile = User::where('id', $internet_user_id)->value('auth_id');
        $secret = BroadbandDbSecret::where('username', $user_mobile)->first();
        if ($secret) {
            $secret->update([
                'password' => $request->get('password')
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Something went wrong, Data not stored in Secret table!";
            return ResponseWrapper::End($returned_data);
        }

        $password = User::where('id', $internet_user_id)->value('text_password');

        $returned_data['status'] = 'success';
        $returned_data['message'] = "আপনি ইন্টারনেট ইউজারের তথ্য আপডেট করেছেন। ইন্টারনেট ইউজারের Username : " . $user_mobile . " এবং Password :" . $password;
        return ResponseWrapper::End($returned_data);
    }

    // delete internet user  -------------
    public function deleteBroadbandInternetUserISP($editor_id, $internet_user_id) : JsonResponse {
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
        $profileDeleted = UserProfile::where('uid', $internet_user_id)->delete();
        $internetUser = InternetUsers::where('uid', $internet_user_id)->delete();
        $subscriberInfo = BroadbandDbSubscriberInfo::where('numAsId', $internet_user_mobile_number)->delete();
        $secret = BroadbandDbSecret::where('username', $internet_user_mobile_number)->delete();
        $userDeleted = User::where('id', $internet_user_id)->delete();
        $userFromMikrotik = BroadbandDbUsers::where('username', $internet_user_mobile_number)->delete();

        if ($userDeleted && $profileDeleted && $internetUser && $subscriberInfo && $secret && $userFromMikrotik) {
            $returned_data['results'] = true;
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Broadband User deleted successfully!";
            return ResponseWrapper::End($returned_data);
        } else {
            $returned_data['results'] = false;
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Try again something went wrong!";
            return ResponseWrapper::End($returned_data);
        }
    }

    // bill entry & package update --------
    public function billEntryBroadbandInternetUserISP(Request $request, $editor_id, $internet_user_id) : JsonResponse {
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

        // Checking if the user has paid for the current month
        //$internet_user_id = User::where('auth_id',$request->get('mobile_number'))->value('id');
        
        $currentYear = Carbon::now()->format('Y');
        $currentMonth = Carbon::now()->format('m');
        $checkPayment = Payment::where('uid', $internet_user_id)->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->where('transaction_status','Completed')->exists();

        if ($checkPayment) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Paid for this month!';
            return ResponseWrapper::End($returned_data);
        }

        // Panel Money check
        $user_type = $client ? 'client' : ($agent ? 'agent' : 'sub_agent');
        $user_model = $user_type === 'client' ? CorporateClient::class : ($user_type === 'agent' ? CorporateAgent::class : CorporateSubAgent::class);
        $pre_balance = $user_model::where('uid', $editor_id)->value('balance');
        $package_estimated_price = (int) $request->get('final_price');
        if ($pre_balance <= $package_estimated_price) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Insufficient Balance';
            return ResponseWrapper::End($returned_data);
        }

        // Fetch branch information
        if($sub_agent){
            $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
            $branchInfo = CorporateClient::where('uid', '=', $editor_id_from_sub_agent)->get();
        }elseif($agent){
            $editor_id_from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
            $branchInfo = CorporateClient::where('uid', '=', $editor_id_from_agent)->get();
        }elseif($client){
            $branchInfo = CorporateClient::where('uid', '=', $editor_id)->get();
        }

        // Check if branch information exists
        if (!$branchInfo) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Zone information not found!';
            return ResponseWrapper::End($returned_data);
        }

        // // Api Variables
        $ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
        $mkUser = $branchInfo->implode('mikrotik_username', ', ');
        $mkPass = $branchInfo->implode('mikrotik_password', ', ');

        // $oldUser = BroadbandDbSecret::where('username', $request->get('mobile_number'))->exists();
        $password = User::where('id',$internet_user_id)->value('text_password');
        $API = new RouterOsApi();

        // Connect to MikroTik Router
        $oldPackage = BroadbandDbSecret::where('username', $request->get('mobile_number'))->value('profile');
        // Connect to MikroTik Router
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            if($oldPackage !== $request->get('package')){
                $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id","?name" => $request->get('mobile_number')));
                $API->comm("/ppp/secret/set", array(".id" => $arrID[0][".id"], "name"=> $request->get('mobile_number'), "password" => $password, "service" => 'pppoe' , "profile" => $request->get('package')));

                $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                $API->disconnect();
            }else{
                $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $request->get('mobile_number')));
                $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                $API->disconnect();
            }
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Failed to connect to MikroTik Router!';
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
                'connection_status' => 'active'
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
        $paymentForBackend->purpose = 'broadband_internet_bill_payment';
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
        $transactionForBackend->purpose = 'new_user_internet_bill_payment';

        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }
        $user_id = $editor_id;
        $uid = $internet_user_id;

        $added_by = InternetUsers::where('uid', $internet_user_id)->value('added_by');
        $added_by_user_table = User::where('id', $added_by)->first();
        if ($added_by_user_table->base_role === 'agent' || $added_by_user_table->base_role === 'sub_agent') {
            $previous_wallet_amount = UserProfile::where('uid', $added_by)->value('wallet_amount');
            $commission_amount = CustomHelpers::getCommissionAmount($added_by_user_table, $package_estimated_price);

            // Save Affiliate History
            $affiliate_history = new AffiliateHistory();
            $affiliate_history->affiliator_uid = $added_by;
            $affiliate_history->product_type = 'internet_package';
            $affiliate_history->product_id = $internet_user_id;
            $affiliate_history->status = 'approved';
            $affiliate_history->commission_amount = $commission_amount;
            if (!$affiliate_history->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
                return ResponseWrapper::End($returned_data);
            }

            // Save Agent Commission Breakdown
            $agentCommissionForBackend = new AgentCommissionBreakdown();
            $agentCommissionForBackend->agent_uid = $added_by;
            $agentCommissionForBackend->user_uid = $internet_user_id;
            $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
            $agentCommissionForBackend->new_commission_amount = $commission_amount;
            if (!$agentCommissionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                return ResponseWrapper::End($returned_data);
            }

            UserProfile::where('uid', $added_by)->update([
                'wallet_amount' => $previous_wallet_amount + $commission_amount
            ]);
        }

        // Save Mikrotik Payment
        $paymentForMikrotik = new BroadbandDbPayment();
        $paymentForMikrotik->username = $request->get('mobile_number');
        $paymentForMikrotik->amount = $package_estimated_price;
        $paymentForMikrotik->type = 'panelMoney';
        $paymentForMikrotik->month = date('n');
        $paymentForMikrotik->submited_by = $editor_id;
        $paymentForMikrotik->payment_date = Carbon::now();
        $paymentForMikrotik->last_update = Carbon::now();

        if (!$paymentForMikrotik->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Mikrotik Payment Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $mikrotikSecret = BroadbandDbSecret::where('username',$request->get('mobile_number'))->exists();
        if($mikrotikSecret){
            Log::info($request->get('mobile_number'));
            BroadbandDbSecret::where('username',$request->get('mobile_number'))->update([
                'status' => '0',
                'profile' => $request->get('package')
            ]);
        }else{
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Mikrotik Secret updating!';
            return ResponseWrapper::End($returned_data);
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Payment processed successfully';
        $returned_data['results'] = [
            'transaction_id' => $trxID,
            'invoice_id' => $invoiceID,
            'payment_id' => $paymentID,
            'full_name' => $request->get('full_name'),
            'mobile_number' => $request->get('mobile_number'),
            'package' => $request->get('package'),
            'final_price' => $request->get('final_price'),
            'payment_method' => 'Panel Money',
            'payment_date' => Carbon::now()
        ];
        return ResponseWrapper::End($returned_data);
    }

    // create broadband user ------
    public function createBroadbandInternetUserISPExisting(Request $request, $client_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $client_id)->exists();
        $agent = CorporateAgent::where('uid', $client_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $client_id)->exists();

        if( !$client && !$agent && !$sub_agent){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You are not allowed!';
            return ResponseWrapper::End($returned_data);
        }

        // Fetch branch information
       if($sub_agent){
            $client_id_from_sub_agent = CorporateSubAgent::where('uid', '=', $client_id)->value('client_id');
            $branchInfo = CorporateClient::where('uid', '=', $client_id_from_sub_agent)->get();
        }elseif($agent){
            $client_id_from_agent = CorporateAgent::where('uid', '=', $client_id)->value('client_id');
            $branchInfo = CorporateClient::where('uid', '=', $client_id_from_agent)->get();
        }elseif($client){
            $branchInfo = CorporateClient::where('uid', '=', $client_id)->get();
        }

        if (!$branchInfo) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Zone information not found!';
            return ResponseWrapper::End($returned_data);
        }

        // Variables --
        $uid = $request->get('uid');
        $generatedPassword = (new \App\Classes\CustomHelpers)->generate_new_password(6);

        $isUserActive = InternetUsers::where('uid',$uid)->value('connection_status');
        if($isUserActive === 'Active'){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "This user is under another zone!";
            return ResponseWrapper::End($returned_data);
        }else{
            // Update Internet User table
            $internetUserData = InternetUsers::where('uid',$uid)->first();
            if($client){
                $internetUserData->zone_id = $client_id;
            }elseif($agent){
                $client = CorporateAgent::where('uid',$client_id)->value('client_id');
                $internetUserData->zone_id = $client;
                $internetUserData->agent_id = $client_id;
            }elseif($sub_agent){
                $client = CorporateSubAgent::where('uid',$client_id)->value('client_id');
                $agent = CorporateSubAgent::where('uid',$client_id)->value('agent_id');
                $internetUserData->zone_id = $client;
                $internetUserData->agent_id = $agent;
                $internetUserData->sub_agent_id = $client_id;
            }
            $internetUserData->added_by = $client_id;
            $internetUserData->package_id = $request->get('package');
            $internetUserData->package_type = 'broadband';
            $internetUserData->password = $generatedPassword;
            $internetUserData->password_broadband = $generatedPassword;
            $internetUserData->user_type = $request->get('user_type');
            $internetUserData->broadband_pop_id = GeoUpazila::where('id', $request->get('upazila'))->value('en_name');
            $internetUserData->connection_media = $request->get('connection_media');
            $internetUserData->installation_charge = $request->get('ins_cost');
            $internetUserData->connection_status = 'pending';
            $internetUserData->save();

            $subscriber_info_data = BroadbandDbSubscriberInfo::where('numAsId',$request->get('mobile_number'))->exists();
            if($subscriber_info_data){
                $subscriber_info = BroadbandDbSubscriberInfo::where('numAsId',$request->get('mobile_number'))->first();
                $subscriber_info->serial = $uid;
                $subscriber_info->popId = GeoUpazila::where('id', $request->get('upazila'))->value('en_name');
                $subscriber_info->date = date('Y-m-d');
                if($client){
                    $subscriber_info->zone_name = $client_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$client_id)->value('client_id');
                    $subscriber_info->zone_name = $client;
                }elseif($sub_agent){
                    $client = CorporateSubAgent::where('uid',$client_id)->value('client_id');
                    $subscriber_info->zone_name = $client;
                }
                $subscriber_info->numAsId = $request->get('mobile_number');

                // package data fetching ----
                $packages = InternetPackageCorporate::where('id',$request->get('package'))->first();

                $subscriber_info->packageId = $packages->package_name;
                $subscriber_info->customerName = $request->get('full_name');
                $subscriber_info->gender = $request->get('gender') ?? 'Male';
                $subscriber_info->instcost = $request->get('ins_cost'); //
                $subscriber_info->home = $request->get('house_no');
                $subscriber_info->village = GeoVillage::where('id', $request->get('village'))->value('en_name') ?? 'not listed';
                $subscriber_info->police_station = GeoUpazila::where('id', $request->get('upazila'))->value('en_name');
                $subscriber_info->district = GeoDistrict::where('id', $request->get('district'))->value('en_name');
                $subscriber_info->division = GeoDivision::where('id', $request->get('division'))->value('en_name');
                $subscriber_info->billingAddress = $request->get('address');
                $subscriber_info->nid = $request->get('nid') ?? 'not given';
                $subscriber_info->numOne = $request->get('mobile_number');
                $subscriber_info->email = $request->get('email') ?? "shadhin_broadband@gmail.com";
                $subscriber_info->UserType = $request->get('user_type') ?? 'HomeUser';
                $subscriber_info->connectionMedia = $request->get('connection_media') ?? 'Fiber';
                $subscriber_info->acativation_date = Carbon::now();
                $subscriber_info->update();
            }else{
                // data for subscriber info table ----------------
                $subscriber_info = new BroadbandDbSubscriberInfo();
                $subscriber_info->serial = $uid;
                $subscriber_info->popId = GeoUpazila::where('id', $request->get('upazila'))->value('en_name');
                $subscriber_info->date = date('Y-m-d');
                if($client){
                    $subscriber_info->zone_name = $client_id;
                }elseif($agent){
                    $client = CorporateAgent::where('uid',$client_id)->value('client_id');
                    $subscriber_info->zone_name = $client;
                }elseif($sub_agent){
                    $client = CorporateSubAgent::where('uid',$client_id)->value('client_id');
                    $subscriber_info->zone_name = $client;
                }
                $subscriber_info->numAsId = $request->get('mobile_number');

                // package data fetching ----
                $packages = InternetPackageCorporate::where('id',$request->get('package'))->first();
                $subscriber_info->packageId = $packages->package_name;
                $subscriber_info->customerName = $request->get('full_name');
                $subscriber_info->gender = $request->get('gender') ?? 'Male';
                $subscriber_info->instcost = $request->get('ins_cost');
                $subscriber_info->home = $request->get('house_no');
                $subscriber_info->village = GeoVillage::where('id', $request->get('village'))->value('en_name') ?? 'not listed';
                $subscriber_info->police_station = GeoUpazila::where('id', $request->get('upazila'))->value('en_name');
                $subscriber_info->district = GeoDistrict::where('id', $request->get('district'))->value('en_name');
                $subscriber_info->division = GeoDivision::where('id', $request->get('division'))->value('en_name');
                $subscriber_info->billingAddress = $request->get('address');
                $subscriber_info->nid = $request->get('nid') ?? 'not given';
                $subscriber_info->numOne = $request->get('mobile_number');
                $subscriber_info->email = $request->get('email') ?? "shadhin_broadband@gmail.com";
                $subscriber_info->UserType = $request->get('user_type') ?? 'HomeUser';
                $subscriber_info->connectionMedia = $request->get('connection_media') ?? 'Fiber';
                $subscriber_info->acativation_date = Carbon::now();
                $subscriber_info->save();
            }

            // package data fetching ----
            $packages = InternetPackageCorporate::where('id',$request->get('package'))->first();

            // Finding the days left of the current month
            $currentDayOfMonth = date('j');
            $totalDaysInMonth = date('t');
            $daysleft = ($totalDaysInMonth-$currentDayOfMonth)+1;

            // Calculating package price
            $price = $packages->price;
            $packageFinalFloat = $daysleft*($price / $totalDaysInMonth);
            $finalPackagePrice = (int)$packageFinalFloat;

            $returned_data['status'] = 'success';
            $returned_data['results'] = [
                'uid' => $uid,
                'full_name' => $request->get('full_name'),
                'mobile_number' => $request->get('mobile_number'),
                'package' => $packages->package_name,
                'days_left' => $daysleft,
                'final_price' => $finalPackagePrice
            ];
        }
        return ResponseWrapper::End($returned_data);
    }

    // Change package for existing user
    public function changePackageOfExistingUser(Request $request, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $userProfile = InternetUsers::where('uid', $uid)->first();
        $userProfile->update([
            'package_id' => $request->get('package_id'),
            'package_type' => 'broadband'
        ]);
        $returned_data['status'] = 'success';
        $returned_data['results'] = [];
        return ResponseWrapper::End($returned_data);
    }

    // Bulk Upload Hello---
    public function bulkUploadBroadbandUserRegister(Request $request) : JsonResponse
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
            Excel::import(new ISPClientBroadbandUserBulkRegisterImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }

    public function connectionStatusActive($mobile_number) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $user_id = User::where('auth_id', $mobile_number)->value('id');
        if($user_id){
            $current_status = InternetUsers::where('uid', $user_id)->value('connection_status');
            if($current_status == 'active'){
                InternetUsers::where('uid', $user_id)->update(['connection_status' => 'inactive']);
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Connection status inactivated successfully!!';
                return ResponseWrapper::End($returned_data);
            }else{
                InternetUsers::where('uid', $user_id)->update(['connection_status' => 'active']);
                $returned_data['status'] = 'success';
                $returned_data['message'] = 'Connection status activated successfully!';
            }
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found!';
        }
        return ResponseWrapper::End($returned_data);
    }
}
