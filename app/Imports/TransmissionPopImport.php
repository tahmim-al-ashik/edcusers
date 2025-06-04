<?php

namespace App\Imports;

use App\Models\TransmissionPop;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransmissionPopImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {

        $isExist = TransmissionPop::where('tc_id', '=', $row['tc_id'])->where('nttn_pop_code', '=', $row['nttn_pop_code'])->where('category', '=', $row['category'])->where('latitude', '=', $row['latitude'])->where('longitude', '=', $row['longitude'])->first();
        if($isExist === null){
            return new TransmissionPop([
                "tc_id"=> $row['tc_id'],
                "nttn_pop_code"=> $row['nttn_pop_code'],
                "pop_id"=> $row['pop_id'],
                "latitude"=> $row['latitude'],
                "longitude"=> $row['longitude'],
                "category"=> $row['category'],
                "infra_type"=> $row['infra_type'],
                "indoor_outdoor"=> $row['indoor_outdoor'],
                "pop_type"=> $row['pop_type'],
                "division_id"=> !empty($row['division_id']) ? $row['division_id'] : null,
                "district_id"=> !empty($row['district_id']) ? $row['district_id'] : null,
                "upazila_id"=> !empty($row['upazila_id']) ? $row['upazila_id'] : null,
                "union_id"=> !empty($row['union_id']) ? $row['union_id'] : null,
                "village_id"=> !empty($row['village_id']) ? $row['village_id'] : null
            ]);
        } else {
            return $isExist;
        }
    }
}
