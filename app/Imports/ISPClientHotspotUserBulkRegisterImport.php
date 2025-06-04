<?php
namespace App\Imports;

use App\Classes\RouterOsApi;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\BroadbandDbZone;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUpazila;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WifiDbRadCheck;
use App\Models\WifiDbRadReply;
use App\Models\WifiDbRadUserGroup;
use App\Models\WifiDbUserInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ISPClientHotspotUserBulkRegisterImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Validate the row data
        $this->validateRow($row);

        // Check if the mobile number is already in use
        $this->checkUserExistence($row['mobile_number']);

        // check user type
        $userType = $this->checkClient($row);

        // Create new user and related profiles
        $newUser = $this->createNewUser($row);

        $this->createUserProfile($row, $newUser, $userType);
        $this->createInternetUser($row, $newUser, $userType);
        $this->createWifiDbUserInfo($row, $userType);
        $this->createWifiDbRadCheck($row, $newUser, $userType);
        $this->createWifiDbRadUserGroup($row, $userType);
        $this->createWifiDbRadReply($row);
    }

    private function validateRow(array $row)
    {
        $validator = Validator::make($row, [
            'full_name' => 'required',
            'mobile_number' => 'required',
            'client_id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception(json_encode($validator->errors()));
        }
    }

    private function checkUserExistence($mobileNumber)
    {
        $auth_id = (new \App\Classes\CustomHelpers)->auth_id_validation($mobileNumber);
        if (User::where('auth_id', '=', $auth_id)->exists()) {
            throw new \Exception(json_encode('This mobile number is already in use - ' . $mobileNumber . '!'));
        }
    }

    private function checkClient(array $row){
        $sub_agent_check = CorporateSubAgent::where('uid', $row['client_id'])->exists();
        $agent_check = CorporateAgent::where('uid', $row['client_id'])->exists();
        $client_check = CorporateClient::where('uid', $row['client_id'])->exists();
        if($sub_agent_check){
            return [
                'client_id' => CorporateSubAgent::where('uid',$row['client_id'])->value('client_id'),
                'agent_id' => CorporateSubAgent::where('uid',$row['client_id'])->value('agent_id'),
                'sub_agent_id' => $row['client_id']
            ];
        }elseif($agent_check){
            return[
                'client_id' => CorporateAgent::where('uid',$row['client_id'])->value('client_id'),
                'agent_id' => $row['client_id']
            ];
        }elseif($client_check){
            return [
                'client_id' => $row['client_id']
            ];
        }else{
            throw new \Exception(json_encode('No client id found!'));
        }
    }

    private function createNewUser(array $row)
    {
        $userData = (new \App\Classes\CustomHelpers)->create_new_user($row['mobile_number'], 'user', 'wifi');
        return [
            'uid' => $userData['user']['id'],
            'password' => $userData['password'],
        ];
    }

    private function createUserProfile(array $row, $newUser, $userType)
    {
        $editor_profile = UserProfile::where('uid',$row['client_id'])->first();

        $profile = new UserProfile();
        $profile->uid = $newUser['uid'];
        $profile->full_name = $row['full_name'];
        $profile->mobile_number = $row['mobile_number'];
        $profile->whatsapp_number = $row['mobile_number'];
        $profile->email = 'noemail@shadhinwifi.com';
        // $profile->profession = $row['profession'] ?? null;
        // $profile->nid = $row['nid'] ?? null;
        // $profile->gender = $row['gender'] ?? null;
        $profile->division_id = $editor_profile->division_id;
        $profile->district_id = $editor_profile->district_id;
        $profile->upazila_id = $editor_profile->upazila_id;
        $profile->union_id = $editor_profile->union_id;
        $profile->village_id = $editor_profile->village_id;
        $profile->house_no = $editor_profile->house_no;
        $profile->address = $editor_profile->address;
        $profile->address_direction = $editor_profile->address_direction;
        $profile->latitude = $editor_profile->latitude ?? "0.00000";
        $profile->longitude = $editor_profile->longitude ?? "0.00000";
        $profile->device_info = json_encode(["brand" => "website"]);
        $profile->save();
    }

    private function createInternetUser(array $row, $newUser, $userType)
    {
        $editor_profile = UserProfile::where('uid',$row['client_id'])->first();

        $internetUser = new InternetUsers();
        $internetUser->uid = $newUser['uid'];

        $package_id = '';
        if (isset($userType['sub_agent_id'])) {
            $client = CorporateSubAgent::where('uid',$userType['client_id'])->value('client_id');
            $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
            $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
        } elseif (isset($userType['agent_id'])) {
            $client = CorporateAgent::where('uid',$userType['client_id'])->value('client_id');
            $package_name = CorporateClient::where('uid',$client)->value('hotspot_profile');
            $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
        } else{
            $package_name = CorporateClient::where('uid',$userType['client_id'])->value('hotspot_profile');
            $package_id = InternetPackageCorporate::where('package_name',$package_name)->value('id');
        }

        if (isset($userType['sub_agent_id'])) {
            $internetUser->package_id = $package_id;
            $internetUser->zone_id = $userType['client_id'];
            $internetUser->agent_id = $userType['agent_id'];
            $internetUser->sub_agent_id = $userType['sub_agent_id'];
        } elseif (isset($userType['agent_id'])) {
            $internetUser->package_id = $package_id;
            $internetUser->zone_id = $userType['client_id'];
            $internetUser->agent_id = $userType['agent_id'];
        } else {
            $internetUser->package_id = $package_id;
            $internetUser->zone_id = $userType['client_id'];
        }

        if (isset($userType['sub_agent_id'])) {
            $internetUser->added_by = $userType['sub_agent_id'];
        } elseif (isset($userType['agent_id'])) {
            $internetUser->added_by = $userType['agent_id'];
        } else {
            $internetUser->added_by = $userType['client_id'];
        }

        $internetUser->package_type = 'wifi';
        $internetUser->latitude = $editor_profile->latitude ?? "0.00000";
        $internetUser->longitude = $editor_profile->longitude ?? "0.00000";
        $internetUser->password = $newUser['password'];
        $internetUser->user_type = 'wifi';
        $internetUser->billing_address = $editor_profile->address;
        // $internetUser->broadband_pop_id = $row['pop'] ?? null;
        // $internetUser->connection_media = $row['connection_media'] ?? null;
        // $internetUser->installation_charge = $row['ins_cost'] ?? null;
        // $internetUser->connection_status = 'pending';
        $internetUser->save();
    }

    private function createWifiDbUserInfo(array $row, $userType)
    {
        $editor_profile = UserProfile::where('uid',$row['client_id'])->first();

        $user_info = new WifiDbUserInfo();
        $user_info->username = $row['mobile_number'];
        $user_info->firstname = $row['full_name'];
        $user_info->email = 'noemail@shadhinwifi.com';

        if (isset($userType['sub_agent_id'])) {
            $ip_address = CorporateClient::where('uid',$userType['client_id'])->value('mikrotik_ip');
            $user_info->ipaddress = $ip_address;
        } elseif (isset($userType['agent_id'])) {
            $ip_address = CorporateClient::where('uid',$userType['client_id'])->value('mikrotik_ip');
            $user_info->ipaddress = $ip_address;
        } else {
            $ip_address = CorporateClient::where('uid',$userType['client_id'])->value('mikrotik_ip');
            $user_info->ipaddress = $ip_address;
        }

        $user_info->mobilephone = $row['mobile_number'];
        $user_info->address = $editor_profile->address;

        if (isset($userType['sub_agent_id'])) {
            $user_info->branch = $userType['client_id'];
        } elseif (isset($userType['agent_id'])) {
            $user_info->branch = $userType['client_id'];
        } else {
            $user_info->branch = $userType['client_id'];
        }

        $user_info->thana = GeoUpazila::where('id',$editor_profile->upazila_id)->value('en_name');
        $user_info->district = GeoDistrict::where('id',$editor_profile->district_id)->value('en_name');
        $user_info->city = GeoDistrict::where('id',$editor_profile->district_id)->value('en_name');
        $user_info->state = GeoDivision::where('id',$editor_profile->division_id)->value('en_name');
        $user_info->country = 'Bangladesh';
        $user_info->country = '1340';

        if (isset($userType['sub_agent_id'])) {
            $user_info->client_id = $userType['client_id'];
            $user_info->agent_id = $userType['agent_id'];
            $user_info->sub_agent_id = $userType['sub_agent_id'];
        } elseif (isset($userType['agent_id'])) {
            $user_info->client_id = $userType['client_id'];
            $user_info->agent_id = $userType['agent_id'];
        } else {
            $user_info->client_id = $userType['client_id'];
        }

        $user_info->creationdate = Carbon::now();
        $user_info->creationby = $row['client_id'];
        $user_info->updatedate = Carbon::now();
        $user_info->updateby = $row['client_id'];
        $user_info->save();
    }

    private function createWifiDbRadCheck(array $row, $newUser, $userType){
        $radcheck = new WifiDbRadCheck();
        $radcheck->username = $row['mobile_number'];
        $radcheck->attribute = "Cleartext-Password";
        $radcheck->op = ":=";
        $radcheck->value = $newUser['password'];

        if (isset($userType['sub_agent_id'])) {
            $radcheck->branch = $userType['client_id'];
        } elseif (isset($userType['agent_id'])) {
            $radcheck->branch = $userType['client_id'];
        } else {
            $radcheck->branch = $userType['client_id'];
        }

        $radcheck->updatetime = Carbon::now();
        $radcheck->save();
    }

    private function createWifiDbRadUserGroup(array $row, $userType){
        $radUserGroup = new WifiDbRadUserGroup();
        $radUserGroup->username = $row['mobile_number'];

        if (isset($userType['sub_agent_id'])) {
            $radUserGroup->groupname = CorporateClient::where('uid',$userType['client_id'])->value('hotspot_profile');
        } elseif (isset($userType['agent_id'])) {
            $radUserGroup->groupname = CorporateClient::where('uid',$userType['client_id'])->value('hotspot_profile');
        } else {
            $radUserGroup->groupname = CorporateClient::where('uid',$userType['client_id'])->value('hotspot_profile');
        }

        $radUserGroup->priority = "0";
        $radUserGroup->save();
    }

    private function createWifiDbRadReply(array $row){
        $radUserGroup = new WifiDbRadReply();
        $radUserGroup->username = $row['mobile_number'];
        $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
        $radUserGroup->op = ":=";
        $radUserGroup->value = Carbon::now()->addMinutes(30);
        $radUserGroup->save();
    }
}
