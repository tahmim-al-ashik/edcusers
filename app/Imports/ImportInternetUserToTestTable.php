<?php

namespace App\Imports;

use App\Models\InternetUserTest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ImportInternetUserToTestTable implements WithStartRow, ToCollection
{

    public function startRow(): int
    {
        return 2;
    }

    public $data;

    public function collection(Collection $rows)
    {
        $imported = [];
        foreach ($rows as $row){

            $full_name = $row[0];
            $mobile_number = $row[1];
            $email = $row[2];
            $division_id = $row[3];
            $district_id = $row[4];
            $upazila_id = $row[5];
            $union_id = $row[6];
            $village_id = $row[7];
            $address_others_info = $row[8];
            $wifi_package_id = $row[9];
            $broadband_package_id = $row[10];

            $isDuplicate = InternetUserTest::where('mobile_number', $mobile_number)->exists();

            if(empty($wifi_package_id) && empty($broadband_package_id) || $isDuplicate || empty($mobile_number) && empty($division_id) || empty($district_id) || empty($upazila_id) || empty($union_id) || empty($village_id)){
                $error_item = [
                    'full_name' => $full_name,
                    'mobile_number' => $mobile_number,
                    'email' => $email,
                    'division_id' => $division_id,
                    'district_id' => $upazila_id,
                    'upazila_id' => $upazila_id,
                    'union_id' => $union_id,
                    'village_id' => $village_id,
                    'address_others_info' => $address_others_info,
                    'wifi_package_id' => $wifi_package_id,
                    'broadband_package_id' => $broadband_package_id,
                    'has_error'=> 1,
                    'is_duplicate' => $isDuplicate
                ];
                $imported[] = $error_item;
            }
            else {
                $import = InternetUserTest::create([
                    'full_name' => $full_name,
                    'mobile_number' => $mobile_number,
                    'email' => $email,
                    'division_id' => $division_id,
                    'district_id' => $district_id,
                    'upazila_id' => $upazila_id,
                    'union_id' => $union_id,
                    'village_id' => $village_id,
                    'address_others_info' => $address_others_info,
                    'wifi_package_id' => $wifi_package_id,
                    'broadband_package_id' => $broadband_package_id,
                ]);
                $imported[] = $import;
            }
        }

        // set data to return variable
        $this->data = $imported;
    }
}
