<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\TransPop;
use App\Models\TransLatLong;
use App\Models\TransCustomer;
use App\Models\TransTjBox;
use App\Models\TransWorkerInfo;
use Illuminate\Support\Facades\Validator;

class TransCustomerImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {

        // Validate the row data
        $this->validateRow($row);

        // Check for the existence of necessary IDs
        $this->checkIds($row);

        // Avoid duplicates
        $this->checkDuplicateCustomer($row['customer_mobile']);

        // Create the TransPop entry
        $customer = $this->createTransCustomer($row);

        // Create related entries
        $this->createLatLong($row, $customer->id);
        $this->createWorkerInfo($row, $customer->id);
    }

    private function validateRow(array $row)
    {
        $validator = Validator::make($row, [
            'customer_name' => 'required',
            'customer_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'customer_email' => 'required',
            'customer_organization' => 'required',

            'customer_pop_id' => 'required',
            'customer_tj_box_id' => 'required',

            'contact_person_name' => 'required',
            'contact_person_primary_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            // 'contact_person_secondary_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'contact_person_designation' => 'required',
            // 'contact_person_whatsapp_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            // 'contact_person_email' => 'required',

            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',

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
        if (!TransPop::where('pop_code', $row['customer_pop_id'])->exists()) {
            throw new \Exception(json_encode('No parent pop found according to your code ' . $row['customer_pop_id'] . '!'));
        }
        if($row['customer_tj_box_id']){
            if (!TransTjBox::where('tj_box_code', $row['customer_tj_box_id'])->exists()) {
                throw new \Exception(json_encode('No parent Tj Box found according to your code ' . $row['customer_tj_box_id'] . '!'));
            }
        }
    }

    private function checkDuplicateCustomer($customerNumber)
    {
        if (TransCustomer::where('customer_mobile', $customerNumber)->exists()) {
            throw new \Exception(json_encode('Your given number ' . $customerNumber . ' is already in use!'));
        }
    }

    private function createTransCustomer(array $row)
    {
        $parentPopId = TransPop::where('pop_code', $row['customer_pop_id'])->value('id');
        $parentTjBoxId = TransTjBox::where('tj_box_code',$row['customer_tj_box_id'])->value('id');

        $customer = new TransCustomer();
        $customer->customer_name = $row['customer_name'];
        $customer->customer_mobile = $row['customer_mobile'];
        $customer->customer_email = $row['customer_email'];
        $customer->customer_organization = $row['customer_organization'];
        $customer->pop_id = $parentPopId;
        $customer->tj_box_id = $parentTjBoxId;
        $customer->contact_person_name = $row['contact_person_name'];
        $customer->contact_person_number_pri = $row['contact_person_primary_mobile'];
        $customer->contact_person_number_sec = $row['contact_person_secondary_mobile'];
        $customer->contact_person_designation = $row['contact_person_designation'];
        $customer->contact_person_whatsapp = $row['contact_person_whatsapp_mobile'];
        $customer->contact_person_email = $row['contact_person_email'];
        $customer->division_id = $row['division'];
        $customer->district_id = $row['district'];
        $customer->upazila_id = $row['upazila'];
        $customer->union_id = $row['union'];
        $customer->village = $row['village'];
        $customer->latitude = $row['latitude'];
        $customer->longitude = $row['longitude'];
        $customer->address_direction = $row['address_direction'];
        $customer->added_by_uid = $row['added_by_uid'];
        $customer->updated_by_uid = $row['updated_by_uid'];
        $customer->comments = $row['comments'];
        $customer->status =  'Active';
        $customer->save();

        return $customer;
    }

    private function createLatLong(array $row, $customerId)
    {
        $address = new TransLatLong();
        $address->trans_id = $customerId;
        $address->module_type = 'customer';
        $address->division_id = $row['division'];
        $address->district_id = $row['district'];
        $address->upazila_id = $row['upazila'];
        $address->union_id = $row['union'];
        $address->latitude = $row['latitude'];
        $address->longitude = $row['longitude'];
        $address->status = 'Active';
        $address->save();
    }

    private function createWorkerInfo(array $row, $customerId)
    {
        $workerInfo = new TransWorkerInfo();
        $workerInfo->trans_id = $customerId;
        $workerInfo->module_type = 'customer';
        $workerInfo->added_by_name = $row['worker_name'];
        $workerInfo->mobile_number = $row['worker_mobile'];
        $workerInfo->work_type = $row['work_type'];
        $workerInfo->save();
    }
}
