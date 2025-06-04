<?php

namespace App\Imports;

use App\Models\TransmissionCustomers;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransmissionCustomersImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new TransmissionCustomers([
            "customer_name"=> $row['customer_name'],
            "mobile_number"=> !empty($row['mobile_number']) ? $row['mobile_number'] : null,
            "email"=> !empty($row['email']) ? $row['email'] : null,
            "contact_name"=> !empty($row['contact_name']) ? $row['contact_name'] : null,
            "contact_number"=> !empty($row['contact_number']) ? $row['contact_number'] : null,
            "contact_email"=> !empty($row['contact_email']) ? $row['contact_email'] : null,
            "contact_designation"=> !empty($row['contact_designation']) ? $row['contact_designation'] : null,
            "organization"=> !empty($row['organization']) ? $row['organization'] : null,
            "by_road"=> null,
            "by_air"=> null,
            "latitude"=> !empty($row['latitude']) ? $row['latitude'] : null,
            "longitude"=> !empty($row['longitude']) ? $row['longitude'] : null,
            "package_name"=> !empty($row['package_name']) ? $row['package_name'] : null,
            "division_id"=> !empty($row['division_id']) ? $row['division_id'] : null,
            "district_id"=> !empty($row['district_id']) ? $row['district_id'] : null,
            "upazila_id"=> !empty($row['upazila_id']) ? $row['upazila_id'] : null,
            "union_id"=> !empty($row['union_id']) ? $row['union_id'] : null,
            "village_id"=> !empty($row['village_id']) ? $row['village_id'] : null,
            "address"=> !empty($row['address']) ? $row['address'] : null,
        ]);
    }
}
