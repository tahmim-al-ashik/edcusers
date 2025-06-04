<?php

namespace App\Exports;

use App\Http\Controllers\GlobalController;
use App\Http\Controllers\HelperFunctionController;
use App\Models\JobCallCenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ExportJobCallCenterDateToDate implements FromQuery, WithHeadings, WithStyles
{

    /**
     * @return \Illuminate\Support\Collection
     */

    use Exportable;

    private $headings;
    private $field_set;
    private $start_date;
    private $end_date;

    public function __construct($headings, $field_set, $start_date, $end_date) {
        $this->headings = $headings;
        $this->field_set = $field_set;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }


    public function query(){

//        $startDate = explode("-", $this->start_date);
//        $endDate = explode("-", $this->end_date);
//        $start_date_string = ($startDate[0] < 10 ? '0'.$startDate[0] : $startDate[0]) . '/' . ($startDate[1] < 10 ? '0'.$startDate[1] : $startDate[1]) . '/'. $startDate[2] . ' 00:00:00';
//        $end_date_string = ($endDate[0] < 10 ? '0'.$endDate[0] : $endDate[0]) . '/' . ($endDate[1] < 10 ? '0'.$endDate[1] : $endDate[1]) . '/'. $endDate[2] . ' 23:59:59';
//        $dataList = JobCallCenter::whereDate('created_at', '>=', '06/09/2022 00:00:00')->whereDate('created_at', '<=', '06/18/2022 23:59:59')->get($this->field_set);
        $dataList = DB::table('job_call_centers')
//            ->whereBetween('created_at',[ '2022-06-09 00:00:00', '2022-06-18 23:59:59'])
            ->orderBy('id')
            ->get(['full_name']);
        return $dataList;


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

    public function headings():array{
        return $this->headings;
    }

    public function styles(Worksheet $sheet){
        $sheet->getStyle('A1:W1')->getFont()->setBold(true);
    }
}
