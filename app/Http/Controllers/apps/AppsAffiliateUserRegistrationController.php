<?php

namespace App\Http\Controllers\apps;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MessageAndNotificationController;
use App\Http\Controllers\radius\RadiusServerController;
use App\Models\AffiliateHistory;
use App\Models\AgentSettings;
use App\Models\InternetPackage;
use App\Models\MonthlyCommission;
use App\Models\NetworkSupportCenter;
use App\Models\OtpReference;
use App\Models\Product;
use App\Models\Service;
use App\Models\WifiDbRadCheck;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use function Psy\debug;

class AppsAffiliateUserRegistrationController extends Controller
{
    public function getInternetCommissionList(Request $request, $agent_type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = InternetPackage::query();
        $query->where('is_active', '=', 1);
        $query->where('price', '>=', 1);

        if($agent_type === 'sales_agent'){
            $query->where('sales_agent_commission', '>=', 1);
        } else if($agent_type === 'sales_point'){
            $query->where('sales_point_commission', '>=', 1);
        }
        $query->orderBy('weight', 'asc');
        $allPackages = $query->get(['en_title','bn_title','type','price','sales_point_commission','sales_agent_commission','commission_type','expiration']);

        foreach ($allPackages as $package){
            $expiration_days = ($package->expiration / 1440);
            $package->expiration_days = $expiration_days;
            $returned_data['results'][] = $package;
        }

        return ResponseWrapper::End($returned_data);
    }


    public function getProductCommissionList(Request $request, $agent_type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = Product::query();
        $query->where('is_active', '=', 1);
        $query->where('price', '>=', 1);

        if($agent_type === 'sales_agent'){
            $query->where('sales_agent_commission', '>=', 1);
        } else if($agent_type === 'sales_point'){
            $query->where('sales_point_commission', '>=', 1);
        }
        $returned_data['results'] = $query->get();

        return ResponseWrapper::End($returned_data);
    }


    public function getServiceCommissionList(Request $request, $agent_type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = Service::query();
        $query->where('is_active', '=', 1);
        $query->where('price', '>=', 1);

        if($agent_type === 'sales_agent'){
            $query->where('sales_agent_commission', '>=', 1);
        } else if($agent_type === 'sales_point'){
            $query->where('sales_point_commission', '>=', 1);
        }
        $returned_data['results'] = $query->get();

        return ResponseWrapper::End($returned_data);
    }


    public function affiliateCheckAccountAndSendOtp(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        if(!User::where('auth_id', '=', $request->get('mobile_number'))->exists()){
            $otp = (new \App\Classes\CustomHelpers)->generate_new_password();
//            $smsText = "স্বাধীন ওয়াইফাই ওটিপি হলো- " . $otp;
//            $smsText = "স্বাধীন ওয়াইফাই ওটিপি হলো- " . $otp;
//            $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($request->get('mobile_number'), $smsText);

            $query = OtpReference::where('mobile_number', '=', $request->get('mobile_number'))->first();
            if(empty($query)){
                $query = new OtpReference();
            }
            $query->mobile_number = $request->get('mobile_number');
            $query->otp = 2558;
            $query->save();

            $returned_data['results'] = "sent";
        } else {
            $returned_data['error_type'] = "account_exist";
        }
        return ResponseWrapper::End($returned_data);
    }


    public function affiliateRegisterInternetUser(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $newPassword = (new \App\Classes\CustomHelpers)->generate_new_password();
        $agentUid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('agent_auth_id'));

        //Log::info($agentUid);
        if(OtpReference::where('mobile_number', '=', $request->get('mobile_number'))->where('otp', '=', $request->get('otp_number'))->exists()){
            OtpReference::where('mobile_number', '=', $request->get('mobile_number'))->delete();
        } else {
            $returned_data['error_type'] = "otp_not_match";
            return ResponseWrapper::End($returned_data);
        }

        $auth_id = $request->get('mobile_number');
        if(User::where('auth_id', '=', $auth_id)->exists()){
            $returned_data['error_type']= 'account_exist';
            return ResponseWrapper::End($returned_data);
        }



        $userQuery = new User();
        $userQuery->auth_id = $auth_id;
        $userQuery->base_role = 'user';
        $userQuery->status = 'active';
        $userQuery->password = Hash::make($newPassword);
        $userQuery->text_password = $newPassword;
        $userQuery->save();
        if($userQuery->id){

            $uid = $userQuery->id;
            if($request->get('package_type') === 'wifi'){
                $newInternetPassword = (new \App\Classes\CustomHelpers)->generate_new_password();
                $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$request->get('mobile_number')." এবং পাসওয়ার্ড: " . $newPassword;
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($request->get('mobile_number'), $smsText);
            } else {
                $newInternetPassword = (new \App\Classes\CustomHelpers)->generate_new_password(6);
                MessageAndNotificationController::createNewMessage($uid, 1, 'নতুন প্যাকেজ নিবন্ধন সম্পন্ন হয়েছে', "আপনার স্বাধীন ওয়াই-ফাই ব্রডব্যান্ড প্যাকেজ নিবন্ধন সম্পন্ন হয়েছে। তবে আপনার ইন্টারনেট সংযোগটি বর্তমানে পেন্ডিং অবস্থায় আছে। আপনার নিকটস্থ সাপোর্ট সেন্টার শিগ্রই আপনার সাথে যোগাযোগ করবেন।");
            }


            // set user profile
            $profileQuery = new UserProfile();
            $profileQuery->uid = $uid;
            $profileQuery->full_name = $request->get('full_name');
            $profileQuery->mobile_number = $request->get('mobile_number');
            $profileQuery->whatsapp_number = $request->get('whatsapp_number');
            $profileQuery->email = $request->get('email');
            $profileQuery->division_id = $request->get('division_id');
            $profileQuery->district_id = $request->get('district_id');
            $profileQuery->upazila_id = $request->get('upazila_id');
            $profileQuery->union_id = $request->get('union_id');
            $profileQuery->village_id = $request->get('village_id');
            $profileQuery->house_no = $request->get('house_no');
            $profileQuery->address = $request->get('address');
            $profileQuery->address_direction = $request->get('address_direction');
            $profileQuery->latitude = $request->get('latitude');
            $profileQuery->longitude = $request->get('longitude');
            $profileQuery->device_info = json_encode($request->get('device_info'));
            $profileQuery->save();

            if($profileQuery->id){

                // create internet user
                $agentType = $request->get('agent_type');
                
                $zone_id = null;
                if($agentType === 'support_center'){
                    $zone_id = NetworkSupportCenter::where('uid','=',$agentUid)->value('zone_id');
                }

                $newInternetUser = (new AppsInternetUsersController)->createInternetUser(
                    $uid,
                    $newInternetPassword,
                    $request->get('package_type'),
                    $request->get('package_id'),
                    $zone_id,
                    $request->get('current_conn_type'),
                    null,
                    $request->get('latitude'),
                    $request->get('longitude'),
                );

                if($request->get('package_type') === 'wifi' && $zone_id !== null){
                    $user_radius_insert_status = (new \App\Http\Controllers\radius\RadiusServerController)->create_radius_db_information($auth_id, $request->get('package_id'));
                    if(!$user_radius_insert_status){
                        Log::debug('user_radius_insert_error3', [$auth_id]);
                    }
                } else if($zone_id !== null) {
                    (new \App\Classes\CustomHelpers)->syncOldBroadbandUserWithNewErp($auth_id, $uid, $zone_id);
                }

                $returned_data['results'] = $auth_id;

            }
        }

        return ResponseWrapper::End($returned_data);
    }


    public function affiliateAmarAffiliateProducts(Request $request, $affiliator_auth_id, $affiliate_type) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $agentUid = (new \App\Classes\CustomHelpers)->getUidByAuthId($affiliator_auth_id);
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

        $query = AffiliateHistory::query();
//        $query->leftJoin('internet_users', 'internet_users.uid', '=', 'affiliate_histories.product_id');
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'affiliate_histories.product_id');
        $query->where('affiliate_histories.affiliator_uid', '=', $agentUid);
        if($affiliate_type !== 'all'){
            $query->where('product_type', '=', $affiliate_type);
        }
        $query->orderBy('affiliate_histories.created_at', 'DESC');
        $returned_data['results']['total'] = $query->count();
        $query->skip($totalSkip)->take(20);
        $returned_data['results']['list'] = $query->get(['user_profiles.full_name','affiliate_histories.id','affiliate_histories.created_at','affiliate_histories.product_type','affiliate_histories.commission_amount','affiliate_histories.status']);

        return ResponseWrapper::End($returned_data);
    }


    public function affiliateAmarMonthlyCommission(Request $request, $agent_type, $affiliator_auth_id, $month_year) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $agentUid = (new \App\Classes\CustomHelpers)->getUidByAuthId($affiliator_auth_id);
        $filteredMonth = Carbon::parse($month_year);
        $executionDate = Carbon::parse('31-07-2023 00:00:00');

        $query = AffiliateHistory::query();
        $query->distinct();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'affiliate_histories.product_id');
        $query->where('affiliate_histories.affiliator_uid', '=', $agentUid);
        $query->whereMonth('affiliate_histories.created_at', '=', $filteredMonth->month);
        $query->orderBy('affiliate_histories.created_at', 'DESC');
        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(['user_profiles.full_name','affiliate_histories.id','affiliate_histories.created_at','affiliate_histories.product_type','affiliate_histories.commission_amount','affiliate_histories.status']);


        $total_commission = 0;
        $monthly_commission_rate = 0;
        $monthly_commission_amount = 0;
        $not_applicable = false;

        $existCalculateHistory = MonthlyCommission::where('uid', '=', $agentUid)->whereMonth('date_month', '=', $filteredMonth->month)->first();
        if($existCalculateHistory === null && $filteredMonth > $executionDate){
            $total_commission =  AffiliateHistory::where('affiliator_uid', '=', $agentUid)->whereMonth('created_at', '=', $filteredMonth->month)->sum('commission_amount');
            $monthlyCommissionRate = (new \App\Classes\CustomHelpers)->getAgentsMonthlyCommissionRate($agent_type, $agentUid);
            $monthly_commission_rate = number_format((float)$monthlyCommissionRate, 2, '.', '');
            $monthly_commission_amount = (($total_commission * $monthly_commission_rate) / 100);

            if($filteredMonth < Carbon::now()){
                MonthlyCommission::create([
                    "uid" => $agentUid,
                    "user_type" => $agent_type,
                    "date_month" => $filteredMonth->format('Y-m-d'),
                    "total_commission" => $total_commission,
                    "commission_rate" => $monthly_commission_rate,
                    "commission_amount" => $monthly_commission_amount
                ]);

                $affiliatorData = UserProfile::where('uid', '=', $agentUid)->first();
                $affiliatorData->wallet_amount = ($affiliatorData['wallet_amount'] + $monthly_commission_amount);
                if($affiliatorData->save()){
                    (new \App\Http\Controllers\MessageAndNotificationController)->createNewMessage($agentUid, 1, (new \App\Classes\CustomHelpers)->english_to_bangla_numeric($monthly_commission_amount) .' মাসিক পয়েন্ট অর্জন করেছেন।', 'রেফার করার প্রেক্ষিতে ইন্টারনেট প্যাকেজ রিনিউ পেমেন্ট সম্পন্ন করায় আপনি মাসিক '.CustomHelpers::english_to_bangla_numeric($monthly_commission_amount).' পয়েন্ট অর্জন করেছেন।');
                    (new \App\Classes\CustomHelpers)->create_new_transaction($agentUid, 1, 'wallet_point', 'wallet_point', $monthly_commission_amount, 'plus', 'internet_package_renew');
                }
            }

        } else if($filteredMonth > $executionDate) {
            $total_commission = $existCalculateHistory['total_commission'];
            $monthly_commission_rate = $existCalculateHistory['commission_rate'];
            $monthly_commission_amount = $existCalculateHistory['commission_amount'];
        } else {
            $not_applicable = true;
        }

        $returned_data['results']['not_applicable'] = $not_applicable;
        $returned_data['results']['total_commission'] = $total_commission;
        $returned_data['results']['commission_rate'] = $monthly_commission_rate;
        $returned_data['results']['commission_amount'] = number_format($monthly_commission_amount, 2, '.', '');

        return ResponseWrapper::End($returned_data);
    }


    public function affiliateAmarAffiliateSearch(Request $request, $affiliator_auth_id, $affiliate_type, $keywords) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $agentUid = (new \App\Classes\CustomHelpers)->getUidByAuthId($affiliator_auth_id);
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

        $query = AffiliateHistory::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'affiliate_histories.product_id');
        $query->where('affiliate_histories.affiliator_uid', '=', $agentUid);
        if($affiliate_type !== 'all'){
            $query->where('product_type', '=', $affiliate_type);
        }
        $query->where(function ($query) use($keywords) {
            $query->where('user_profiles.full_name', 'like', '%' . $keywords . '%')->orWhere('user_profiles.mobile_number', 'like', '%' . $keywords . '%');
        });
        $query->orderBy('affiliate_histories.created_at', 'DESC');
        $returned_data['results']['total'] = $query->count();
        $query->skip($totalSkip)->take(20);
        $returned_data['results']['list'] = $query->get(['user_profiles.full_name','affiliate_histories.id','affiliate_histories.created_at','affiliate_histories.product_type','affiliate_histories.commission_amount','affiliate_histories.status']);

        return ResponseWrapper::End($returned_data);
    }
}
