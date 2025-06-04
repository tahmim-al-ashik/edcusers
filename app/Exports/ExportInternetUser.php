<?php

namespace App\Exports;

use App\Http\Controllers\GlobalController;
use App\Http\Controllers\HelperFunctionController;
use App\Models\InternetUser;
use App\Models\Package;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ExportInternetUser implements FromCollection,WithHeadings, ShouldAutoSize, WithStyles
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
        return['Applied Date','Full Name','Mobile Number','Whatsapp Number','Email','WiFi Package', 'WiFi Package Price','Broadband Package','Broadband Package Price','Current Connection Type',
            'Existing Providers', 'Division','District','Upazila/P.Station','Union/Pouroshova','Village/Word', 'House No', 'Address'
        ];
    }

    public function styles(Worksheet $sheet){
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true);
    }


    public function collection(){

        $field_set = ['created_at','full_name', 'mobile_number', 'whatsapp_number', 'email','wifi_package_id', 'wifi_package_id as wifi_package_price', 'broadband_package_id', 'broadband_package_id as broadband_package_price',
            'current_conn_type', 'provider_names', 'division_id', 'district_id', 'upazila_id', 'union_id', 'village_id','house_no','address_others_info'];

        if($this->id){
            $dataList = InternetUser::where('id', $this->id)->get($field_set);
        } else {
            $start_date_string = $this->start_date . ' 00:00:00';
            $end_date_string = $this->end_date . ' 23:59:59';

            $dataList = InternetUser::whereBetween('created_at',[$start_date_string, $end_date_string])->orderBy('created_at')->get($field_set);
        }

        $packages_query = Package::all(['id','bn_title as title','price']);
        $packages = array();
        foreach ($packages_query as $package){
            $packages[$package->id] = $package;
        }


        $serial = 0;
        foreach ($dataList as $data){
            $locationNames = (new GlobalController)->LocationNames($data['division_id'], $data['district_id'], $data['upazila_id'], $data['union_id'], $data['village_id']);
            $dataList[$serial]['division_id'] = $locationNames['division_name'];
            $dataList[$serial]['district_id'] = $locationNames['district_name'];
            $dataList[$serial]['upazila_id'] = $locationNames['upazila_name'];
            $dataList[$serial]['union_id'] = $locationNames['union_name'];
            $dataList[$serial]['village_id'] = $locationNames['village_name'];

            if(!empty($dataList[$serial]['wifi_package_id'])){
                $dataList[$serial]['wifi_package_price'] = $packages[$dataList[$serial]['wifi_package_id']]['price'];
                $dataList[$serial]['wifi_package_id'] = $packages[$dataList[$serial]['wifi_package_id']]['title'];
            }
            if(!empty($dataList[$serial]['broadband_package_id'])){
                $dataList[$serial]['broadband_package_price'] = $packages[$dataList[$serial]['broadband_package_id']]['price'];
                $dataList[$serial]['broadband_package_id'] = $packages[$dataList[$serial]['broadband_package_id']]['title'];
            }
            $dataList[$serial]['mobile_broadband'] = str_replace("_"," & ", $dataList[$serial]['mobile_broadband']);


            $serial++;
        }
        return $dataList;
    }
}
