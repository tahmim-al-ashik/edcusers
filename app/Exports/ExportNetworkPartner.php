<?php

namespace App\Exports;

use App\Http\Controllers\GlobalController;
use App\Http\Controllers\HelperFunctionController;
//use App\Models\InternetUser;
//use App\Models\NetworkPartner;
//use App\Models\Package;
//use App\Models\PartnerExistingFacility;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ExportNetworkPartner implements FromCollection,WithHeadings, ShouldAutoSize, WithStyles
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
        return['Applied Date','Full Name','Mobile Number','Whatsapp Number','Email','Existing Capabilities', 'Current Business','Monthly Sale',
            'Interested Investment','Interest Reason','Previous Experience','Forward Months','Ref. Person Name','Ref. Person Mobile','Ref. Person Relation',
            'Partner Coverage Type', 'Partner Coverage ID','Latitude','Longitude', 'Division','District','Upazila/P.Station','Union/Pouroshova','Village/Word', 'House No', 'Address'
        ];
    }

    public function styles(Worksheet $sheet){
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true);
    }


    public function collection(){

        $field_set = ['created_at','full_name', 'mobile_number', 'whatsapp_number', 'email','existing_facilities', 'current_business', 'monthly_sale',
            'interested_investment','interest_reason','previous_experience','forward_months','ref_person_name','ref_person_mobile','ref_person_relation',
            'partner_coverage_type','partner_coverage_ids','latitude','longitude', 'division_id', 'district_id', 'upazila_id', 'union_id', 'village_id',
            'house_no','address_others_info'];

        if($this->id){
            $dataList = NetworkPartner::where('id', $this->id)->get($field_set);
        } else {
            $start_date_string = $this->start_date . ' 00:00:00';
            $end_date_string = $this->end_date . ' 23:59:59';

            $dataList = NetworkPartner::whereBetween('created_at',[$start_date_string, $end_date_string])->orderBy('created_at')->get($field_set);
        }

        $partner_facilities_query = PartnerExistingFacility::all();
        $partner_facilities = [];
        foreach ($partner_facilities_query as $facility){
            $partner_facilities[$facility->id] = $facility;
        }


        $serial = 0;
        foreach ($dataList as $data){
            $locationNames = (new GlobalController)->LocationNames($data['division_id'], $data['district_id'], $data['upazila_id'], $data['union_id'], $data['village_id']);
            $dataList[$serial]['division_id'] = $locationNames['division_name'];
            $dataList[$serial]['district_id'] = $locationNames['district_name'];
            $dataList[$serial]['upazila_id'] = $locationNames['upazila_name'];
            $dataList[$serial]['union_id'] = $locationNames['union_name'];
            $dataList[$serial]['village_id'] = $locationNames['village_name'];

            $facilities = json_decode($data['existing_facilities']);
            $facility_string = "";
            $fserial = 0;
            foreach ($facilities as $fid){
                $facility_string .= $partner_facilities[$fid]->bn;
                if($fserial < (count($facilities) - 1)){
                    $facility_string .= ", \n";
                }
                $fserial++;
            }
            $dataList[$serial]['existing_facilities'] = $facility_string;


            $dataList[$serial]['previous_experience'] = (int) $dataList[$serial]['previous_experience'] === 0 ? 'No' : 'Yes';
            $dataList[$serial]['forward_months'] = $dataList[$serial]['forward_months'] === '3_m' ? '3 Months' : ($dataList[$serial]['forward_months'] === '6_m' ? '6 Months' : $dataList[$serial]['forward_months'] === '12 Months');

            $serial++;
        }
        return $dataList;
    }
}
