<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\TransPop;
use App\Models\TransLatLong;
use App\Models\TransCableDetail;
use App\Models\TransCoreJoinInfo;
use App\Models\TransLoop;
use App\Models\TransTjBox;
use App\Models\TransWorkerInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransDistributionLoopImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Validate the row data
        $this->validateRow($row);

        // Check for the existence of necessary IDs
        $this->checkIds($row);

        // Avoid duplicates
        $this->checkDuplicateLoop($row['loop_id']);

        // Create the TransPop entry
        $loop = $this->createTransLoop($row);

        // Create related entries
        $this->createLatLong($row, $loop->id);
        $this->importCables($row, $loop->id);
        $this->createWorkerInfo($row, $loop->id);
    }

    private function validateRow(array $row)
    {
        $validator = Validator::make($row, [
            'loop_id' => 'required',
            'parent_pop_id' => 'required',
            // 'parent_tj_box_id' => 'required',

            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',

            'fiber_id' => 'required',
            'fiber_core' => 'required',
            'looped_fiber_start_meter' => 'required',
            'looped_fiber_end_meter' => 'required',
            'looped_fiber_length' => 'required',

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

    private function checkDuplicateLoop($loopId)
    {
        if (TransLoop::where('loop_code', $loopId)->exists()) {
            throw new \Exception(json_encode('This loop id is already exists.'));
        }
    }

    private function createTransLoop(array $row)
    {
        $parentPopId = TransPop::where('pop_code', $row['parent_pop_id'])->value('id');
        $parentTjBoxId = TransTjBox::where('tj_box_code',$row['parent_tj_box_id'])->value('id');

        $loop = new TransLoop();
        $loop->pop_id = $parentPopId;
        $loop->tj_box_id = $parentTjBoxId;
        $loop->loop_code = $row['loop_id'];
        $loop->loop_type = 'distribution_loop';
        $loop->latitude = $row['latitude'];
        $loop->longitude = $row['longitude'];
        $loop->address_direction = $row['address_direction'];
        $loop->added_by_uid = $row['added_by_uid'];
        $loop->updated_by_uid = $row['updated_by_uid'];
        $loop->comments = $row['comments'];
        $loop->status = 'Active';
        $loop->save();

        return $loop;
    }

    private function createLatLong(array $row, $loopId)
    {
        $parentPopId = TransPop::where('pop_code', $row['parent_pop_id'])->value('id');

        $address = new TransLatLong();
        $address->trans_id = $loopId;
        $address->module_type = 'distribution_loop';
        $address->division_id = TransPop::where('id', $parentPopId)->value('division_id');
        $address->district_id = TransPop::where('id', $parentPopId)->value('district_id');
        $address->upazila_id = TransPop::where('id', $parentPopId)->value('upazila_id');
        $address->union_id = TransPop::where('id', $parentPopId)->value('union_id');
        $address->latitude = $row['latitude'];
        $address->longitude = $row['longitude'];
        $address->status = 'Active';
        $address->save();
    }

    private function createWorkerInfo(array $row, $loopId)
    {
        $workerInfo = new TransWorkerInfo();
        $workerInfo->trans_id = $loopId;
        $workerInfo->module_type = 'distribution_loop';
        $workerInfo->added_by_name = $row['worker_name'];
        $workerInfo->mobile_number = $row['worker_mobile'];
        $workerInfo->work_type = $row['work_type'];
        $workerInfo->save();
    }

    private function importCables(array $row, $loopId)
    {
        $cableModel = new TransCableDetail();
        $cableModel->trans_id = $loopId;
        $cableModel->module_type = 'distribution_loop';
        $cableModel->cable_type = 'distribution_loop_cable';
        $cableModel->fiber_code = $row['fiber_id'];
        $cableModel->fiber_core = $row['fiber_core'];
        $cableModel->start_fiber_meter = $row['looped_fiber_start_meter'];
        $cableModel->end_fiber_meter = $row['looped_fiber_end_meter'];
        $cableModel->fiber_length = $row['looped_fiber_length'];
        $cableModel->save();
    }
}
