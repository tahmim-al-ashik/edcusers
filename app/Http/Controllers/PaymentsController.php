<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Models\AffiliateHistory;
use App\Models\AgentCommissionBreakdown;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\BroadbandDbUsers;
use App\Models\GeoDistrict;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\BroadbandDbSecret;
use App\Models\NetworkSupportCenter;
use App\Models\Payment;
use App\Models\PaymentToken;
use App\Models\WifiDbPayment;
use App\Models\WifiDbRadReply;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\Transaction;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use App\Models\User;
use App\Models\UserFirstTimePointReceive;
use App\Models\UserProfile;
use App\Models\WifiDbRadUserGroup;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class PaymentsController extends Controller
{

    public function checkUserZoneId(Request $request, $user_auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($user_auth_id);
        
        $returned_data['results'] = InternetUsers::where('uid', '=', $uid)->whereNotNull('zone_id')->exists();
        return ResponseWrapper::End($returned_data);
    }

    public function paymentSuccess(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $request->validate([
            'vendor_name' => 'required',
            'trx_id' => 'required',
            'trx_id' => 'required',
            'invoice_number' => 'required',
            'amount' => 'required',
            'payment_id' => 'required',
            'transaction_status' => 'required',
        ]);

        $user_auth_id = $request->get('user_auth_id');
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($user_auth_id);
        $sender_auth_id = $request->get('sender_auth_id');
        $senderUid = (new \App\Classes\CustomHelpers)->getUidByAuthId($sender_auth_id);
        $userProfile = UserProfile::where('uid', '=', $uid)->first();
        

        $is_paid = Payment::where('uid', $uid)->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->value('created_at');
        if($is_paid){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Paid for this month!';
            return ResponseWrapper::End($returned_data);
        }

        $extraData = $request->get('extra_data');
        if($extraData['service_type'] === 'internet_service'){
            $internetUser = InternetUsers::where('uid', '=', $uid)->first();
            $package_id = $extraData['package_id'];
            $internetPackage = InternetPackage::where('id', '=', $package_id)->first();
            $packageName = $internetPackage['bn_title'];
            $networkZone = NetworkSupportCenter::where('zone_id', '=', $internetUser['zone_id'])->first();
            $isTestMode = $networkZone['is_test_mode'];
            $userType = User::where('id', $uid)->pluck('base_role');
            $commissionRate = 0;
            if($userType = 'sales_point'){
                $commissionRate = $internetPackage['sales_point_commission'];
            }else if($userType = 'sales_agent'){
                $commissionRate = $internetPackage['sales_agent_commission'];
            }else if($userType = 'user'){
                $commissionRate = $internetPackage['user_points'];
            }else if($userType = 'agent'){
                $agentRate = CorporateAgent::where('uid', $uid)->first();
                $commissionRate = $agentRate['commission'];
            }else if($userType = 'sub_agent'){
                $subAgentRate = CorporateSubAgent::where('uid', $uid)->first();
                $commissionRate = $subAgentRate['commission'];
            }

            $query = new Payment();
            $query->uid = $uid;
            $query->zone_id = $networkZone['zone_id'];
            $query->is_test_mode = $isTestMode;
            $query->vendor_name = $request->get('vendor_name');
            $query->trx_id = $request->get('trx_id');
            $query->invoice_number = $request->get('invoice_number');
            $query->amount = !$isTestMode ? $request->get('amount') : 0;
            $query->payment_id = $request->get('payment_id');
            // $query->transaction_status = $request->get('transaction_status');
            $query->transaction_status = 'Completed';
            $query->process_status = 0; // is query process done
            $query->purpose = 'internet_bill_payment';
            $query->package = $internetPackage->mikrotik_radius_group_name;
            if($query->save()){

                // create transaction entry
                if(!$isTestMode){ //$request->get('vendor_name') !== 'wallet_point'
                    $newTransaction = new Transaction();
                    $newTransaction->trx_id = $request->get('trx_id');
                    $newTransaction->trx_type = 'payment';
                    $newTransaction->plus_minus = 'minus';
                    $newTransaction->sender_uid = $senderUid;
                    $newTransaction->receiver_uid = $uid;
                    $newTransaction->method = $request->get('vendor_name');
                    $newTransaction->amount = $request->get('amount');
                    $newTransaction->purpose = 'internet_bill_payment';
                    $newTransaction->save();
                    if($userType = 'agent'){
                        Log::info($newTransaction);
                    }
                }

                if($internetUser['package_type'] === 'wifi'){
                    WifiDbRadUserGroup::where('username', '=', $user_auth_id)->update(['groupname'=>$internetPackage['mikrotik_radius_group_name']]);
                    if(!$isTestMode){
                        $rdbp_query = new WifiDbPayment();
                        $rdbp_query->username = $user_auth_id;
                        $rdbp_query->amount = (int) $request->get('amount');
                        $rdbp_query->created_at = Carbon::now();
                        $rdbp_query->save();
                    }
                    if($internetUser['package_id'] !== null && $package_id == $internetUser['package_id']){
                        $currentExpireDate = Carbon::parse($internetUser['package_expire_date']);
                        if($currentExpireDate > Carbon::now()){
                            $package_expire_date = $currentExpireDate->addMinutes((int) $internetPackage['expiration']);
                        } else {
                            $package_expire_date = (new \App\Classes\CustomHelpers)->add_minutes_with_datetime($internetPackage['expiration']);
                        }
                    }
                    else {
                        $package_expire_date = (new \App\Classes\CustomHelpers)->add_minutes_with_datetime($internetPackage['expiration']);
                    }
                    $internetUser['package_expire_date'] = $package_expire_date;

                    // update to radius db
                    $radreplySessionTime = WifiDbRadReply::where('username', '=', $user_auth_id)->where('attribute', '=', 'WISPr-Session-Terminate-Time')->first();
                    if($radreplySessionTime === null){
                        $rdrcExpirationQuery = new WifiDbRadReply();
                        $rdrcExpirationQuery['username'] = $user_auth_id;
                        $rdrcExpirationQuery['attribute'] = 'WISPr-Session-Terminate-Time';
                        $rdrcExpirationQuery['op'] = '=';
                        $rdrcExpirationQuery['value'] = $package_expire_date->format('Y-m-d')."T".$package_expire_date->format('H:i:s');
                        $rdrcExpirationQuery->save();
                    }
                    else {
                        $radreplySessionTime['value'] = $package_expire_date->format('Y-m-d')."T".$package_expire_date->format('H:i:s');
                        $radreplySessionTime->save();
                    }

                    // activate user
                    $internetUser['connection_status'] = 'active';
                }
                else if($internetUser['package_type'] === 'broadband' && $uid ==! null) {
                    $currentTime = Carbon::now();
                    $broadbandPayment = new BroadbandDbPayment();
                    $broadbandPayment->username = $user_auth_id;
                    $broadbandPayment->amount = !$isTestMode ? (float) $request->get('amount') : 0;
                    $broadbandPayment->type = 'appPayment';
                    $broadbandPayment->month = $currentTime->format('n');
                    $broadbandPayment->payment_date = $currentTime->format('Y-m-d H:i:s');
                    $broadbandPayment->save();

                    $internetUser['package_expire_date'] = date('Y-m-t H:s:i');

                    // activate to secret table on mikrotik
                    BroadbandDbSecret::where('username', '=', $user_auth_id)->update(['status'=>0, 'profile'=>$internetPackage['mikrotik_radius_group_name']]);
                }

                $title = 'বিল প্রদান সম্পন্ন হয়েছে';
                $description = "আপনার স্বাধীন ওয়াই-ফাই ".$packageName." প্যাকেজ এর ". (new \App\Classes\CustomHelpers)->english_to_bangla_numeric($request->get('amount'))." টাকা বিল প্রদান সম্পন্ন হয়েছে। \n\nআমাদের সাথে থাকার জন্যে আপনাকে ধন্যবাদ।";
                (new MessageAndNotificationController)->createNewMessage($uid, 1, $title, $description);
                //(new MessageAndNotificationController)->OneSignalSendExternalId($user_auth_id, $title, $description);
            }
            else {
                return ResponseWrapper::End($returned_data);
            }



            // check affiliate commission distribution
            $affiliateData = AffiliateHistory::where('product_type','=', 'internet_package')->where('product_id', '=', $uid)->first();
            $commissionType = $internetPackage['commission_type'];
            if($affiliateData !== null && $commissionType === 'percentage'){
                //$affiliateData = AffiliateHistory::where('product_type','=', 'internet_package')->where('product_id', '=', $uid)->first();
                $pntAmount = ($request->get('amount') * $commissionRate) / 100;
                AffiliateHistory::where('product_id', $uid)->update(array('commission_amount' => $pntAmount, 'status' => 'approved'));
           

                // sent commission
                // $affiliatorData = UserProfile::where('uid', '=', $affiliateData['affiliator_uid'])->first();
                
                // $previousAmount = $affiliatorData['wallet_amount'];
                // $affiliatorData->wallet_amount = ($previousAmount + $affiliateData['commission_amount']);                
                // if($affiliatorData->update()){
                //     // track breakdown
                //     AgentCommissionBreakdown::create(['agent_uid'=>$affiliateData['affiliator_uid'], 'user_id'=>$uid, 'previous_wallet_amount'=>$previousAmount, 'new_commission_amount'=>$affiliateData['commission_amount']]);

                //     // update status 
                //     MessageAndNotificationController::createNewMessage($affiliateData['affiliator_uid'], 1, CustomHelpers::english_to_bangla_numeric($affiliateData['commission_amount']) .' পয়েন্ট অর্জন করেছেন।', 'আপনি ইন্টারনেট প্যাকেজ বিক্রয়ের জন্য  '.CustomHelpers::english_to_bangla_numeric($affiliateData['commission_amount']).' পয়েন্ট অর্জন করেছেন।');
                //     CustomHelpers::create_new_transaction($affiliateData['affiliator_uid'], 1, 'wallet_point', 'wallet_point', $affiliateData['commission_amount'], 'plus', 'internet_package_sale');
                    
                // }


                // package purchase gift point for referral id 
                //Log::info($pntAmount);

                if($pntAmount > 0){
                    $affiliatorData = UserProfile::where('uid', '=', $affiliateData['affiliator_uid'])->first();

                    $affiliatorData->wallet_amount = $affiliatorData['wallet_amount'] + (float) $pntAmount;
                    $affiliatorData->update();

                    $userComission = UserProfile::where('uid', '=', $uid)->first();
                    $userComission->wallet_amount = $userComission->wallet_amount + (float) $pntAmount;
                    if($userComission->update()){
                        $previousAmount = $affiliatorData['wallet_amount'];
                        (new MessageAndNotificationController)->createNewMessage($uid, 1, (new \App\Classes\CustomHelpers)->english_to_bangla_numeric($pntAmount) .' পয়েন্ট অর্জন করেছেন।', 'আপনি রেফারেল ব্যবহারের জন্যে '.CustomHelpers::english_to_bangla_numeric($pntAmount).' পয়েন্ট অর্জন করেছেন।');
                        (new \App\Classes\CustomHelpers)->create_new_transaction($uid, 1, 'wallet_point', 'wallet_point', $pntAmount, 'plus', 'internet_package_recharge_bonus');
                        AgentCommissionBreakdown::create(['agent_uid'=>$affiliateData['affiliator_uid'], 'user_id'=>$uid, 'previous_wallet_amount'=>$previousAmount, 'new_commission_amount'=>$affiliateData['commission_amount']]);

                        $pointsReceived = UserFirstTimePointReceive::where('uid', '=', $uid)->where('service_type', '=', 'internet_service')->exists();
                        if(!$pointsReceived){
                            $uftpr_query = new UserFirstTimePointReceive();
                            $uftpr_query->service_type = 'internet_service';
                            $uftpr_query->uid = $uid;
                            $uftpr_query->save();
                        }
                    }
                }

            } else {
                // first package purchase gift point
                $pointsReceived = UserFirstTimePointReceive::where('uid', '=', $uid)->where('service_type', '=', 'internet_service')->exists();
                $commissionType = $internetPackage['commission_type'];
                if(!$pointsReceived){                    
                    if($commissionType === 'percentage'){
                        $pntAmount = ($request->get('amount') * $commissionRate) / 100;
                        $userProfile = UserProfile::where('uid', '=', $uid)->first();
                        $userProfile->wallet_amount = $userProfile->wallet_amount + (float) $pntAmount;
                        $profileUpdate = $userProfile->update();
                        if($profileUpdate && $pntAmount > 0){
                            MessageAndNotificationController::createNewMessage($uid, 1, CustomHelpers::english_to_bangla_numeric($pntAmount) .' পয়েন্ট অর্জন করেছেন।', 'আপনি প্রথমবার ইন্টারনেট প্যাকেজ রিচার্জ এর জন্যে '.CustomHelpers::english_to_bangla_numeric($pntAmount).' পয়েন্ট অর্জন করেছেন।');
                            CustomHelpers::create_new_transaction($uid, 1, 'wallet_point', 'wallet_point', $pntAmount, 'plus', 'internet_package_recharge_bonus');
                        }
                    }        

                    $uftpr_query = new UserFirstTimePointReceive();
                    $uftpr_query->service_type = 'internet_service';
                    $uftpr_query->uid = $uid;
                    $uftpr_query->save();
                }else{                    
                    if($commissionType === 'percentage'){
                        $pntAmount = ($request->get('amount') * $commissionRate) / 100;
                        $userProfile = UserProfile::where('uid', '=', $uid)->first();
                        $userProfile->wallet_amount = $userProfile->wallet_amount + (float) $pntAmount;
                        $profileUpdate = $userProfile->update();
                        if($profileUpdate && $pntAmount > 0){
                            MessageAndNotificationController::createNewMessage($uid, 1, CustomHelpers::english_to_bangla_numeric($pntAmount) .' পয়েন্ট অর্জন করেছেন।', 'আপনি প্রথমবার ইন্টারনেট প্যাকেজ রিচার্জ এর জন্যে '.CustomHelpers::english_to_bangla_numeric($pntAmount).' পয়েন্ট অর্জন করেছেন।');
                            CustomHelpers::create_new_transaction($uid, 1, 'wallet_point', 'wallet_point', $pntAmount, 'plus', 'internet_package_recharge_bonus');
                        }
                    }                   
                }
            }


            // update user zone id if null
            if($internetUser['zone_id'] == null){
                $senderBaseRole = User::where('id', '=', $senderUid)->value('base_role');
                if($senderBaseRole === 'support_center'){
                    $internetUser['zone_id'] = NetworkSupportCenter::where('uid', '=', $senderUid)->value('zone_id');
                } else if($senderBaseRole === 'sales_point'){
                    $internetUser['zone_id'] = SalesPoint::where('uid', '=', $senderUid)->value('zone_id');
                } else if($senderBaseRole === 'sales_agent'){
                    $internetUser['zone_id'] = SalesAgent::where('uid', '=', $senderUid)->value('zone_id');
                }
            }else {

                // fallback for previous password system
                if(!empty($extraData['action_type']) && $extraData['action_type'] === 'activate_broadband_user'){
                    if(empty($internetUser['password_broadband'])){
                        $internetUser['password_broadband'] = random_int(100000, 999999);
                        $internetUser->save();
                    }
                }

                $RouterOsAPI = new RouterOsApi();
                $existSecret = BroadbandDbSecret::where('username', '=', $user_auth_id)->first();
                if ($RouterOsAPI->connect($networkZone['zone_ip'], $networkZone['zone_username'], $networkZone['zone_password'])) {//
                    if($internetUser['connection_status'] == 'pending'){

                        // create secret on db
                        if(empty($existSecret)){
                            $bbDbSecret = new BroadbandDbSecret();
                            $bbDbSecret->username = $user_auth_id;
                            $bbDbSecret->password = random_int(100000, 999999);
                            $bbDbSecret->profile = $internetPackage['mikrotik_radius_group_name'];
                            $bbDbSecret->service = 'pppoe';
                            $bbDbSecret->zone = $networkZone['zone_name'];
                            $bbDbSecret->status = '0';
                            $bbDbSecret->type = 'paid';
                            $bbDbSecret->created_at = Carbon::now();
                            $bbDbSecret->updated_at = Carbon::now();
                            $bbDbSecret->save();
                        } else {
                            $internetUser['password_broadband'] = $existSecret->password;
                        }

                        // create user
                        if(!BroadbandDbUsers::where('username', '=', $user_auth_id)->exists()){
                            $bbDbUser = new BroadbandDbUsers();
                            $bbDbUser->username = $user_auth_id;
                            $bbDbUser->mobileno = $user_auth_id;
                            $bbDbUser->password = $user_auth_id;
                            $bbDbUser->created_at = Carbon::now();
                            $bbDbUser->save();
                        }


                        // create subscriber
                        $village = GeoVillage::where('id','=', $userProfile['village_id'])->value('en_name');
                        $post_office = null;
                        $police_station = GeoUpazila::where('id','=', $userProfile['upazila_id'])->value('en_name');
                        $district = GeoDistrict::where('id','=', $userProfile['district_id'])->value('en_name');
                        $home = $userProfile['house_no'];
                        $subscriberInfo = BroadbandDbSubscriberInfo::where('numAsId', '=', $user_auth_id)->first();
                        if($subscriberInfo === null){
                            $bbSubscriber = new BroadbandDbSubscriberInfo();
                            $bbSubscriber->serial = $extraData['serial_number'];
                            $bbSubscriber->popId = $extraData['pop_name'];
                            $bbSubscriber->date = Carbon::now();
                            $bbSubscriber->zone_name = $networkZone['zone_name'];
                            $bbSubscriber->m_name = !empty($affiliatorData) ? $affiliatorData['full_name'] : '';
                            $bbSubscriber->m_mobile = !empty($affiliatorData) ? $affiliatorData['mobile_number'] : '';
                            $bbSubscriber->numAsId = $user_auth_id;
                            $bbSubscriber->packageId = $internetPackage['mikrotik_radius_group_name'];
                            $bbSubscriber->customerName = $userProfile['full_name'];
                            $bbSubscriber->gender = $extraData['gender'];
                            $bbSubscriber->instcost = $extraData['installation_charge'];
                            $bbSubscriber->home = !empty($home) ? $home : 'unknown';
                            $bbSubscriber->village = !empty($village) ? $village : $userProfile['village_id'];
                            $bbSubscriber->post_office = !empty($post_office) ? $post_office : 'unknown';
                            $bbSubscriber->police_station = !empty($police_station) ? $police_station : $userProfile['upazila_id'];
                            $bbSubscriber->district = !empty($district) ? $district : $userProfile['district_id'];
                            $bbSubscriber->billingAddress = $userProfile['address'];
                            $bbSubscriber->nid = $extraData['nid'];
                            $bbSubscriber->numOne = $user_auth_id;
                            $bbSubscriber->email = !empty($userProfile['email']) ? $userProfile['email'] : 'noreply@shadhinwifi.com';
                            $bbSubscriber->UserType = $extraData['user_type'];
                            $bbSubscriber->connectionMedia = $extraData['connection_type'];
                            $bbSubscriber->save();
                        }

                        $userProfile['gender'] = !empty($extraData['gender']) ? $extraData['gender'] : 'not_to_say';
                        $userProfile['nid'] = !empty($extraData['nid']) ? $extraData['nid'] : null;
                        $userProfile->save();


                        if($existSecret !== null){
                            $internetUser['serial_number'] =  $subscriberInfo['serial'];
                            $internetUser['broadband_pop_id'] = $subscriberInfo['popId'];
                            $internetUser['installation_charge'] = $subscriberInfo['instcost'];
                            $internetUser['connection_media'] = $subscriberInfo['connectionMedia'];
                            $internetUser['connection_status'] = 'active';
                            $internetUser['package_expire_date'] = date('Y-m-t H:s:i');



                            $arrID = $RouterOsAPI->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $user_auth_id));
                            $RouterOsAPI->comm('/ppp/secret/set', [
                                    ".id" => $arrID[0][".id"],
                                    'name' => $user_auth_id,
                                    'password' => $internetUser['password_broadband'],
                                    'service' => 'pppoe',
                                    'profile' => $internetPackage['mikrotik_radius_group_name']
                                ]
                            );
                            Log::debug("broadband profile/package id updated", ['yes']);
                        }
                        else {
                            $internetUser['serial_number'] = $extraData['serial_number'];
                            $internetUser['user_type'] = $extraData['user_type'];
                            $internetUser['broadband_pop_id'] = $extraData['pop_name'];
                            $internetUser['installation_charge'] = $extraData['installation_charge'];
                            $internetUser['connection_media'] = $extraData['connection_type'];
                            $internetUser['connection_status'] = 'active';
                            $internetUser['package_expire_date'] = date('Y-m-t H:s:i');


                            // create mikrotik secret
                            $RouterOsAPI->comm('/ppp/secret/add', [
                                    'name' => $user_auth_id,
                                    'password' => $internetUser['password_broadband'],
                                    'service' => 'pppoe',
                                    'profile' => $internetPackage['mikrotik_radius_group_name']
                                ]
                            );
                        }

                    }

                    else if($internetUser['package_type'] == 'broadband'){
                        if($internetUser['connection_status'] == 'active' || $internetUser['connection_status'] == 'inactive'){
                            $arrID = $RouterOsAPI->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $user_auth_id));
                            $RouterOsAPI->comm('/ppp/secret/set', [".id" => $arrID[0][".id"], 'profile' => $internetPackage['mikrotik_radius_group_name']]);
                            $RouterOsAPI->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                        }
                    }
                    $RouterOsAPI->disconnect();
                }
                else {
                    (new \App\Classes\CustomHelpers)->insertErrorLogPayment($user_auth_id, $uid, $internetUser['zone_id'], $request->get('trx_id'), 'can not connect to router os');
                }
            }

            $internetUser['package_id'] = $package_id;
            $internetUser->save();

            // update payment status
            $returned_data['results'] = Payment::where('trx_id', '=', $request->get('trx_id'))->where('uid', '=', $uid)->update([
                'process_status'=> 1
            ]);
        }

        return ResponseWrapper::End($returned_data);
    }

    public function getPanelPaymentList(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $keywords = strtolower(trim($request->get('keyword')));
        $vendor = $request->get('vendor_name');
        $package_type = $request->get('package');

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        $query = Payment::query();
        $query->select('payments.*', 'users.auth_id'); // Include 'users.auth_id' in the select statement
        $query->join('users', 'users.id', '=', 'payments.uid');

        if($keywords) {
            $query->where(function($qr) use ($keywords){
                $qr->where('users.auth_id', '=', $keywords)->orWhere('payments.trx_id', '=', $keywords);
            });
        }

        if ($vendor !== 'all' && $vendor !== null) {
            $query->where('payments.vendor_name', '=', $vendor);
        }

        if ($package_type !== 'all' && $package_type !== null) {
            $query->where('payments.package', '=', $package_type);
        }

        $query->orderBy('payments.created_at', $sortBy);
        $query->skip($totalSkip)->take(50);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(['payments.*', 'users.auth_id']);

        return ResponseWrapper::End($returned_data);
    }

    public function getOldUsersPaidByApps(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = Payment::query();
        $query->leftJoin('users', 'users.id', '=', 'payments.uid');
        $query->groupBy('payments.uid');
        $payments = $query->get(['users.auth_id','payments.amount']);

        $notActivateUsers = [];
        $activePaidUsers = [];
        $activePaidGroupLessUsers = [];
        $activeFreePackageUsers = [];
        foreach ($payments as $payment){
            if(WifiDbPayment::where('username', $payment['auth_id'])->exists()){
                $groupname = WifiDbRadUserGroup::where('username', $payment['auth_id'])->groupBy('username')->value('groupname');
                $payment->groupname = $groupname;
                if(str_contains(strtolower($groupname), 'free')){
                    $activeFreePackageUsers[] = $payment;
                } else {
                    if(!empty($groupname)){
                        $activePaidUsers[] = $payment;
                    } else {
                        $activePaidGroupLessUsers[] = $payment;
                    }
                }
            } else {
                $notActivateUsers[] = $payment;
            }
        }

        $returned_data['results']['inactive'] = $notActivateUsers;
        $returned_data['results']['active_paid'] = $activePaidUsers;
        $returned_data['results']['active_group_less_free'] = $activePaidGroupLessUsers;
        $returned_data['results']['active_free'] = $activeFreePackageUsers;

        return ResponseWrapper::End($returned_data);
    }

}
