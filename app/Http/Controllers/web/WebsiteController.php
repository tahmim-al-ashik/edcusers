<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\apps\AppsInternetUsersController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MessageAndNotificationController;
use App\Http\Controllers\radius\RadiusServerController;
use App\Models\BroadbandDbZone;
use App\Models\CareerResume;
use App\Models\CorporateClient;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\User;
use App\Models\TestTable;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebsiteController extends Controller {

    public function webNetworkSupportCenterZoneNameUpdate(Request $request) {
        $allActiveZone = NetworkSupportCenter::where('status', '=', 'active')->get(['zone_name','zone_ip','id']);
        $updatedZones = [];
        foreach ($allActiveZone as $zone){
            $oldZoneName = BroadbandDbZone::where('mikrotik_ip', '=', $zone->zone_ip)->value('zone_name');
            if($oldZoneName !== null){
                $updatedZones[$zone->zone_ip]['erp_name'] = $zone->zone_name;
                $updatedZones[$zone->zone_ip]['old_name'] = $oldZoneName;
                $zone->zone_name = $oldZoneName;
                $zone->save();
            }
        }
        print_r($updatedZones);
    }

    public function webRegisterInternetUser(Request $request) {
        $profileData = $request->get('profile');
        $internetData = $request->get('data');
        //Log::info($internetData);

        if(!empty($profileData['mobile_number'])){
            $mobileNumber = $profileData['mobile_number'];

            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $auth_id)->exists()){
                $userData = User::where('auth_id', '=', $auth_id)->first();
                $uid = $userData['id'];
            }
            else {
                //create new user
                if(empty($internetData['wifi_package_id'])){
                    $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user','broadband');
                }else{
                    $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
                }
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                // create new profile
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $profileData['full_name'];
                $userProfile->mobile_number = $profileData['mobile_number'];
                $userProfile->whatsapp_number = $profileData['whatsapp_number'];
                $userProfile->email = $profileData['email'];
                $userProfile->profession = $profileData['profession'];
                $userProfile->nid = $profileData['nid'];
                $userProfile->gender = $profileData['gender'];
                $userProfile->division_id = $profileData['division_id'];
                $userProfile->district_id = $profileData['district_id'];
                $userProfile->upazila_id = $profileData['upazila_id'];
                $userProfile->union_id = $profileData['union_id'];
                $userProfile->village_id = $profileData['village_id'];
                $userProfile->address = $profileData['address'];
                $userProfile->address_direction = $profileData['address_direction'];
                $userProfile->latitude = $profileData['latitude'];
                $userProfile->longitude = $profileData['longitude'];
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                if( $user_type = $profileData['user_type'] !== 'corporate'){
                    MessageAndNotificationController::createNewMessage($uid, 1, 'স্বাগতম!', 'স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।');
                    $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$auth_id." এবং পাসওয়ার্ড: " . $password;
                    $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($auth_id, $smsText);
                }

                // else{
                //     MessageAndNotificationController::createNewMessage($uid, 1, 'স্বাগতম!', 'স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।');
                //     $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$auth_id." এবং পাসওয়ার্ড: " . $password;
                //     $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($auth_id, $smsText);
                // }
            }

            if(!empty($uid)){
                if(!InternetUsers::where('uid', '=', $uid)->exists()){

                    $zone_id = null;
                    $latitude = $profileData['latitude'];
                    $longitude = $profileData['longitude'];
                    $user_type = $profileData['user_type'] ?? 'Home User';

                    $package_id = "";
                    $packageType = '';
                    if(!empty($internetData['wifi_package_id'])){
                        $package_id = $internetData['wifi_package_id'];
                        $packageType = 'wifi';
                    } else if(!empty($internetData['broadband_package_id'])){
                        $package_id = $internetData['broadband_package_id'];
                        $packageType = 'broadband';
                    } else {
                        $package_id = $internetData['corporate_package_id'];
                        $packageType = 'corporate';
                    }

                    if($packageType === 'broadband'){
                        $internetPassword = (new \App\Classes\CustomHelpers)->generate_new_password(6);
                    } else if($packageType === 'corporate'){
                        $internetPassword = (new \App\Classes\CustomHelpers)->generate_new_password(6);
                    } else {
                        $internetPassword = (new \App\Classes\CustomHelpers)->generate_new_password();
                    }

                    // get zone_id from user latitude,longitude
                    if(!empty($latitude) && !empty($longitude)){
                        $zoneInfo = (new \App\Http\Controllers\panel\location\GeoController)->getZoneInfo($latitude, $longitude);
                        if($zoneInfo !== null && $zoneInfo['zone_id'] !== null){
                            $zone_id = $zoneInfo['zone_id'];
                        }
                    }

                    $newUser = new InternetUsers();
                    $newUser->uid = $uid;
                    $newUser->zone_id = $zone_id;
                    $newUser->added_by = null;
                    $newUser->package_id = $package_id;
                    if(!empty($internetData['wifi_package_id'])){
                        $newUser->package_type = 'wifi';
                    } else if(!empty($internetData['broadband_package_id'])){
                        $newUser->package_type = 'broadband';
                    } else {
                        $newUser->package_type = 'corporate';
                    }
                    $newUser->package_expire_date = null;
                    $newUser->latitude = $latitude;
                    $newUser->longitude = $longitude;
                    $newUser->password = $internetPassword;
                    $newUser->password_broadband = $internetPassword;
                    $newUser->user_type = $user_type;
                    $newUser->billing_address = null;
                    $newUser->serial_number = null;
                    $newUser->broadband_pop_id = null;
                    $newUser->connection_media = null;
                    $newUser->installation_charge = 0;
                    $newUser->connection_status = 'pending';
                    $newUser->save();

                    if($request->get('package_type') === 'wifi'){
                        $radiusDbStatus = RadiusServerController::create_radius_db_information($auth_id, $package_id);
                        if(!$radiusDbStatus){
                            Log::debug('user_radius_insert_error1', [$auth_id]);
                        }
                    }

                    if($newUser->id){
                        return redirect($request->get('redirect') . '?status=success&message_key=sms_for_app_credentials');
                    } else {
                        return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
                    }
                } else {
                    return redirect($request->get('redirect') . '?status=error&error_type=already_applied');
                }
            }

            else {
                return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
            }
        }
        return redirect($request->get('redirect') . '?status=error&error_type=mobile_number_required');
    }
    
    // public function webRegisterInternetUser(Request $request) {
    //     $profileData = $request->get('profile');
    //     $internetData = $request->get('data');
    //     if($profileData['mobile_number'] !== null){
    //         $mobileNumber = $profileData['mobile_number'];
    //         $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
    //         if(User::where('auth_id', '=', $auth_id)->exists()){
    //             $userData = User::where('auth_id', '=', $auth_id)->first();
    //             $uid = $userData['id'];
    //         }
    //         else {
    //             //create new user
    //             if(empty($internetData['wifi_package_id'])){
    //                 $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user','broadband');
    //             }else{
    //                 $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
    //             }
    //             $uid = $userData['user']['id'];
    //             $password = $userData['password'];

    //             // create new profile
    //             $userProfile = new UserProfile();
    //             $userProfile->uid = $uid;
    //             $userProfile->full_name = $profileData['full_name'];
    //             $userProfile->mobile_number = $profileData['mobile_number'];
    //             $userProfile->whatsapp_number = $profileData['whatsapp_number'];
    //             $userProfile->email = $profileData['email'];
    //             $userProfile->profession = $profileData['profession'];
    //             $userProfile->nid = $profileData['nid'];
    //             $userProfile->gender = $profileData['gender'];
    //             $userProfile->division_id = $profileData['division_id'];
    //             $userProfile->district_id = $profileData['district_id'];
    //             $userProfile->upazila_id = $profileData['upazila_id'];
    //             $userProfile->union_id = $profileData['union_id'];
    //             $userProfile->village_id = $profileData['village_id'];
    //             $userProfile->address = $profileData['address'];
    //             $userProfile->address_direction = $profileData['address_direction'];
    //             $userProfile->latitude = $profileData['latitude'];
    //             $userProfile->longitude = $profileData['longitude'];
    //             $userProfile->device_info = json_encode(["brand"=>"website"]);
    //             $userProfile->save();

    //             MessageAndNotificationController::createNewMessage($uid, 1, 'স্বাগতম!', 'স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।');
    //             $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$auth_id." এবং পাসওয়ার্ড: " . $password;
    //             $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($auth_id, $smsText);
    //         }

    //         if(!empty($uid)){
    //             if(!InternetUsers::where('uid', '=', $uid)->exists()){

    //                 $zone_id = null;
    //                 $latitude = $profileData['latitude'];
    //                 $longitude = $profileData['longitude'];

    //                 $package_id = $internetData['wifi_package_id'];
    //                 $packageType = 'wifi';
    //                 if(empty($internetData['wifi_package_id'])){
    //                     $package_id = $internetData['broadband_package_id'];
    //                     $packageType = 'broadband';
    //                 }

    //                 if($packageType === 'broadband'){
    //                     $internetPassword = (new \App\Classes\CustomHelpers)->generate_new_password(6);
    //                 } else {
    //                     $internetPassword = (new \App\Classes\CustomHelpers)->generate_new_password();
    //                 }

    //                 // get zone_id from user latitude,longitude
    //                 if(!empty($latitude) && !empty($longitude)){
    //                     $zoneInfo = (new \App\Http\Controllers\panel\location\GeoController)->getZoneInfo($latitude, $longitude);
    //                     if($zoneInfo !== null && $zoneInfo['zone_id'] !== null){
    //                         $zone_id = $zoneInfo['zone_id'];
    //                     }
    //                 }


    //                 $newUser = AppsInternetUsersController::createInternetUser($uid, $internetPassword, $packageType, $package_id, $zone_id, !empty($internetData['current_conn_type']) ? $internetData['current_conn_type'] : null, !empty($internetData['provider_names']) ? $internetData['provider_names'] : null, $latitude, $longitude);

    //                 if($request->get('package_type') === 'wifi'){
    //                     $radiusDbStatus = RadiusServerController::create_radius_db_information($auth_id, $package_id);
    //                     if(!$radiusDbStatus){
    //                         Log::debug('user_radius_insert_error1', [$auth_id]);
    //                     }
    //                 }
    //                 if($newUser->id){
    //                     return redirect($request->get('redirect') . '?status=success&message_key=sms_for_app_credentials');
    //                 } else {
    //                     return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
    //                 }
    //             }
    //             else {
    //                 return redirect($request->get('redirect') . '?status=error&error_type=already_applied');
    //             }
    //         }
    //         else {
    //             return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
    //         }

    //     }
    //     return redirect($request->get('redirect') . '?status=error&error_type=mobile_number_required');
    // }

    public function webRegisterNetworkSupportCenter(Request $request) {

        $profileData = $request->get('profile');
        $centerData = $request->get('data');

        $request->validate([
            'full_name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'coverage_ids' => 'required'
        ]);

        if($profileData['mobile_number'] !== null){
            $mobileNumber = $profileData['mobile_number'];

            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $auth_id)->exists()){
                $userData = User::where('auth_id', '=', $auth_id)->first();
                $uid = $userData['id'];
            }
            else {
                //create new user
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                // create new profile
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $profileData['full_name'];
                $userProfile->mobile_number = $profileData['mobile_number'];
                $userProfile->whatsapp_number = $profileData['whatsapp_number'];
                $userProfile->email = $profileData['email'];
                $userProfile->profession = $profileData['profession'];
                $userProfile->nid = $profileData['nid'];
                $userProfile->gender = $profileData['gender'];
                $userProfile->division_id = $profileData['division_id'];
                $userProfile->district_id = $profileData['district_id'];
                $userProfile->upazila_id = $profileData['upazila_id'];
                $userProfile->union_id = $profileData['union_id'];
                $userProfile->village_id = $profileData['village_id'];
                $userProfile->address = $profileData['address'];
                $userProfile->latitude = $profileData['latitude'];
                $userProfile->longitude = $profileData['longitude'];
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                MessageAndNotificationController::createNewMessage($uid, 1, 'স্বাগতম!', 'স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।');
                $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$auth_id." এবং পাসওয়ার্ড: " . $password;
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($auth_id, $smsText);
            }

            if(!empty($uid)){
                if(!NetworkSupportCenter::where('uid', '=', $uid)->exists()){

                    $newCenter = new NetworkSupportCenter();
                    $newCenter->uid = $uid;
                    $newCenter->zone_name = null;
                    $newCenter->support_number = $profileData['mobile_number'];
                    $newCenter->email = $profileData['email'];
                    $newCenter->center_type = $centerData['center_type'];
                    $newCenter->coverage_type = $centerData['coverage_type'];
                    $newCenter->coverage_ids = implode(",", $centerData['coverage_ids']);
                    $newCenter->division_id = $profileData['division_id'];
                    $newCenter->district_id = $profileData['district_id'];
                    $newCenter->upazila_id = $profileData['upazila_id'];
                    $newCenter->union_id = $profileData['union_id'];
                    $newCenter->village_id = $profileData['village_id'];
                    $newCenter->latitude = $profileData['latitude'];
                    $newCenter->longitude = $profileData['longitude'];
                    $newCenter->address = $profileData['address'];

                    if(!empty($centerData['data_object']) && !empty($centerData['data_object']['existing_facilities'])){
                        $centerData['data_object']['existing_facilities'] = implode(",", $centerData['data_object']['existing_facilities']);
                    }

                    $dataObject = !empty($centerData['data_object']) ? $centerData['data_object'] : array();
                    $newCenter->data_object = json_encode($dataObject);

                    $newCenter->updated_by = $uid;
                    $newCenter->save();

                    if($newCenter->id){
                        return redirect($request->get('redirect') . '?status=success&message_key=sms_for_app_credentials');
                    } else {
                        return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
                    }
                }
                else {
                    return redirect($request->get('redirect') . '?status=error&error_type=already_applied');
                }
            }
            else {
                return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
            }
        }
        return redirect($request->get('redirect') . '?status=error&error_type=mobile_number_required');
    }

    public function webRegisterResumeApplication(Request $request){
        $profileData = $request->get('profile');
        $othersData = $request->get('data');
        $check = CareerResume::where('mobile_number',$profileData['mobile_number'])->exists();
        if($check){
            return redirect($request->get('redirect') . '?status=error&message_key=this number already in use.');
        } else {
            $query = new CareerResume();
            $query->career_id = $othersData['career_id'];
            $query->full_name_bn = $profileData['full_name_bn'];
            $query->full_name_en = $profileData['full_name_en'];
            $query->mobile_number = $profileData['mobile_number'];
            $query->whatsapp_number = $profileData['whatsapp_number'];
            $query->email = $profileData['email'];
            $query->nid_number = $profileData['nid_number'];
            $query->date_of_birth = $profileData['date_of_birth'];
            $query->nationality = $profileData['nationality'];
            $query->division_id = $profileData['division_id'];
            $query->district_id = $profileData['district_id'];
            $query->upazila_id = $profileData['upazila_id'];
            $query->union_id = $profileData['union_id'];
            $query->village_id = $profileData['village_id'];
            $query->address_details = $profileData['address_details'];
            $query->latitude = $profileData['latitude'];
            $query->longitude = $profileData['longitude'];
            $query->educations = json_encode($othersData['educations']);
            $query->certifications = json_encode($othersData['certifications']);
            $query->experiences = json_encode($othersData['experiences']);
            $query->languages = json_encode($othersData['languages']);
            $query->others_activity = $othersData['others_activity'];
            $query->save();
            if($query->id){
                return redirect($request->get('redirect') . '?status=success&message_key=success');
            } else {
                return redirect($request->get('redirect') . '?status=error&message_key=can_not_submit');
            }
        }
        return redirect($request->get('redirect') . '?status=error&error_type=mobile_number_required');
    }

    public function webRegisterSalesAgent(Request $request) {
        $profileData = $request->get('profile');
        if($profileData['mobile_number'] !== null){
            $mobileNumber = $profileData['mobile_number'];
            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $auth_id)->exists()){
                $userData = User::where('auth_id', '=', $auth_id)->first();
                $uid = $userData['id'];
            }
            else {
                //create new user
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
                $uid = $userData['user']['id'];
                $password = $userData['password'];
                // create new profile
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $profileData['full_name'];
                $userProfile->mobile_number = $profileData['mobile_number'];
                $userProfile->whatsapp_number = $profileData['whatsapp_number'];
                $userProfile->email = $profileData['email'];
                $userProfile->profession = $profileData['profession'];
                $userProfile->nid = $profileData['nid'];
                $userProfile->gender = $profileData['gender'];
                $userProfile->division_id = $profileData['division_id'];
                $userProfile->district_id = $profileData['district_id'];
                $userProfile->upazila_id = $profileData['upazila_id'];
                $userProfile->union_id = $profileData['union_id'];
                $userProfile->village_id = $profileData['village_id'];
                $userProfile->address = $profileData['address'];
                $userProfile->address_direction = $profileData['address_direction'];
                $userProfile->latitude = $profileData['latitude'];
                $userProfile->longitude = $profileData['longitude'];
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                (new \App\Http\Controllers\MessageAndNotificationController)->createNewMessage($uid, 1, 'স্বাগতম!', 'স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।');
                $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$auth_id." এবং পাসওয়ার্ড: " . $password;
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($auth_id, $smsText);
            }

            if(!empty($uid)){
                if(!SalesAgent::where('uid', '=', $uid)->exists()){
                    $salesAgent = new SalesAgent();
                    $salesAgent->uid = $uid;
                    $salesAgent->nid = $profileData['nid'];
                    $salesAgent->birth_date = $profileData['birth_date'];
                    $salesAgent->photo_source = null;
                    $data_object = array();
                    $salesAgent->data_object = json_encode($data_object);
                    $salesAgent->save();

                    if($salesAgent->id){
                        return redirect($request->get('redirect') . '?status=success&message_key=sms_for_app_credentials');
                    } else {
                        return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
                    }
                }
                else {
                    return redirect($request->get('redirect') . '?status=error&error_type=already_applied');
                }
            }
            else {
                return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
            }

        }
        return redirect($request->get('redirect') . '?status=error&error_type=mobile_number_required');
    }

    public function webRegisterSalesPoint(Request $request) {
        $profileData = $request->get('profile');
        $storeData = $request->get('data');

        if($profileData['mobile_number'] !== null){
            $mobileNumber = $profileData['mobile_number'];
            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $auth_id)->exists()){
                $userData = User::where('auth_id', '=', $auth_id)->first();
                $uid = $userData['id'];
            } else {
                //create new user
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'user');
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                // create new profile
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $profileData['full_name'];
                $userProfile->mobile_number = $profileData['mobile_number'];
                $userProfile->whatsapp_number = $profileData['whatsapp_number'];
                $userProfile->email = $profileData['email'];
                $userProfile->profession = $profileData['profession'];
                $userProfile->nid = $profileData['nid'];
                $userProfile->gender = $profileData['gender'];
                $userProfile->division_id = $profileData['division_id'];
                $userProfile->district_id = $profileData['district_id'];
                $userProfile->upazila_id = $profileData['upazila_id'];
                $userProfile->union_id = $profileData['union_id'];
                $userProfile->village_id = $profileData['village_id'];
                $userProfile->address = $profileData['address'];
                $userProfile->address_direction = $profileData['address_direction'];
                $userProfile->latitude = $profileData['latitude'];
                $userProfile->longitude = $profileData['longitude'];
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                (new \App\Http\Controllers\MessageAndNotificationController)->createNewMessage($uid, 1, 'স্বাগতম!', 'স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।');
                $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$auth_id." এবং পাসওয়ার্ড: " . $password;
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($auth_id, $smsText);
            }

            if(!empty($uid)){
                if(!SalesPoint::where('uid', '=', $uid)->exists()){
                    $salesPoint = new SalesPoint();
                    $salesPoint->uid = $uid;
                    $salesPoint->store_name = $storeData['store_name'];
                    $salesPoint->trade_licence = $storeData['trade_licence'];
                    $salesPoint->division_id = $storeData['store_address']['division_id'];
                    $salesPoint->district_id = $storeData['store_address']['district_id'];
                    $salesPoint->upazila_id = $storeData['store_address']['upazila_id'];
                    $salesPoint->union_id = $storeData['store_address']['union_id'];
                    $salesPoint->village_id = $storeData['store_address']['village_id'];
                    $salesPoint->address = $storeData['store_address']['address'];
                    $salesPoint->latitude = $storeData['store_address']['latitude'];
                    $salesPoint->longitude = $storeData['store_address']['longitude'];
                    $salesPoint->logo_source = null;
                    $salesPoint->data_object = json_encode($storeData['data_object']);
                    $salesPoint->save();

                    if($salesPoint->id){
                        return redirect($request->get('redirect') . '?status=success&message_key=sms_for_app_credentials');
                    } else {
                        return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
                    }
                } else {
                    return redirect($request->get('redirect') . '?status=error&error_type=already_applied');
                }
            } else {
                return redirect($request->get('redirect') . '?status=error&error_type=something_wrong');
            }
        }
        return redirect($request->get('redirect') . '?status=error&error_type=mobile_number_required');
    }

    public function webRegisterISPBusinessCenter(Request $request) {

        $profileData = $request->get('profile');
        Log::info($profileData);
        $centerData = $request->get('data');

        $request->validate([
            'profile.mobile_number' => 'required|string|min:11|max:13|regex:/^([0-9\s\-\+\(\)]*)$/'
        ]);

        if($profileData['mobile_number'] !== null){
            $mobileNumber = $profileData['mobile_number'];

            // checking if user exists or not, if not then create new one
            $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
            if(User::where('auth_id', '=', $mobileNumber)->exists()){
                return redirect($request->get('redirect') . '?status=error&error_type=already_applied');
            } else {
                //create new user
                $userData = (new \App\Classes\CustomHelpers)->create_new_user($auth_id, 'corporate');
                $uid = $userData['user']['id'];
                $password = $userData['password'];

                // create new profile
                $userProfile = new UserProfile();
                $userProfile->uid = $uid;
                $userProfile->full_name = $profileData['full_name'];
                $userProfile->mobile_number = $profileData['mobile_number'];
                $userProfile->whatsapp_number = $profileData['whatsapp_number'];
                $userProfile->email = $profileData['email'];
                $userProfile->profession = $profileData['profession'];
                $userProfile->gender = $profileData['gender'];
                $userProfile->division_id = $profileData['division_id'];
                $userProfile->district_id = $profileData['district_id'];
                $userProfile->upazila_id = $profileData['upazila_id'];
                $userProfile->union_id = $profileData['union_id'];
                $userProfile->village_id = $profileData['village_id'];
                $userProfile->house_no = $profileData['house_no'];
                $userProfile->address = $profileData['address'];
                $userProfile->latitude = $profileData['latitude'];
                $userProfile->longitude = $profileData['longitude'];
                $userProfile->address_direction = $profileData['address_direction'];
                $userProfile->device_info = json_encode(["brand"=>"website"]);
                $userProfile->save();

                // creating data to client table
                $newCenter = new CorporateClient();
                $newCenter->uid = $uid;
                $newCenter->company_name = $centerData['institution_name'];
                $newCenter->using_softwares = 'log_server,billing_software,network_monitoring_software'; 
                $newCenter->using_devices = 'junipar,mikrotik,olt,indoor_device';
                $newCenter->status = 'pending';
                $newCenter->updated_by = $uid;
                $newCenter->save();

                // Sending Message ----
                MessageAndNotificationController::createNewMessage($uid, 1, 'স্বাগতম!', 'আপনি আইএসপি সফটওয়্যার নেওয়ার জন্য আগ্রহী, আপনার সাথে আমরা খুব দ্রুত যোগাযোগ করব। ');
                $smsText = "আপনার স্বাধীন ওয়াইফাই ইউজার আইডি: ".$mobileNumber." এবং পাসওয়ার্ড: " . $password;
                if($smsText){
                    (new \App\Classes\CustomHelpers)->send_text_sms($mobileNumber, $smsText);
                }

                return redirect($request->get('redirect') . '?status=success&message_key=Success! We have send you a message to your mobile with username and password.');
            }
        }else{
            return redirect($request->get('redirect') . '?status=error&error_type=mobile_number_required');
        }
    }
}
