<?php
namespace App\Imports;

use App\Classes\RouterOsApi;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\BroadbandDbZone;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ISPClientBroadbandUserBulkRegisterImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Validate the row data
        $this->validateRow($row);

        // check user type
        $userType = $this->checkClient($row);

        // Check if client exists
        $branchInfo = $this->fetchBranchInfo($userType);

        // Set MikroTik connection
        $this->connectMikrotik($branchInfo);

        // Check if the mobile number is already in use
        $this->checkUserExistence($row['mobile_number']);

        // Create new user and related profiles
        $newUser = $this->createNewUser($row);
        $this->createUserProfile($row, $newUser);
        $this->createInternetUser($row, $newUser, $userType);
        $this->createSubscriberInfo($row, $newUser, $userType);
    }

    private function validateRow(array $row)
    {
        $validator = Validator::make($row, [
            'full_name' => 'required',
            'email' => 'required',
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

    private function fetchBranchInfo(array $userType)
    {
        if (isset($userType['sub_agent_id'])) {
            $editor_id = $userType['client_id'];
        } elseif (isset($userType['agent_id'])) {
            $editor_id = $userType['client_id'];
        } else {
            $editor_id = $userType['client_id'];
        }
        return BroadbandDbZone::where('zone_name', '=', $editor_id)->get(['mikrotik_ip','username','password']);
    }

    private function connectMikrotik($branchInfo)
    {
        foreach ($branchInfo as $branch) {

            $ipAddr = $branch['mikrotik_ip'];
            $mkUser = $branch['username'];
            $mkPass = $branch['password'];
        }
        // $API = new RouterOsApi();
        // if (!$API->connect($ipAddr, $mkUser, $mkPass)) {
        //     throw new \Exception(json_encode('Mikrotik data not found!'));
        // }
    }

    private function createNewUser(array $row)
    {
        $userData = (new \App\Classes\CustomHelpers)->create_new_user($row['mobile_number'], 'user', 'broadband');
        return [
            'uid' => $userData['user']['id'],
            'password' => $userData['password'],
        ];
    }

    private function createUserProfile(array $row, $newUser)
    {
        $profile = new UserProfile();
        $profile->uid = $newUser['uid'];
        $profile->full_name = $row['full_name'];
        $profile->mobile_number = $row['mobile_number'];
        $profile->whatsapp_number = $row['mobile_number'];
        $profile->email = $row['email'] ?? null;
        $profile->profession = $row['profession'] ?? null;
        $profile->nid = $row['nid'] ?? null;
        $profile->gender = $row['gender'] ?? null;
        $profile->division_id = $row['division'];
        $profile->district_id = $row['district'];
        $profile->upazila_id = $row['upazila'];
        $profile->union_id = $row['union'];
        $profile->village_id = $row['village'] ?? null;
        $profile->house_no = $row['house'] ?? null;
        $profile->address = $row['address'] ?? null;
        $profile->address_direction = $row['address_direction'] ?? null;
        $profile->latitude = $row['latitude'] ?? null;
        $profile->longitude = $row['longitude'] ?? null;
        $profile->device_info = json_encode(["brand" => "website"]);
        $profile->save();
    }

    private function createInternetUser(array $row, $newUser, $userType)
    {
        $internetUser = new InternetUsers();
        $internetUser->uid = $newUser['uid'];
        if (isset($userType['sub_agent_id'])) {
            $internetUser->zone_id = $userType['client_id'];
            $internetUser->agent_id = $userType['agent_id'];
            $internetUser->sub_agent_id = $userType['sub_agent_id'];
        } elseif (isset($userType['agent_id'])) {
            $internetUser->zone_id = $userType['client_id'];
            $internetUser->agent_id = $userType['agent_id'];
        } else {
            $internetUser->zone_id = $userType['client_id'];
        }
        $internetUser->added_by = $row['client_id'];
        $internetUser->package_id = $row['package'];
        $internetUser->package_type = 'broadband';
        $internetUser->latitude = $row['latitude'] ?? null;
        $internetUser->longitude = $row['longitude'] ?? null;
        $internetUser->password = $newUser['password'];
        $internetUser->user_type = $row['user_type'] ?? null;
        $internetUser->billing_address = $row['address'] ?? null;
        $internetUser->broadband_pop_id = $row['pop'] ?? null;
        $internetUser->connection_media = $row['connection_media'] ?? null;
        $internetUser->installation_charge = $row['ins_cost'] ?? null;
        $internetUser->connection_status = 'pending';
        $internetUser->save();
    }

    private function createSubscriberInfo(array $row, $newUser, $userType)
    {
        $packageId = InternetPackageCorporate::where('id',$row['package'])->value('package_name');

        $subscriber = new BroadbandDbSubscriberInfo();
        $subscriber->serial = $newUser['uid'];
        $subscriber->popId = $row['pop'] ?? null;
        $subscriber->date = date('Y-m-d');
        if (isset($userType['sub_agent_id'])) {
            $subscriber->zone_name = $userType['client_id'];
        } elseif (isset($userType['agent_id'])) {
            $subscriber->zone_name = $userType['client_id'];
        } else {
            $subscriber->zone_name = $userType['client_id'];
        }
        $subscriber->numAsId = $row['mobile_number'];
        $subscriber->packageId = $packageId;
        $subscriber->customerName = $row['full_name'] ?? null;
        $subscriber->gender = $row['gender'] ?? null;
        $subscriber->instcost = $row['ins_cost'] ?? null;
        $subscriber->home = $row['house'] ?? null;
        $subscriber->village = $row['village'] ?? null;
        $subscriber->police_station = $row['upazila_name'] ?? null;
        $subscriber->district = $row['district_name'] ?? null;
        $subscriber->division = $row['division_name'] ?? null;
        $subscriber->billingAddress = $row['address'] ?? null;
        $subscriber->nid = $row['nid'] ?? null;
        $subscriber->numOne = $row['mobile_number'] ?? null;
        $subscriber->email = $row['email'] ?? null;
        $subscriber->UserType = $row['user_type'] ?? null;
        $subscriber->connectionMedia = $row['connection_media'] ?? null;
        $subscriber->acativation_date = Carbon::now();
        $subscriber->save();
    }
}
