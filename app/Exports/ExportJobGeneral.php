<?php

namespace App\Exports;

use App\Http\Controllers\GlobalController;
use App\Http\Controllers\HelperFunctionController;
use App\Models\JobGeneral;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ExportJobGeneral implements FromCollection,WithHeadings, ShouldAutoSize, WithStyles
{

    private $id;
    private $start_date;
    private $end_date;

    public function __construct($id, $start_date, $end_date) {
        $this->id = $id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function headings():array{
        return['Applied Date','Position Name','Full Name','Mobile Number','Whatsapp Number','Email', 'DOB', 'Nationality', 'NID', 'Picture',
            'Educations', 'Certificates','Experiences','Languages', 'Others Activity',
            'Division','District','Upazila/P.Station','Union/Pouroshova','Village/Word', 'Address', 'UTM Source'
        ];
    }

    public function styles(Worksheet $sheet){
        $sheet->getStyle('A1:W1')->getFont()->setBold(true);
    }


    public function collection(){

        $field_set = ['created_at','position_name','full_name', 'mobile_number', 'whatsapp_number', 'email','date_of_birth', 'nationality','nid','personal_img',
            'educations', 'certifications', 'experiences','languages','others_activity',
            'division_id', 'district_id', 'upazila_id', 'union_id', 'village_id','address_details','utm_source'];

        if($this->id){
            $dataList = JobGeneral::where('id', $this->id)->get($field_set);
        } else {
            $start_date_string = $this->start_date . ' 00:00:00';
            $end_date_string = $this->end_date . ' 23:59:59';

            $dataList = JobGeneral::whereBetween('created_at',[$start_date_string, $end_date_string])->orderBy('created_at')->get($field_set);
        }

        $serial = 0;
        foreach ($dataList as $data){
            $locationNames = (new GlobalController)->LocationNames($data['division_id'], $data['district_id'], $data['upazila_id'], $data['union_id'], $data['village_id']);
            $dataList[$serial]['division_id'] = $locationNames['division_name'];
            $dataList[$serial]['district_id'] = $locationNames['district_name'];
            $dataList[$serial]['upazila_id'] = $locationNames['upazila_name'];
            $dataList[$serial]['union_id'] = $locationNames['union_name'];
            $dataList[$serial]['village_id'] = $locationNames['village_name'];
            $dataList[$serial]['position_name'] = (new HelperFunctionController)->StringReplace('_',' ', $data['position_name']);
            $dataList[$serial]['date_of_birth'] = $data['date_of_birth'];
            $dataList[$serial]['nationality'] = $data['nationality'];
            $dataList[$serial]['nid'] = $data['nid'];
            $dataList[$serial]['personal_img'] = $data['personal_img'];
            $dataList[$serial]['languages'] = $data['languages'];
            $dataList[$serial]['others_activity'] = $data['others_activity'];
            $dataList[$serial]['utm_source'] = $data['utm_source'];


//            $educations = json_decode($data['educations']);
//            $dataList[$serial]['educations'] = "";
            $device_type_serial = 0;
//            foreach ($educations as $type){
//                $dataList[$serial]['educations'] .= $type;
//                if($device_type_serial < (count($educations) - 1)){
//                    $dataList[$serial]['educations'] .= ' || ';
//                }
//            }

//            $skills = json_decode($data['skills']);
//            $skill_types = (new GlobalController)->SkillTypes('call_center');
//            $skill_string = "";
//            $skill_serial = 0;
//            foreach ($skills as $skill_id){
//                $skill_string .= $skill_types[$skill_id];
//                if($skill_serial < (count($skills) - 1)){
//                    $skill_string .= ', ';
//                }
//                $skill_serial++;
//            }
//            $dataList[$serial]['skills'] = $skill_string;



//            $experiences = json_decode($data['experiences']);
////            $experience_types = (new GlobalController)->ExperienceTypes('call_center');
//            $experience_string = "";
//            $experience_serial = 0;
//            foreach ($experiences as $experience_id){
////                $experience_string .= $experience_types[$experience_id];
////                if($experience_serial < (count($experiences) - 1)){
////                    $experience_string .= ', ';
////                }
////                $experience_serial++;
//            }
//            $dataList[$serial]['experiences'] = $experience_string;
            $serial++;
        }
        return $dataList;
    }
}
