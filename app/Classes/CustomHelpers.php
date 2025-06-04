<?php

namespace App\Classes;

use App\Models\AffiliateHistory;
use App\Models\AgentSettings;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\ErrorLogPayment;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\MonthlyCommission;
use App\Models\MonthlyCommissionBreakdownZone;
use App\Models\NetworkSupportCenter;
use App\Models\Payment;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserRole;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomHelpers {

    public function calculateCommissionRateSlav(int $totalUsers, $zoneInfo, $filteredDate){

        if($totalUsers >= 1001){
            $commissionRate = ($totalUsers * 42) / 100;
        } else if($totalUsers >= 500 && $totalUsers <= 1000){
            $commissionRate = ($totalUsers * 40) / 100;
        } else if($totalUsers >= 200 && $totalUsers <= 499){
            $commissionRate = ($totalUsers * 35) / 100;
        } else {
            $commissionRate = 30;
        }

        $commissionRateType = NetworkSupportCenter::where('zone_id', '=', $zoneInfo['zone_id'])->value('commission_rate_type');
        $monthlyCommission = MonthlyCommissionBreakdownZone::where('zone_id', '=', $zoneInfo['zone_id'])->whereMonth('date_month', '=', $filteredDate->month)->whereYear('date_month', '=', $filteredDate->year)->first();
        if($commissionRateType === 'fixed'){
            $commission_rate_wifi = $monthlyCommission['commission_rate_wifi'];
            $commission_rate_broadband = $monthlyCommission['commission_rate_broadband'];
        } else {
            if($zoneInfo['broadband_commission_rate'] > 0){
                // fixed commission for this zone
                $commission_rate_broadband = $zoneInfo['broadband_commission_rate'];
            } else {
                // calculate dynamically from users
                $commission_rate_broadband = $commissionRate;
            }

            if($zoneInfo['wifi_commission_rate'] > 0){
                $commission_rate_wifi = $zoneInfo['wifi_commission_rate'];
            } else {
                // calculate dynamically from users
                $commission_rate_wifi = $commissionRate;
            }

            // insert missing row
            if($monthlyCommission === null){
                MonthlyCommissionBreakdownZone::where('zone_id', '=', $zoneInfo['zone_id'])->create(['zone_id'=>$zoneInfo['zone_id'], 'date_month'=>$filteredDate, 'commission_rate_wifi'=>$commission_rate_wifi, 'commission_rate_broadband'=>$commission_rate_broadband]);
            }
        }

        return ['wifi_commission_rate'=>$commission_rate_wifi, 'broadband_commission_rate'=>$commission_rate_broadband];
    }

    public function getAgentsMonthlyCommissionRate($agent_type, $agentUid){
        $monthlyCommissionRate = 0;
        if($agent_type === 'sales_agent'){
            $commissionRate = SalesAgent::where('uid', '=', $agentUid)->where('status', '=', 'active')->value('monthly_commission_rate');
            if($commissionRate > 0 && $commissionRate !== null){
                $monthlyCommissionRate = $commissionRate;
            }
        } else if($agent_type === 'sales_point'){
            $commissionRate = SalesPoint::where('uid', '=', $agentUid)->where('status', '=', 'active')->value('monthly_commission_rate');
            if($commissionRate > 0 && $commissionRate !== null){
                $monthlyCommissionRate = $commissionRate;
            }
        }
        return $monthlyCommissionRate;
    }

    public function getAgentSettings(){
        return AgentSettings::find(1);
    }

    public function insertErrorLogPayment($auth_id, $uid, $zone_id, $trx_id, $error_type) : void {
        ErrorLogPayment::create(["auth_id"=> $auth_id, "uid"=>$uid, "zone_id"=>$zone_id, "trx_id"=>$trx_id, "error_type"=>$error_type]);
    }

    public function syncOldBroadbandUserWithNewErp($auth_id, $uid, $zone_id) : void {
        $existSecret = BroadbandDbSecret::where('username', '=', $auth_id)->first();
        if($existSecret !== null){
            $subscriberInfo = BroadbandDbSubscriberInfo::where('numAsId', '=', $auth_id)->first();
            $packageInfo = InternetPackage::where('mikrotik_radius_group_name', '=', strtolower($existSecret['profile']))->where('is_active', '=', 1)->first();
            if($packageInfo !== null){
                $newBroadbandUserData = InternetUsers::where('uid', '=', $uid)->first();
                $newBroadbandUserData->password_broadband = $existSecret['password'];
                $newBroadbandUserData->zone_id = $zone_id;
                $newBroadbandUserData->serial_number = $subscriberInfo['serial'];
                $newBroadbandUserData->package_id = $packageInfo['id'];
                $newBroadbandUserData->broadband_pop_id = $subscriberInfo['popId'];
                $newBroadbandUserData->installation_charge = $subscriberInfo['instcost'];
                $newBroadbandUserData->connection_media = $subscriberInfo['connectionMedia'];
                $newBroadbandUserData->connection_status = 'active';
                $newBroadbandUserData->save();
            }
        }
    }

    public function apiSecrets($type): string
    {
        $secrets = '';
        if($type === 'openrouteservice'){
            $secrets = '5b3ce3597851110001cf6248780f81291c814fa3bba0ba957919198c';
        }
        return $secrets;
    }

    public function calculateDistanceBetweenTwoPoints($latitudeOne='', $longitudeOne='', $latitudeTwo='', $longitudeTwo='',$distanceUnit ='',$round=false,$decimalPoints='')
    {
        if (empty($decimalPoints))
        {
            $decimalPoints = '3';
        }
        if (empty($distanceUnit)) {
            $distanceUnit = 'KM';
        }
        $distanceUnit = strtolower($distanceUnit);
        $pointDifference = $longitudeOne - $longitudeTwo;
        $toSin = (sin(deg2rad($latitudeOne)) * sin(deg2rad($latitudeTwo))) + (cos(deg2rad($latitudeOne)) * cos(deg2rad($latitudeTwo)) * cos(deg2rad($pointDifference)));
        $toAcos = acos($toSin);
        $toRad2Deg = rad2deg($toAcos);

        $toMiles  =  $toRad2Deg * 60 * 1.1515;
        $toKilometers = $toMiles * 1.609344;
        $toNauticalMiles = $toMiles * 0.8684;
        $toMeters = $toKilometers * 1000;
        $toFeets = $toMiles * 5280;
        $toYards = $toFeets / 3;


        switch (strtoupper($distanceUnit))
        {
            case 'ML'://miles
                $toMiles  = ($round == true ? round($toMiles) : round($toMiles, $decimalPoints));
                return $toMiles;
                break;
            case 'KM'://Kilometers
                $toKilometers  = ($round == true ? round($toKilometers) : round($toKilometers, $decimalPoints));
                return $toKilometers;
                break;
            case 'MT'://Meters
                $toMeters  = ($round == true ? round($toMeters) : round($toMeters, $decimalPoints));
                return $toMeters;
                break;
            case 'FT'://feets
                $toFeets  = ($round == true ? round($toFeets) : round($toFeets, $decimalPoints));
                return $toFeets;
                break;
            case 'YD'://yards
                $toYards  = ($round == true ? round($toYards) : round($toYards, $decimalPoints));
                return $toYards;
                break;
            case 'NM'://Nautical miles
                $toNauticalMiles  = ($round == true ? round($toNauticalMiles) : round($toNauticalMiles, $decimalPoints));
                return $toNauticalMiles;
                break;
        }


    }

    public function external_hash_verification($requestHash) : bool {
        $hashString = 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e';
        return $requestHash === $hashString;
    }

    public function generate_new_uuid(){
        return Str::uuid()->toString();
    }

    public function generate_unique_numbers($digit){
        switch ($digit) {
            case 4:
                $result = random_int(1000, 9999);
                break;
            case 6:
                $result = random_int(100000, 999999);
                break;
            case 9:
                $result = random_int(100000000, 999999999);
                break;
            default:
                $result = random_int(100000, 999999);
        }
        return $result;
    }

    public function generate_new_password($digit = 4){
        if($digit === 4){
            return random_int(1000, 9999);
        }
        return random_int(100000, 999999);
    }

    // public static function generate_new_password_6($digit = 6){
    //     if($digit === 6){
    //         return random_int(100000, 999999);
    //     }
    //     return random_int(1000, 9999);
    // }

    public function auth_id_validation($mobile_number){
        $auth_id = (string) $mobile_number;
        if(is_numeric($auth_id)){
            if($mobile_number[0] !== '0'){
                $auth_id = '0'. $mobile_number;
            }
        }
        return $auth_id;
    }

    public function getUidByAuthId($auth_id){
        return User::where('auth_id', '=', $auth_id)->value('id');
    }

    public function getNetworkSupportCenterZoneInfo($zone_id){
        return NetworkSupportCenter::where('zone_id', '=', $zone_id)->first(['zone_name', 'simultaneous_use_disable']);
    }

    public function create_basic_user_profile($uid, $full_name, $mobile_number, $userLocationData){
        $user = new UserProfile();
        $user->uid = $uid;
        $user->full_name = $full_name;
        $user->mobile_number = $mobile_number;
        $user->division_id = $userLocationData['division_id'];
        $user->district_id = $userLocationData['district_id'];
        $user->upazila_id = $userLocationData['upazila_id'];
        $user->union_id = $userLocationData['union_id'];
        $user->village_id = $userLocationData['village_id'];
        return $user->save();
    }

    public function english_to_bangla_numeric($englishDigit) {

        if($englishDigit == null){
            return "";
        }

        $banglaNumber = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯','.',':','-'];
        $englishNumber = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9','.',':','-'];


        $englishDigitStr = (string) $englishDigit;
        $banglaDigit = "";
        for ($i = 0; $i < strlen($englishDigitStr); $i++) {
            if (in_array($englishDigitStr[$i], $englishNumber)) {
                $banglaDigit .= $banglaNumber[array_search($englishDigitStr[$i], $englishNumber)];
            } else {
                $banglaDigit .= $englishDigitStr[$i];
            }
        }
        return $banglaDigit;
    }

    public function generate_user_address($uid = null, $addressArray = null) {

        $uProfile = [];
        if($uid !== null){
            $uProfile = UserProfile::where('uid', '=', $uid)->first();
        } else if($addressArray !== null){
            $uProfile = $addressArray;
        }

        if($uProfile == null || $uProfile['division_id'] === null){
            return ["bn"=>"-", "en"=>"-"];
        }

        $GeoDivision = GeoDivision::where('id', '=', $uProfile['division_id'])->first('bn_name','en_name');
        if($uProfile['district_id'] !== 1000000){
            $GeoDistrict = GeoDistrict::where('id', '=', $uProfile['district_id'])->first(['bn_name','en_name']);
        } else {$GeoDistrict = ["bn_name"=>"তালিকাভুক্ত নয়", "en_name"=>"not listed"];}

        if($uProfile['upazila_id'] !== 1000000){
            $GeoUpazila = GeoUpazila::where('id', '=', $uProfile['upazila_id'])->first(['bn_name','en_name']);
        } else {$GeoUpazila = ["bn_name"=>"তালিকাভুক্ত নয়", "en_name"=>"not listed"];}

        if($uProfile['union_id'] !== 1000000){
            $GeoUnionPouroshova = GeoUnionPouroshova::where('id', '=', $uProfile['union_id'])->first(['bn_name','en_name']);
        } else {$GeoUnionPouroshova = ["bn_name"=>"তালিকাভুক্ত নয়", "en_name"=>"not listed"];}

        if($uProfile['village_id'] !== 1000000){
            $GeoVillage = GeoVillage::where('id', '=', $uProfile['village_id'])->first(['bn_name','en_name']);
        } else {$GeoVillage = ["bn_name"=>"তালিকাভুক্ত নয়", "en_name"=>"not listed"];}

        //.self::english_to_bangla_numeric($uProfile['house_no']).
        $houseNoEn = '-';
        $houseNoBn = '-';
        if(!empty($uProfile['house_no']) && is_numeric($uProfile['house_no'])){
            $houseNoBn = self::english_to_bangla_numeric($uProfile['house_no']);
            $houseNoEn = $uProfile['house_no'];
        }

        $address_direction = '-';
        if(!empty($uProfile['address_direction'])){
            $address_direction = !empty($uProfile['address_direction']);
        }
        $bn_name = '-';
        $en_name = '-';
        if(!empty($GeoVillage['bn_name'])){
            $bn_name = $GeoVillage['bn_name'];
        }
        if(!empty($GeoVillage['en_name'])){
            $en_name = $GeoVillage['en_name'];
        }

        $unionBn = '-';
        $unionEn = '-';
        if(!empty($GeoUnionPouroshova['bn_name'])){
            $unionBn = $GeoUnionPouroshova['bn_name'];
        }
        if(!empty($GeoUnionPouroshova['en_name'])){
            $unionEn = $GeoUnionPouroshova['en_name'];
        }


        return [
            "bn"=> "বাড়ি: ".$houseNoBn.", গ্রাম: ".$bn_name.", ইউনিয়ন/পৌরসভা: ".$unionBn.", থানা/উপজেলা: ".$GeoUpazila['bn_name'].", জেলা: ".$GeoDistrict['bn_name'].", বিভাগ: ".$GeoDivision['bn_name']."। (".$address_direction.")",
            "en"=> "House: ".$houseNoEn.", Village: ".$en_name.", Union/Pouroshova: ".$unionEn.", PS/Upazila: ".$GeoUpazila['en_name'].", District: ".$GeoDistrict['en_name'].", Division: ".$GeoDivision['en_name']."। (".$address_direction.")",
        ];
    }

    public function update_user_password($auth_id, $password = null){

        if(!$password){
            $password = self::generate_new_password();
        }

        $auth_id = self::auth_id_validation($auth_id);
        $uid = self::getUidByAuthId($auth_id);
        $user = User::find($uid);
        if($user !== null){
            $user->password = Hash::make($password);
            $user->text_password = $password;
            $user->save();
        } else {
            return ['user'=>$user, 'password'=> $password, 'status'=> 'account_not_found'];
        }

        return ['user'=>$user, 'password'=> $password, 'status'=> 'success'];
    }

    public function create_new_user($mobile_number, $base_role, $package_type = null){

        $auth_id = self::auth_id_validation($mobile_number);
        if($package_type === 'broadband'){
            $password = self::generate_new_password(6);
        }else{
            $password = self::generate_new_password();
        }

        $user = new User();
        $user->auth_id = $auth_id;
        $user->status = 'active';
        $user->base_role = $base_role;
        $user->panel_access = 0;
        $user->password = Hash::make($password);
        $user->text_password = $password;
        $user->save();
        return ['user'=>$user, 'password'=> $password];
    }
    
    public function generate_strong_password()
    {
        $length = random_int(8, 10);

        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%&';

        // Ensure the password has at least one of each required character type
        $password = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $special[random_int(0, strlen($special) - 1)],
        ];

        // Fill the remaining characters randomly from all sets combined
        $all = $upper . $lower . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle to make the password unpredictable
        shuffle($password);

        return implode('', $password);
    }

    public function user_auth_login($auth_id, $password, $device_name){

        $user = User::where('auth_id', $auth_id)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'auth_id' => ['The provided credentials are incorrect.'],
            ]);
        }
        $user->roles = UserRole::where('auth_id', '=', $user->auth_id)->pluck('rid')->toArray();
        if(empty($user->permissions)){
            $user->permissions = [];
        } else {
            $user->permissions = json_decode($user->permissions);
        }


        $user->personal_data = [];
        switch ($user->user_type){
//            case 'internet_user':
//                $user->personal_data = InternetUser::where('auth_id','=', $user->auth_id)->first(['id','full_name','status', 'auth_id','mobile_number']);
//                break;
//            case 'network_partner':
//                $user->personal_data = NetworkPartner::where('auth_id','=', $user->auth_id)->first(['id','full_name','status', 'auth_id','mobile_number']);
//                break;
//            case 'employee':
//                $user->personal_data = Employee::where('auth_id','=', $user->auth_id)->first(['id','full_name','status', 'auth_id','mobile_number']);
//                break;
//            case 'agent':
//                $user->personal_data = Agent::where('auth_id','=', $user->auth_id)->first(['id','full_name','status', 'auth_id','mobile_number']);
//                break;
        }

        $auth_prevent_status = false;
        $auth_prevent_message = '';
        if($user->personal_data['status'] === 'pending' || $user->personal_data['status'] === 'inactive'){
            $auth_prevent_status = true;
            $auth_prevent_message = 'Account Pending or Inactive';
        } else if($user->personal_data['status'] === 'blocked'){
            $auth_prevent_status = true;
            $auth_prevent_message = 'Account Temporary Blocked';
        } else if($user->personal_data['status'] === 'canceled'){
            $auth_prevent_status = true;
            $auth_prevent_message = 'Account Temporary Suspended';
        }

        if($auth_prevent_status){
            return [
                "status" => 'error',
                'auth_prevent'=> true,
                'message'=>$auth_prevent_message
            ];
        }

        return [
            "accessToken" => $user->createToken($device_name)->plainTextToken,
            "auth_prevent"=> false,
            "user" => $user,
            "status" => 'success'
        ];
    }

    public function custom_string_replace($string, $search, $replace): string {
        return str_replace($search,$replace,$string);
    }

    public function custom_formatted_date_time($datetime, $format = 'Y-M-d H:m:s'): string {
        if(!$datetime){
            return 'never';
        }
        return Carbon::parse($datetime)->format($format);
    }

    public function add_minutes_with_datetime($minutes) : Carbon {
        $datetime_string =  Carbon::now();
        return Carbon::createFromFormat('Y-m-d H:i:s', $datetime_string)->addMinutes((int) $minutes);
    }

    public function get_base64_image_size($base64Image, $max_kb = null){
        try{
            $size_in_bytes = (int) (strlen(rtrim($base64Image, '=')) * 0.75);
            $size_in_kb = ($size_in_bytes / 1024);

            if($max_kb){
                return $size_in_kb <= $max_kb;
            }
            return $size_in_kb;
        }
        catch(Exception $e){
            return $e;
        }
    }

    public function send_text_sms($mobile_number, $message){
//        $smsEndpoint="https://smsplus.sslwireless.com/api/v3/send-sms";
//        $smsParams = array(
//            'api_token'=> "PlexusSoftware-a7202cad-3b2c-4965-9617-f42f7bfc1efc",
//            'sid'=> "SHADHINWIFIOTP",
//            'msisdn'=> $mobile_number,
//            'sms'=> $message,
//            'csms_id'=> substr( str_shuffle("1234567890"), 0, 10 ),
//
//        );
//
//        $response = Http::post($smsEndpoint, $smsParams);
        $response = Http::get('https://user.plexuscloud.com.bd/send-sms.php?send_to='.$mobile_number.'&message='.$message);
        $responseBody = json_decode($response, true);
        return strtolower($responseBody);
    }
    
    public function sendSmsRtcom($smsText, $mobile) {

        $url = 'https://api.rtcom.xyz/onetomany';

        $payload = [
            'acode'           => '30000074',
            'api_key'         => '50f296eeaa781030b0d6fb90aef67e5267c164cd',
            'senderid'        => 'Fly FAr International', // Replace with your actual sender ID
            'type'            => 'text',
            'msg'             => $smsText,
            'contacts'        => $mobile, // Example: "+8801XXXXXXXXX"
            'transactionType' => 'T',
            'contentID'       => ''
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function sendSmsNovocom($message, $mobileNumber) {
        $url = 'https://sms.novocom-bd.com/api/v2/SendSMS';
    
        $data = [
            "SenderId"      => "8809638002777",      // Your registered Sender ID
            "Message"       => $message,             // Your SMS message
            "MobileNumbers" => "88".$mobileNumber,        // Example: "8801XXXXXXXXX"
            "ApiKey"        => "71MpKoygyOkYyz1uNkZmFdQXeNs/u2nH9M4dp6Y2krY=",          // Your API key
            "ClientId"      => "822d3141-b00b-4c72-bd74-161a9e0dfb31"            // Your Client ID
        ];
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
    
        $response = curl_exec($ch);
        curl_close($ch);
    
        return $response;
    }
    
    public function payment_credentials($vendor_name){
        $vendor_credentials = [
            'bkash' => [
                'app_key'=>'71cq1rvcpai56i4f845t4vucfh',
                'app_secret'=>'1h2ncj4cc07vivetne5066n2gjkj59b2leb6jogttbvu45fmj21a',
                'username'=>'PLEXUSCLOUD',
                'password'=>'P7@3L0dDcUw',
                'token_url' => 'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/token/grant',
                'create_url'=>'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/payment/create',
                'execute_url'=>'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/payment/execute/'
            ],
            'nagad' => []
        ];
        return $vendor_credentials[$vendor_name];
    }

    public function get_payment_token($vendor_name){

        $vCredentials = CustomHelpers::payment_credentials($vendor_name);
        $results = null;
        if($vendor_name === 'bkash'){
            $tokenUrl = $vCredentials['token_url'];

            $url=curl_init($tokenUrl);
            $credentials=json_encode(array('app_key'=>$vCredentials['app_key'], 'app_secret'=>$vCredentials['app_secret']));
            $header=array('Content-Type:application/json', 'username:'.$vCredentials['username'], 'password:'.$vCredentials['password']);
            curl_setopt($url,CURLOPT_HTTPHEADER, $header);
            curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url,CURLOPT_POSTFIELDS, $credentials);
            curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
            $resultData = curl_exec($url);
            curl_close($url);
            $results = json_decode($resultData, true);
        }

        return $results;
    }

    public function create_new_affiliate_history($affiliator_uid, $product_type, $product_id, $commission_amount) : int {

        if(!AffiliateHistory::where('affiliator_uid', '=', $affiliator_uid)->where('product_type', '=', $product_type)->where('product_id', '=', $product_id)->exists()){
            $query = new AffiliateHistory();
            $query->affiliator_uid = $affiliator_uid;
            $query->product_type = $product_type;
            $query->product_id = $product_id;
            $query->commission_amount = $commission_amount;
            return $query->save();
        }
        return 0;
    }

    public function create_new_transaction($receiver_uid, $senderUid, $trx_type, $method, $amount, $plus_minus, $purpose) : bool {
        $newTransaction = new Transaction();
        $newTransaction->trx_id = time().'U'.$receiver_uid;
        $newTransaction->trx_type = $trx_type;
        $newTransaction->plus_minus = $plus_minus;
        $newTransaction->sender_uid = $senderUid;
        $newTransaction->receiver_uid = $receiver_uid;
        $newTransaction->method = $method;
        $newTransaction->amount = $amount;
        $newTransaction->purpose = $purpose;
        return $newTransaction->save();
    }

    public static function generateTransactionID($prefix = 'TXN') {
        // Get the current timestamp in milliseconds
        $timestamp = round(microtime(true) * 1000);

        // Generate a random number
        $randomNumber = mt_rand(100000, 999999);

        // Combine the prefix, timestamp, and random number
        $transactionID = $prefix . $timestamp . $randomNumber;

        return $transactionID;
    }

    public static function generateInvoiceID($prefix = 'INV') {
        // Get the current timestamp in milliseconds
        $timestamp = round(microtime(true) * 1000);

        // Generate a random number
        $randomNumber = mt_rand(1000, 9999);

        // Combine the prefix, timestamp, and random number
        $invoiceID = $prefix . $timestamp . $randomNumber;

        return $invoiceID;
    }
    public static function generatePaymentID($trxID, $invoiceID) {
        // Combine the transaction ID and invoice ID
        $paymentID = $trxID . '-' . $invoiceID;

        return $paymentID;
    }

    public static function getCommissionAmount($added_by_user_table, $package_estimated_price) {
        $commission_amount = 0;
        if($added_by_user_table->base_role === 'agent') {
            $commission = CorporateAgent::where('uid', $added_by_user_table->id)->value('commission');
            $commission_amount = ($commission / 100) * $package_estimated_price;
            //Log::info($commission_amount);
            //Log::info($commission);
        } elseif($added_by_user_table->base_role === 'sub_agent') {
            $sub_agent_info = CorporateSubAgent::where('uid', $added_by_user_table->id)->first();
            $commission_amount = ($sub_agent_info->commission / 100) * $package_estimated_price;

            // agent commission from sub agent sales
            $agent_info_user_pro = UserProfile::where('uid', $sub_agent_info->agent_id)->first();
            $agent_commission = (CorporateAgent::where('uid', $sub_agent_info->agent_id)->value('commission')) - $sub_agent_info->commission;
            $agent_commission_amount = ($agent_commission / 100) * $package_estimated_price;
            $agent_info_user_pro->update([
                'wallet_amount' => $agent_info_user_pro->wallet_amount + $agent_commission_amount
            ]);
        }
        return $commission_amount;
    }
}
