<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\TransPop;
use App\Models\TransLatLong;
use App\Models\TransCableDetail;
use App\Models\TransCoreJoinInfo;
use App\Models\TransTjBox;
use App\Models\TransTjBoxSplitters;
use App\Models\TransWorkerInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransDistributionTjBoxImport implements ToModel, WithHeadingRow
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
        $this->importOutCables($row, $tj->id);
        $this->importCoreJoinInfo($row, $tj->id);
        $this->createWorkerInfo($row, $tj->id);
        $this->importSplitterInfo($row, $tj->id);
    }

    private function validateRow(array $row)
    {
        $validator = Validator::make($row, [
            'tj_box_id' => 'required',
            'parent_pop_id' => 'required',

            'latitude' => 'required',
            'longitude' => 'required',

            'in_cables_fiber_id.*' => 'required',
            'in_cables_fiber_core.*' => 'required',
            'in_cables_start_meter.*' => 'required',
            'in_cables_end_meter.*' => 'required',
            'in_cables_length.*' => 'required',

            'out_cables_fiber_id.*' => 'required',
            'out_cables_fiber_core.*' => 'required',
            'out_cables_start_meter.*' => 'required',
            'out_cables_end_meter.*' => 'required',
            'out_cables_length.*' => 'required',

            'core_joins_in_fiber_id.*' => 'required',
            'core_joins_out_fiber_id.*' => 'required',
            'core_joins_color.*' => 'required',
            'core_joins_db_signal.*' => 'required',

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
        $tjBox->tj_box_type = 'distribution_tj';
        $tjBox->parent_tj_box_id = $parentTjBoxId;
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
        $address->module_type = 'distribution_tj';
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
        $workerInfo->module_type = 'distribution_tj';
        $workerInfo->added_by_name = $row['worker_name'];
        $workerInfo->mobile_number = $row['worker_mobile'];
        $workerInfo->work_type = $row['work_type'];
        $workerInfo->save();
    }

    private function importInCables(array $row, $tjId)
    {
        $inCableCount = 0;
        while (isset($row["in_cables_fiber_id$inCableCount"])) {
            TransCableDetail::create([
                'trans_id' => $tjId,
                'module_type' => 'distribution_tj',
                'cable_type' => 'in_cable',
                'fiber_code' => $row["in_cables_fiber_id$inCableCount"],
                'fiber_core' => $row["in_cables_fiber_core$inCableCount"],
                'start_fiber_meter' => $row["in_cables_start_meter$inCableCount"],
                'end_fiber_meter' => $row["in_cables_end_meter$inCableCount"],
                'fiber_length' => $row["in_cables_length$inCableCount"],
            ]);
            $inCableCount++;
        }
    }

    private function importOutCables(array $row, $tjId)
    {
        $outCableCount = 0;
        while (isset($row["out_cables_fiber_id$outCableCount"])) {
            TransCableDetail::create([
                'trans_id' => $tjId,
                'module_type' => 'distribution_tj',
                'cable_type' => 'out_cable',
                'fiber_code' => $row["out_cables_fiber_id$outCableCount"],
                'fiber_core' => $row["out_cables_fiber_core$outCableCount"],
                'start_fiber_meter' => $row["out_cables_start_meter$outCableCount"],
                'end_fiber_meter' => $row["out_cables_end_meter$outCableCount"],
                'fiber_length' => $row["out_cables_length$outCableCount"],
            ]);
            $outCableCount++;
        }
    }

    private function importCoreJoinInfo(array $row, $tjId)
    {
        $joinInfoCount = 0;
        while (isset($row["core_joins_in_fiber_id$joinInfoCount"])) {
            TransCoreJoinInfo::create([
                'trans_id' => $tjId,
                'module_type' => 'distribution_tj',
                'in_fiber_id' => TransCableDetail::where('fiber_code',$row["core_joins_in_fiber_id$joinInfoCount"])->where('trans_id',$tjId)->where('cable_type','in_cable')->value('id') ?? null,
                'out_fiber_id' => TransCableDetail::where('fiber_code',$row["core_joins_out_fiber_id$joinInfoCount"])->where('trans_id',$tjId)->where('cable_type','out_cable')->value('id') ?? null,
                'joining_core_color' => $row["core_joins_color$joinInfoCount"],
                'db_signal' => $row["core_joins_db_signal$joinInfoCount"],
            ]);
            $joinInfoCount++;
        }
    }

    private function importSplitterInfo(array $row, $tjId)
    {
        $splitterCount = 0;
        while (isset($row["splitter_info_id$splitterCount"])) {
            TransTjBoxSplitters::create([
                'trans_id' => $tjId,
                'module_type' => 'distribution_tj',
                'splitter_code' => $row["splitter_info_id$splitterCount"],
                'splitter_brand_name' => $row["splitter_info_model$splitterCount"],
                'splitter_type' => $row["splitter_info_type$splitterCount"],
                'joining_core_color' => $row["splitter_info_color$splitterCount"],
            ]);
            $splitterCount++;
        }
    }
}
