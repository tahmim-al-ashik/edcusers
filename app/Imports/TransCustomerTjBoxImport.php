<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\TransPop;
use App\Models\TransLatLong;
use App\Models\TransCableDetail;
use App\Models\TransCoreJoinInfo;
use App\Models\TransTjBox;
use App\Models\TransWorkerInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransCustomerTjBoxImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Validate the row data
        $this->validateRow($row);

        // Check for the existence of necessary IDs
        $this->checkIds($row);

        // Avoid duplicates
        $this->checkDuplicateTjBox($row['tj_box_id']);

        // Create the TransPop entry
        $tj = $this->createTransTj($row);

        // Create related entries
        $this->createLatLong($row, $tj->id);
        $this->importInCables($row, $tj->id);
        $this->importCoreJoinInfo($row, $tj->id);
        $this->createWorkerInfo($row, $tj->id);
    }

    private function validateRow(array $row)
    {
        $validator = Validator::make($row, [
            'tj_box_id' => 'required',
            'parent_pop_id' => 'required',

            'customer_name' => 'required',
            'customer_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],

            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',

            'in_fiber_id' => 'required',
            'in_fiber_core' => 'required',
            'in_fiber_start_meter' => 'required',
            'in_fiber_end_meter' => 'required',
            'in_fiber_length' => 'required',

            'core_join_in_fiber_id' => 'required',
            'core_join_joining_core_color' => 'required',
            'core_join_db_signal' => 'required',

            'worker_name' => 'required',
            'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'work_type' => 'required'
        ]);

        if ($validator->fails()) {
            throw new \Exception(json_encode($validator->errors()));
        }
    }

    private function checkIds(array $row)
    {
        if (!TransPop::where('pop_code', $row['parent_pop_id'])->exists()) {
            throw new \Exception(json_encode('No parent pop found according to your code!'));
        }
        if($row['parent_tj_box_id']){
            if (!TransTjBox::where('tj_box_code', $row['parent_tj_box_id'])->exists()) {
                throw new \Exception(json_encode('No parent Tj Box found according to your code!'));
            }
        }
    }

    private function checkDuplicateTjBox($tjId)
    {
        if (TransTjBox::where('tj_box_code', $tjId)->exists()) {
            throw new \Exception(json_encode('This tj id is already exists.'));
        }
    }

    private function createTransTj(array $row)
    {
        $parentPopId = TransPop::where('pop_code', $row['parent_pop_id'])->value('id');
        $parentTjBoxId = TransTjBox::where('tj_box_code',$row['parent_tj_box_id'])->value('id');

        $tjBox = new TransTjBox();
        $tjBox->pop_id = $parentPopId;
        $tjBox->tj_box_code = $row['tj_box_id'];
        $tjBox->tj_box_type = 'customer_tj';
        $tjBox->parent_tj_box_id = $parentTjBoxId;
        $tjBox->customer_name = $row['customer_name'];
        $tjBox->customer_mobile = $row['customer_mobile'];
        $tjBox->latitude = $row['latitude'];
        $tjBox->longitude = $row['longitude'];
        $tjBox->address_direction = $row['address_direction'];
        $tjBox->added_by_uid = $row['added_by_uid'];
        $tjBox->updated_by_uid = $row['updated_by_uid'];
        $tjBox->comments = $row['comments'];
        $tjBox->status = 'Active';
        $tjBox->save();

        return $tjBox;
    }

    private function createLatLong(array $row, $tjId)
    {
        $parentPopId = TransPop::where('pop_code', $row['parent_pop_id'])->value('id');

        $address = new TransLatLong();
        $address->trans_id = $tjId;
        $address->module_type = 'customer_tj';
        $address->division_id = TransPop::where('id', $parentPopId)->value('division_id');
        $address->district_id = TransPop::where('id', $parentPopId)->value('district_id');
        $address->upazila_id = TransPop::where('id', $parentPopId)->value('upazila_id');
        $address->union_id = TransPop::where('id', $parentPopId)->value('union_id');
        $address->latitude = $row['latitude'];
        $address->longitude = $row['longitude'];
        $address->status = 'Active';
        $address->save();
    }

    private function createWorkerInfo(array $row, $tjId)
    {
        $workerInfo = new TransWorkerInfo();
        $workerInfo->trans_id = $tjId;
        $workerInfo->module_type = 'customer_tj';
        $workerInfo->added_by_name = $row['worker_name'];
        $workerInfo->mobile_number = $row['worker_mobile'];
        $workerInfo->work_type = $row['work_type'];
        $workerInfo->save();
    }

    private function importInCables(array $row, $tjId)
    {
        $inCableModel = new TransCableDetail();
        $inCableModel->trans_id = $tjId;
        $inCableModel->module_type = 'customer_tj';
        $inCableModel->cable_type = 'in_cable';
        $inCableModel->fiber_code = $row['in_fiber_id'];
        $inCableModel->fiber_core = $row['in_fiber_core'];
        $inCableModel->start_fiber_meter = $row['in_fiber_start_meter'];
        $inCableModel->end_fiber_meter = $row['in_fiber_end_meter'];
        $inCableModel->fiber_length = $row['in_fiber_length'];
        $inCableModel->save();
    }

    private function importCoreJoinInfo(array $row, $tjId)
    {
        $coreJoin = new TransCoreJoinInfo();
        $coreJoin->trans_id = $tjId;
        $coreJoin->module_type = 'customer_tj';
        $coreJoin->in_fiber_id = TransCableDetail::where('fiber_code',$row['core_join_in_fiber_id'])->where('trans_id',$tjId)->where('cable_type','in_cable')->value('id') ?? null;
        $coreJoin->joining_core_color = $row['core_join_joining_core_color'];
        $coreJoin->db_signal = $row['core_join_db_signal'];
        $coreJoin->save();
    }
}
