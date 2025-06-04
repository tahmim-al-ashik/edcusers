<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\TransPop;
use App\Models\TransLatLong;
use App\Models\TransCableDetail;
use App\Models\TransCoreJoinInfo;
use App\Models\TransPopDeviceInfo;
use App\Models\TransWorkerInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
class TransNTTNPopImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Define validation rules
        $validator = Validator::make($row, [
            'pop_id' => 'required',
            'pop_type' => 'required',
            'provider_id' => 'required',

            'division' => 'required',
            'district' => 'required',
            'union' => 'required',
            'upazila' => 'required',
            'village' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',

            'join_core_color' => 'required',
            'join_core_db_signal' => 'required',
            'join_fiber_id_our' => 'required',
            'join_fiber_id_provider' => 'required',

            'our_fiber_id' => 'required',
            'our_fiber_core' => 'required',
            'our_cable_start_meter' => 'required',
            'our_cable_end_meter' => 'required',
            'our_cable_length' => 'required',

            'provider_fiber_id' => 'required',
            'provider_fiber_core' => 'required',
            'provider_core_capacity' => 'required',

            'sfp_type' => 'required',
            'sfp_model' => 'required',
            'sfp_capacity' => 'required',

            'worker_name' => 'required',
            'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'work_type' => 'required',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // You can either throw an exception to stop the import or log the errors
            throw new \Exception(json_encode($validator->errors()));

            // Or log the errors to keep track
            // Log::warning('Validation failed for row: ', $validator->errors()->toArray());

            return null; // Skip the row
        }

        // Avoid duplicates
        $checkPopCode = TransPop::where('pop_code', $row['pop_id'])->exists();
        if ($checkPopCode) {
            // You can either throw an exception to stop the import or log the errors
            throw new \Exception(json_encode('This pop id is already exists.'));
            return null;
        }

        // Create new TransPop entry
        $pop = new TransPop();
        $pop->company_id = $row['provider_id'];
        $pop->pop_code = $row['pop_id'];
        $pop->pop_type = 'nttn';
        $pop->pop_main_type = $row['pop_type'];
        $pop->division_id = $row['division'];
        $pop->district_id = $row['district'];
        $pop->upazila_id = $row['upazila'];
        $pop->union_id = $row['union'];
        $pop->village_name = $row['village'];
        $pop->address_direction = $row['address_direction'];
        $pop->latitude = $row['latitude'];
        $pop->longitude = $row['longitude'];
        $pop->added_by_uid = $row['added_by_uid'];
        $pop->updated_by_uid = $row['updated_by_uid'];
        $pop->comments = $row['comments'];
        $pop->status = 'Active';
        $pop->save();

        // Catching the pop id
        $popId = $pop->id;

        $address = new TransLatLong();
        $address->trans_id = $popId;
        $address->module_type = 'nttn';
        $address->division_id = $row['division'];
        $address->district_id = $row['district'];
        $address->upazila_id = $row['upazila'];
        $address->union_id = $row['union'];
        $address->latitude = $row['latitude'];
        $address->longitude = $row['longitude'];
        $address->status = 'Active';
        $address->save();

        $ourCable = new TransCableDetail();
        $ourCable->trans_id = $popId;
        $ourCable->module_type = 'nttn';
        $ourCable->cable_type = 'our_cable';
        $ourCable->fiber_code = $row['our_fiber_id'];
        $ourCable->fiber_core = $row['our_fiber_core'];
        $ourCable->start_fiber_meter = $row['our_cable_start_meter'];
        $ourCable->end_fiber_meter = $row['our_cable_end_meter'];
        $ourCable->fiber_length = $row['our_cable_length'];
        $ourCable->save();

        $providerCable = new TransCableDetail();
        $providerCable->trans_id = $popId;
        $providerCable->module_type = 'nttn';
        $providerCable->cable_type = 'provider_cable';
        $providerCable->core_capacity = $row['provider_core_capacity'];
        $providerCable->fiber_code = $row['provider_fiber_id'];
        $providerCable->fiber_core = $row['provider_fiber_core'];
        $providerCable->save();

        $joinInfo = new TransCoreJoinInfo();
        $joinInfo->trans_id = $popId;
        $joinInfo->module_type = 'nttn';
        $joinInfo->in_fiber_id = TransCableDetail::where('fiber_code', $row['provider_fiber_id'])->where('module_type','nttn')->value('id');
        $joinInfo->out_fiber_id = TransCableDetail::where('fiber_code', $row['our_fiber_id'])->where('module_type','nttn')->value('id');
        $joinInfo->joining_core_color = $row['join_core_color'];
        $joinInfo->db_signal = $row['join_core_db_signal'];
        $joinInfo->save();

        $popDeviceInfo = new TransPopDeviceInfo();
        $popDeviceInfo->trans_id = $popId;
        $popDeviceInfo->module_type = 'nttn';
        $popDeviceInfo->sfp_brand_name = $row['sfp_model'];
        $popDeviceInfo->sfp_type = $row['sfp_type'];
        $popDeviceInfo->sfp_capacity = $row['sfp_capacity'];
        $popDeviceInfo->save();

        $workerInfo = new TransWorkerInfo();
        $workerInfo->trans_id = $popId;
        $workerInfo->module_type = 'nttn';
        $workerInfo->added_by_name = $row['worker_name'];
        $workerInfo->mobile_number = $row['worker_mobile'];
        $workerInfo->work_type = $row['work_type'];
        $workerInfo->save();
    }
}
?>
