<?php

namespace App\Exports;

use App\Http\Controllers\GlobalController;
use App\Http\Controllers\HelperFunctionController;
use App\Models\JobCallCenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ExportJobCallCenter implements FromCollection,WithHeadings, ShouldAutoSize, WithStyles
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
        return['Applied Date','Department','Full Name','Mobile Number','Whatsapp Number','Email','Job Type','Lob Level','Working Hours',
            'Working Shift','Expected Salary', 'Institution Name','Academic Qualification','Internet Medium','Device Access','Skills','Experiences',
            'Division','District','Upazila/P.Station','Union/Pouroshova','Village/Word', 'Address'
        ];
    }

    public function styles(Worksheet $sheet){
        $sheet->getStyle('A1:W1')->getFont()->setBold(true);
    }


    public function collection(){

        $field_set = ['created_at','department','full_name', 'mobile_number', 'whatsapp_number', 'email','job_type', 'job_level',
            'working_hours', 'working_shift', 'expected_salary', 'institution_name', 'acc_qualification', 'internet_connection', 'device_type', 'skills',
            'experiences', 'division_id', 'district_id', 'upazila_id', 'union_id', 'village_id','address'];

        if($this->id){
            $dataList = JobCallCenter::where('id', $this->id)->get($field_set);
        } else {
            $start_date_string = $this->start_date . ' 00:00:00';
            $end_date_string = $this->end_date . ' 23:59:59';

            $dataList = JobCallCenter::whereBetween('created_at',[$start_date_string, $end_date_string])->orderBy('created_at')->get($field_set);
        }

        $serial = 0;
        foreach ($dataList as $data){
            $locationNames = (new GlobalController)->LocationNames($data['division_id'], $data['district_id'], $data['upazila_id'], $data['union_id'], $data['village_id']);
            $dataList[$serial]['division_id'] = $locationNames['division_name'];
            $dataList[$serial]['district_id'] = $locationNames['district_name'];
            $dataList[$serial]['upazila_id'] = $locationNames['upazila_name'];
            $dataList[$serial]['union_id'] = $locationNames['union_name'];
            $dataList[$serial]['village_id'] = $locationNames['village_name'];
            $dataList[$serial]['department'] = (new HelperFunctionController)->StringReplace('_',' ', $data['department']);
            $dataList[$serial]['job_level'] = (new HelperFunctionController)->StringReplace('_',' ', $data['job_level']);
            $dataList[$serial]['working_hours'] = (new HelperFunctionController)->StringReplace('_',' ', $data['working_hours']);
            $dataList[$serial]['internet_connection'] = (new HelperFunctionController)->StringReplace('_',' ', $data['internet_connection']);


            $device_type = json_decode($data['device_type']);
            $dataList[$serial]['device_type'] = "";
            $device_type_serial = 0;
            foreach ($device_type as $type){
                $dataList[$serial]['device_type'] .= $type;
                if($device_type_serial < (count($device_type) - 1)){
                    $dataList[$serial]['device_type'] .= ', ';
                }
            }

            $skills = json_decode($data['skills']);
            $skill_types = (new GlobalController)->SkillTypes('call_center');
            $skill_string = "";
            $skill_serial = 0;
            foreach ($skills as $skill_id){
                $skill_string .= $skill_types[$skill_id];
                if($skill_serial < (count($skills) - 1)){
                    $skill_string .= ', ';
                }
                $skill_serial++;
            }
            $dataList[$serial]['skills'] = $skill_string;



            $experiences = json_decode($data['experiences']);
            $experience_types = (new GlobalController)->ExperienceTypes('call_center');
            $experience_string = "";
            $experience_serial = 0;
            foreach ($experiences as $experience_id){
                $experience_string .= $experience_types[$experience_id];
                if($experience_serial < (count($experiences) - 1)){
                    $experience_string .= ', ';
                }
                $experience_serial++;
            }
            $dataList[$serial]['experiences'] = $experience_string;
            $serial++;
        }
        return $dataList;
    }
}
