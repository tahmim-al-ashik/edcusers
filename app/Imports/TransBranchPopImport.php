<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\TransPop;
use App\Models\TransLatLong;
use App\Models\TransCableDetail;
use App\Models\TransCompany;
use App\Models\TransPopDeviceInfo;
use App\Models\TransPopOutputDeviceInfo;
use App\Models\TransWorkerInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransBranchPopImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Validate the row data
        $this->validateRow($row);

        // Check for the existence of necessary IDs
        $this->checkIds($row);

        // Avoid duplicates
        $this->checkDuplicatePopId($row['pop_id']);

        // Create the TransPop entry
        $pop = $this->createTransPop($row);

        // Create related entries
        $this->createLatLong($row, $pop->id);
        $this->importInCables($row, $pop->id);
        $this->importOutCables($row, $pop->id);
        $this->createPopDeviceInfo($row, $pop->id);
        $this->importOutputDevices($row, $pop->id);
        $this->createWorkerInfo($row, $pop->id);
    }

    private function validateRow(array $row)
    {
        $validator = Validator::make($row, [
            'pop_id' => 'required',
            'pop_type' => 'required',
            'provider_id' => 'required',
            'nttn_pop_id' => 'required',

            'division' => 'required',
            'district' => 'required',
            'union' => 'required',
            'upazila' => 'required',
            'village' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',
            'added_by_uid' => 'required',
            'updated_by_uid' => 'required',

            'in_cables_fiber_id.*' => 'required',
            'in_cables_fiber_core.*' => 'required',
            'in_cables_start_meter.*' => 'required',
            'in_cables_end_meter.*' => 'required',
            'in_cables_length.*' => 'required',
            'in_cables_joining_core_color.*' => 'required',
            'in_cables_db_signal.*' => 'required',

            'out_cables_fiber_id.*' => 'required',
            'out_cables_fiber_core.*' => 'required',
            'out_cables_start_meter.*' => 'required',
            'out_cables_end_meter.*' => 'required',
            'out_cables_length.*' => 'required',
            'out_cables_connected_port_number.*' => 'required',

            'input_sfp_mc_model' => 'required',
            'input_device_port_type' => 'required',
            'incoming_fiber_connected_port_number' => 'required',

            'output_devices_device_type.*' => 'required',
            'output_devices_port_number.*' => 'required',
            'output_devices_brand_name.*' => 'required',
            'output_devices_connection_capacity.*' => 'required',
            'output_devices_serial_no.*' => 'required',
            'output_devices_device_id.*' => 'required',
            'output_devices_power_consumption.*' => 'required',

            'worker_name' => 'required',
            'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'work_type' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception(json_encode($validator->errors()));
        }
    }

    private function checkIds(array $row)
    {
        if (!TransCompany::where('id', $row['provider_id'])->exists()) {
            throw new \Exception(json_encode('No Company found according to your provider Id!'));
        }

        if (!TransPop::where('pop_code', $row['nttn_pop_id'])->exists()) {
            throw new \Exception(json_encode('No NTTN Pop found according to your NTTN Pop Code!'));
        }

        if ($row['backup_nttn_pop_id'] && !TransPop::where('pop_code', $row['backup_nttn_pop_id'])->exists()) {
            throw new \Exception(json_encode('No Backup NTTN Pop found according to your NTTN Pop Code!'));
        }
    }

    private function checkDuplicatePopId($popId)
    {
        if (TransPop::where('pop_code', $popId)->exists()) {
            throw new \Exception(json_encode('This pop id already exists.'));
        }
    }

    private function createTransPop(array $row)
    {
        $pop = new TransPop();
        $pop->company_id = $row['provider_id'];
        $pop->pop_code = $row['pop_id'];
        $pop->pop_type = 'branch';
        $pop->pop_main_type = $row['pop_type'];
        $pop->nttn_pop_id = TransPop::where('pop_code', $row['nttn_pop_id'])->value('id');
        $pop->backup_nttn_pop_id = TransPop::where('pop_code', $row['backup_nttn_pop_id'])->value('id');
        $pop->scr_id = $row['scr_id'];
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
        return $pop;
    }

    private function createLatLong(array $row, $popId)
    {
        $address = new TransLatLong();
        $address->trans_id = $popId;
        $address->module_type = 'branch';
        $address->division_id = $row['division'];
        $address->district_id = $row['district'];
        $address->upazila_id = $row['upazila'];
        $address->union_id = $row['union'];
        $address->latitude = $row['latitude'];
        $address->longitude = $row['longitude'];
        $address->status = 'Active';
        $address->save();
    }

    private function createPopDeviceInfo(array $row, $popId)
    {
        $popDeviceInfo = new TransPopDeviceInfo();
        $popDeviceInfo->trans_id = $popId;
        $popDeviceInfo->module_type = 'branch';
        $popDeviceInfo->sfp_brand_name = $row['input_sfp_mc_model'];
        $popDeviceInfo->sfp_type = $row['input_sfp_type'];
        $popDeviceInfo->sfp_capacity = $row['input_sfp_capacity'];
        $popDeviceInfo->input_device_port_type = $row['input_device_port_type'];
        $popDeviceInfo->incoming_fiber_connected_port_number = $row['incoming_fiber_connected_port_number'];
        $popDeviceInfo->mk_brand_name = $row['mikrotik_model'];
        $popDeviceInfo->mk_capacity = $row['mikrotik_capacity'];
        $popDeviceInfo->mk_port_number = $row['mikrotik_port_number'];
        $popDeviceInfo->mk_serial_no = $row['mikrotik_serial_no'];
        $popDeviceInfo->mk_device_id = $row['mikrotik_device_id'];
        $popDeviceInfo->mk_power_consumption = $row['mikrotik_power_consumption'];
        $popDeviceInfo->mk_mac_address = $row['mikrotik_mac'];
        $popDeviceInfo->rak_brand_name = $row['rak_model'];
        $popDeviceInfo->rak_capacity = $row['rak_capacity'];
        $popDeviceInfo->save();
    }

    private function createWorkerInfo(array $row, $popId)
    {
        $workerInfo = new TransWorkerInfo();
        $workerInfo->trans_id = $popId;
        $workerInfo->module_type = 'branch';
        $workerInfo->added_by_name = $row['worker_name'];
        $workerInfo->mobile_number = $row['worker_mobile'];
        $workerInfo->work_type = $row['work_type'];
        $workerInfo->save();
    }

    private function importInCables(array $row, $popId)
    {
        $inCableCount = 0;
        while (isset($row["in_cables_fiber_id$inCableCount"])) {
            TransCableDetail::create([
                'trans_id' => $popId,
                'module_type' => 'branch',
                'cable_type' => 'in_cable',
                'fiber_code' => $row["in_cables_fiber_id$inCableCount"],
                'fiber_core' => $row["in_cables_fiber_core$inCableCount"],
                'start_fiber_meter' => $row["in_cables_start_meter$inCableCount"],
                'end_fiber_meter' => $row["in_cables_end_meter$inCableCount"],
                'fiber_length' => $row["in_cables_length$inCableCount"],
                'joining_core_color' => $row["in_cables_joining_core_color$inCableCount"],
                'db_signal' => $row["in_cables_db_signal$inCableCount"],
            ]);
            $inCableCount++;
        }
    }

    private function importOutCables(array $row, $popId)
    {
        $outCableCount = 0;
        while (isset($row["out_cables_fiber_id$outCableCount"])) {
            TransCableDetail::create([
                'trans_id' => $popId,
                'module_type' => 'branch',
                'cable_type' => 'out_cable',
                'fiber_code' => $row["out_cables_fiber_id$outCableCount"],
                'fiber_core' => $row["out_cables_fiber_core$outCableCount"],
                'start_fiber_meter' => $row["out_cables_start_meter$outCableCount"],
                'end_fiber_meter' => $row["out_cables_end_meter$outCableCount"],
                'fiber_length' => $row["out_cables_length$outCableCount"],
                'connected_port_number' => $row["out_cables_connected_port_number$outCableCount"],
            ]);
            $outCableCount++;
        }
    }

    private function importOutputDevices(array $row, $popId)
    {
        $outputDeviceCount = 0;
        while (isset($row["output_devices_device_type$outputDeviceCount"])) {
            TransPopOutputDeviceInfo::create([
                'trans_id' => $popId,
                'module_type' => 'branch',
                'output_device_type' => $row["output_devices_device_type$outputDeviceCount"],
                'output_device_port_type' => $row["output_devices_port_type$outputDeviceCount"],
                'output_device_port_number' => $row["output_devices_port_number$outputDeviceCount"],
                'output_device_brand_name' => $row["output_devices_brand_name$outputDeviceCount"],
                'output_device_connection_capacity' => $row["output_devices_connection_capacity$outputDeviceCount"],
                'output_device_serial_no' => $row["output_devices_serial_no$outputDeviceCount"],
                'output_device_id' => $row["output_devices_device_id$outputDeviceCount"],
                'output_device_power_consumption' => $row["output_devices_power_consumption$outputDeviceCount"],
            ]);
            $outputDeviceCount++;
        }
    }
}
