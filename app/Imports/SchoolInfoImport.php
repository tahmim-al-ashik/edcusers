<?php
namespace App\Imports;

use App\Models\InternetUsers;
use App\Models\School\SchoolProfile;
use App\Models\UserProfile;
use App\Models\School\SchoolLatLong;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
class SchoolInfoImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Define validation rules
        $validator = Validator::make($row, [
            'mobile_number' => 'required|string|unique:users,auth_id',
            'whatsapp_number' => 'string',
            'name' => 'required|string|max:255',
            'connection_code' => 'required|string|max:255',
            'division' => 'required|string|max:10',
            'district' => 'required|string|max:10',
            'upazila' => 'required|string|max:10',
            'union' => 'required|string|max:10',
            'village' => 'required|string|max:20',
            'latitude' => 'required|string|max:20',
            'longitude' => 'required|string|max:20',
            'address_direction' => 'required|string',
            'auth_id' => 'required|numeric',
            'package_id' => 'required|string|max:20',
            'head_teacher_name' => 'required|string|max:200',
            'head_teacher_mobile' => 'required|string|max:20',
            'fiber_id' => 'required|string|max:50',
            'fiber_core' => 'required|string|max:50',
            'db_signal' => 'required|string|max:50',
            'fiber_start_meter' => 'required|string|max:20',
            'fiber_end_meter' => 'required|string|max:20',
            'fiber_length' => 'required|string|max:20',
            'onu_mac' => 'required|string|max:50',
            'router_login_username' => 'required|string|max:50',
            'router_login_password' => 'required|string|max:50',
            'router_login_mac' => 'required|string|max:50',
            'router_remote_management_port' => 'required|string|max:50',
            'gateway' => 'required|string|max:50',
            'subnet_mask' => 'required|string|max:50',
            'dnsv4_primary' => 'required|string|max:50',
            'dnsv4_secondary' => 'required|string|max:50',
            'ipv4_ip' => 'required|string|max:50',
            'ipv6_ip' => 'required|string|max:50',
            'slaac_enabled' => 'required|string|in:yes,no,others',
            'icmp_enabled' => 'required|string|in:yes,no,others',
            'router_model' => 'required|string|max:50',
            'router_serial_number' => 'required|string|max:50',
            'tj_box_quantity' => 'required|string|max:10',
            'fiber_patch_cord_quantity' => 'required|string|max:10',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            throw new \Exception(json_encode($validator->errors()));
            return null;
        }

        $userData = (new \App\Classes\CustomHelpers)->create_new_user($row['mobile_number'], 'user','broadband');
        $uid = $userData['user']['id'];
        $password = $userData['password'];

        // create user profile
        $userProfile = new UserProfile();
        $userProfile->uid = $uid;
        $userProfile->full_name = $row['name'];
        $userProfile->mobile_number = $row['mobile_number'];
        $userProfile->whatsapp_number = $row['whatsapp_number'];
        $userProfile->email = $row['email'];
        $userProfile->division_id = $row['division'];
        $userProfile->district_id = $row['district'];
        $userProfile->upazila_id = $row['upazila'];
        $userProfile->union_id = $row['union'];
        $userProfile->village_id = $row['village'];
        $userProfile->address_direction = $row['address_direction'];
        $userProfile->latitude = $row['latitude'];
        $userProfile->longitude = $row['longitude'];
        $userProfile->device_info = json_encode(["brand"=>"erp"]);
        $userProfile->save();

        // data for internet user table --------------------
        $internetUser = new InternetUsers();
        $internetUser->uid = $uid;
        $internetUser->zone_id = $row['auth_id'];
        $internetUser->added_by = $row['auth_id'];
        $internetUser->package_id = $row['package_id'];
        $internetUser->package_type = 'broadband';
        $internetUser->latitude = $row['latitude'];
        $internetUser->longitude = $row['longitude'];
        $internetUser->password = $password;
        $internetUser->user_type = 'school';
        $internetUser->billing_address = $row['address_direction'];
        $internetUser->broadband_pop_id = $row['pop_id'];
        $internetUser->connection_status = 'pending';
        $internetUser->save();

        // data for school profile table -----------------
        $schoolProfile = new SchoolProfile();
        $schoolProfile->uid = $uid;
        $schoolProfile->manager_id = $row['auth_id'];
        $schoolProfile->lot_id = $row['lot_id'];
        $schoolProfile->institution_type = $row['institution_type'];
        $schoolProfile->connection_code = $row['connection_code'];
        $schoolProfile->edc_book_sl_no = $row['edc_book_sl_no'];
        $schoolProfile->school_name = $row['name'];
        $schoolProfile->package_id = $row['package_id'];
        $schoolProfile->electricity = $row['electricity'];
        $schoolProfile->area_code = $row['area_code'];
        $schoolProfile->dis_code = $row['dis_code'];
        $schoolProfile->emis_code = $row['emis_code'];
        $schoolProfile->head_teacher_name = $row['head_teacher_name'];
        $schoolProfile->head_teacher_mobile = $row['head_teacher_mobile'];
        $schoolProfile->head_teacher_ast_name = $row['assistant_name'];
        $schoolProfile->head_teacher_ast_mobile = $row['assistant_mobile'];
        $schoolProfile->fiber_id = $row['fiber_id'];
        $schoolProfile->fiber_core = $row['fiber_core'];
        $schoolProfile->db_signal = $row['db_signal'];
        $schoolProfile->start_meter = $row['fiber_start_meter'];
        $schoolProfile->end_meter = $row['fiber_end_meter'];
        $schoolProfile->fiber_length = $row['fiber_length'];
        $schoolProfile->onu_mac = $row['onu_mac'];
        $schoolProfile->router_username = $row['router_login_username'];
        $schoolProfile->router_password = $row['router_login_password'];
        $schoolProfile->router_mac = $row['router_login_mac'];
        $schoolProfile->router_remote_magt_port = $row['router_remote_management_port'];
        $schoolProfile->gateway = $row['gateway'];
        $schoolProfile->subnet_mask = $row['subnet_mask'];
        $schoolProfile->dnsv4_primary = $row['dnsv4_primary'];
        $schoolProfile->dnsv4_secondary = $row['dnsv4_secondary'];
        $schoolProfile->ipv4_ip = $row['ipv4_ip'];
        $schoolProfile->ipv6_ip = $row['ipv6_ip'];
        $schoolProfile->snmp_com = $row['snmp_com'];
        $schoolProfile->slaac_enabled = $row['slaac_enabled'];
        $schoolProfile->icmp_enabled = $row['icmp_enabled'];
        $schoolProfile->router_model = $row['router_model'];
        $schoolProfile->router_serial = $row['router_serial_number'];
        $schoolProfile->tj_box_quantity = $row['tj_box_quantity'];
        $schoolProfile->tj_box_remarks = $row['tj_box_remarks'];
        $schoolProfile->fiber_patch_cord_quantity = $row['fiber_patch_cord_quantity'];
        $schoolProfile->fiber_patch_cord_remarks = $row['fiber_patch_cord_remarks'];
        $schoolProfile->status = 'pending';
        $schoolProfile->comments = $row['comments'];
        $schoolProfile->others = $row['others'];
        $schoolProfile->updated_by = $row['auth_id'];
        $schoolProfile->save();
        
        $schoolLatLong = new SchoolLatLong();
        $schoolLatLong->uid = $uid;
        $schoolLatLong->manager_id = $row['auth_id'];
        $schoolLatLong->lot_id = $row['lot_id'];
        $schoolLatLong->institution_type = $row['institution_type'];
        $schoolLatLong->division_id = $row['division'];
        $schoolLatLong->district_id = $row['district'];
        $schoolLatLong->upazila_id = $row['upazila'];
        $schoolLatLong->union_id = $row['union'];
        $schoolLatLong->latitude = $row['latitude'];
        $schoolLatLong->longitude = $row['longitude'];
        $schoolLatLong->status = 'pending';
        $schoolLatLong->save();
    }
}
?>
