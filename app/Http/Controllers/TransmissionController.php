<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Imports\TransmissionCustomersImport;
use App\Imports\TransmissionCustomersImportProblemCheck;
use App\Imports\TransmissionPopImport;
use App\Imports\TransmissionPopImportProblemCheck;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\NetworkSupportCenter;
use App\Models\TransmissionCompany;
use App\Models\TransmissionCustomers;
use App\Models\TransmissionPop;
use App\Models\TransmissionPopProblemCheck;
use App\Models\TransmissionTjbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TransmissionController extends Controller
{

    private function getDistance($startLat, $startLng, $endLat, $endLng)
    {
        $apiKey = (new \App\Classes\CustomHelpers)->apiSecrets('openrouteservice');
        $response = Http::get("https://api.openrouteservice.org/v2/directions/driving-car", [
            'api_key' => $apiKey,
            'start' => "$startLng,$startLat",
            'end' => "$endLng,$endLat"
        ]);
        $data = $response->json();

        $distance = 0;
        if (!empty($data['features'])){
            $distance = $data['features'][0]['properties']['summary']['distance'];
        }

        return $distance;
    }

    public function calculatePopWiseCustomerDistance(Request $request, $district_id, $upazila_id, $union_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        if($district_id === 'all'){
            $returned_data['error_type'] = 'district_required';
            $returned_data['message'] = 'district filter required';
            return ResponseWrapper::End($returned_data);
        }

        $query = TransmissionPop::query();
        $query->where('district_id', '=', $district_id);
        if($upazila_id !== 'all'){$query->where('upazila_id', '=', $upazila_id);}
        if($union_id !== 'all'){$query->where('union_id', '=', $union_id);}
        $totalPops = $query->count();
        $query->select('tc_id', 'category', 'nttn_pop_code', 'pop_id','latitude','longitude');
        $popList = $query->get()->toArray();


        $convertedPopList = [];
        foreach ($popList as $item){
            $item['250m'] = [];
            $item['500m'] = [];
            $item['750m'] = [];
            $item['1000m'] = [];
            $item['1500m'] = [];
            $item['2000m'] = [];
            $item['2500m'] = [];
            $item['3000m'] = [];
            $item['3500m'] = [];
            $item['4000m'] = [];
            $item['4500m'] = [];
            $item['5000m'] = [];
            $item['5000up'] = [];
            $convertedPopList[$item['nttn_pop_code']] = $item;
        }

        $csQuery = TransmissionCustomers::query();
        $csQuery->where('district_id', '=', $district_id);
        if($upazila_id !== 'all'){$csQuery->where('upazila_id', '=', $upazila_id);}
        if($union_id !== 'all'){$csQuery->where('union_id', '=', $union_id);}
        $customerList = $csQuery->take(50)->get(['customer_name','latitude','longitude']);


        foreach ($customerList as $customer){
            $nearestParentPopId = null;
            $nearestDistance = INF;

            foreach ($popList as $parentPop) {
                $distance = $this->getDistance($customer->latitude, $customer->longitude, $parentPop['latitude'], $parentPop['longitude']);
                if ($distance> 0 && $distance < $nearestDistance) {
                    $nearestParentPopId = $parentPop['nttn_pop_code'];
                    $nearestDistance = $distance;
                }
            }

            if ($nearestDistance <= 10000) { // Skip childPops exceeding 10,000 meters
                $customer->distance = $nearestDistance;

                if($nearestDistance <= 250){
                    $convertedPopList[$nearestParentPopId]['250m'][] = $customer;
                } else if($nearestDistance <= 500){
                    $convertedPopList[$nearestParentPopId]['500m'][] = $customer;
                } else if($nearestDistance <= 750){
                    $convertedPopList[$nearestParentPopId]['750m'][] = $customer;
                } else if($nearestDistance <= 1000){
                    $convertedPopList[$nearestParentPopId]['1000m'][] = $customer;
                } else if($nearestDistance <= 1500){
                    $convertedPopList[$nearestParentPopId]['1500m'][] = $customer;
                } else if($nearestDistance <= 2000){
                    $convertedPopList[$nearestParentPopId]['2000m'][] = $customer;
                } else if($nearestDistance <= 2500){
                    $convertedPopList[$nearestParentPopId]['2500m'][] = $customer;
                } else if($nearestDistance <= 3000){
                    $convertedPopList[$nearestParentPopId]['3000m'][] = $customer;
                } else if($nearestDistance <= 3500){
                    $convertedPopList[$nearestParentPopId]['3500m'][] = $customer;
                } else if($nearestDistance <= 4000){
                    $convertedPopList[$nearestParentPopId]['4000m'][] = $customer;
                } else if($nearestDistance <= 4500){
                    $convertedPopList[$nearestParentPopId]['4500m'][] = $customer;
                } else if($nearestDistance <= 5000){
                    $convertedPopList[$nearestParentPopId]['5000m'][] = $customer;
                } else {
                    $convertedPopList[$nearestParentPopId]['5000up'][] = $customer;
                }

            }

        }

        $returned_data['results']['list'] = $convertedPopList;
        $returned_data['results']['total_pops'] = $totalPops;

        return ResponseWrapper::End($returned_data);
    }


    public function transmissionCompanyCreateUpdate(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        if($request->get('id') !== null){
            $tCompany = TransmissionCompany::find($request->get('id'));
        } else {
            $tCompany = new TransmissionCompany();
        }
        $tCompany->name = $request->get('name');
        $tCompany->contact_person_name_first = $request->get('contact_person_name_first');
        $tCompany->contact_person_number_first = $request->get('contact_person_number_first');
        $tCompany->contact_person_designation_first = $request->get('contact_person_designation_first');
        $tCompany->contact_person_name_sec = $request->get('contact_person_name_sec');
        $tCompany->contact_person_number_sec = $request->get('contact_person_number_sec');
        $tCompany->contact_person_designation_sec = $request->get('contact_person_designation_sec');
        $tCompany->company_type = $request->get('company_type');
        $tCompany->vendor_name = $request->get('vendor_name');
        $tCompany->status = $request->get('status');
        $tCompany->save();

        $returned_data['results'] = $tCompany;

        return ResponseWrapper::End($returned_data);
    }
    public function deleteTransmissionCompany(Request $request, $rowId) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = TransmissionCompany::where('id', '=', $rowId)->delete();
        return ResponseWrapper::End($returned_data);
    }
    public function transmissionCompanyDetails(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = TransmissionCompany::find($id);
        return ResponseWrapper::End($returned_data);
    }
    public function getTransmissionCompanySummary(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = TransmissionCompany::all()->groupBy('status')->map(function ($row) {
            return $row->count();
        });

        $countArray["active"] = !empty($query['active']) ? $query['active'] : 0;
        $countArray["inactive"] = !empty($query['inactive']) ? $query['inactive'] : 0;
        $countArray["total"] = $countArray['active'] + $countArray['inactive'];

        $returned_data['results'] = $countArray;

        return ResponseWrapper::End($returned_data);
    }
    public function getTransmissionCompanyList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

        $query = TransmissionCompany::query();
        if($request->get('status') !== 'all'){
            $query->where('status', '=', $request->get('status'));
        }
        $query->orderBy('name');
        $query->skip($totalSkip)->take(50);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get();

        return ResponseWrapper::End($returned_data);

    }
    public function getTransmissionActiveCompanyList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $query = TransmissionCompany::query();
        $query->where('status', '=', 'active');
        $query->orderBy('name');
        $returned_data['results'] = $query->get(['id','name']);

        return ResponseWrapper::End($returned_data);

    }
    public function getTransmissionActiveSupportCenterList(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = NetworkSupportCenter::where('status', '=', 'active')->orderBy('zone_name')->get(['id','uid','zone_id','zone_name']);
        return ResponseWrapper::End($returned_data);
    }

    public function getTransmissionPopSummary(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = TransmissionPop::all()->groupBy('status')->map(function ($row) {
            return $row->count();
        });

        $countArray["active"] = !empty($query['active']) ? $query['active'] : 0;
        $countArray["pending"] = !empty($query['pending']) ? $query['pending'] : 0;
        $countArray["total"] = $countArray['active'] + $countArray['pending'];

        $returned_data['results'] = $countArray;

        return ResponseWrapper::End($returned_data);
    }
    public function getTransmissionPopList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        // get user list on partner area
        $query = TransmissionPop::query();
        $query->leftJoin('transmission_companies', 'transmission_companies.id', '=', 'transmission_pops.tc_id');
        if($request->get('status') !== 'all'){
            $query->where('transmission_pops.status', '=', $request->get('status'));
        }
        if($request->get('district') !== 'all'){
            $query->where('transmission_pops.district_id', '=', $request->get('district'));
        }
        if($request->get('upazila') !== 'all'){
            $query->where('transmission_pops.upazila_id', '=', $request->get('upazila'));
        }
        if($request->get('union') !== 'all'){
            $query->where('transmission_pops.union_id', '=', $request->get('union'));
        }
        $query->orderBy('created_at', $sortBy);
        $query->skip($totalSkip)->take(100);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(['transmission_pops.id','transmission_pops.nttn_pop_code','transmission_pops.pop_id','transmission_pops.status','transmission_pops.created_at','transmission_companies.name','transmission_companies.company_type']);

        return ResponseWrapper::End($returned_data);

    }

    public function deleteTransmissionPop(Request $request, $rowId) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = TransmissionPop::where('id', '=', $rowId)->delete();
        return ResponseWrapper::End($returned_data);
    }

    public function getTransmissionPopDetails(Request $request, $rowId) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = TransmissionPop::query();
        $query->leftJoin('transmission_companies', 'transmission_companies.id', '=', 'transmission_pops.tc_id');
        $query->where('transmission_pops.id', '=', $rowId);
        $details = $query->first(['transmission_companies.name','transmission_companies.company_type','transmission_pops.*']);

        if(!empty($details['division_id'])){
            $details['division'] = GeoDivision::where('id', '=', $details['division_id'])->value('bn_name');
        }
        if(!empty($details['district_id'])){
            $details['district'] = GeoDistrict::where('id', '=', $details['district_id'])->value('bn_name');
        }
        if(!empty($details['upazila_id'])){
            $details['upazila'] = GeoUpazila::where('id', '=', $details['upazila_id'])->value('bn_name');
        }
        if(!empty($details['union_id'])){
            $details['union'] = GeoUnionPouroshova::where('id', '=', $details['union_id'])->value('bn_name');
        }
        if(!empty($details['village_id'])){
            $details['village'] = GeoVillage::where('id', '=', $details['village_id'])->value('bn_name');
        }
        unset($details['tc_id']);
        unset($details['division_id']);
        unset($details['district_id']);
        unset($details['upazila_id']);
        unset($details['union_id']);
        unset($details['village_id']);
        unset($details['updated_at']);

        $returned_data['results'] = $details;
        return ResponseWrapper::End($returned_data);
    }

    public function getTransmissionAddNewPop(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

//        $existingPop = TransmissionPop::where('tc_id', '=', $request->get('tc_id'))->where('category', '=', $request->get('category'))->where('nttn_pop_code', '=', $request->get('nttn_pop_code'))->first();
//        if(empty($existingPop)){
//            $division = $request->get('division');
//            $district = $request->get('district');
//            $upazila = $request->get('upazila');
//            $union = $request->get('union');
//            $village = $request->get('village');
//
//            $query = new TransmissionPop();
//            $query->tc_id = $request->get('tc_id');
//            $query->nttn_pop_code = $request->get('nttn_pop_code');
//            $query->pop_id = $request->get('pop_id');
//            $query->latitude = $request->get('latitude');
//            $query->longitude = $request->get('longitude');
//            $query->category = $request->get('category');
//            $query->infra_type = $request->get('infra_type');
//            $query->indoor_outdoor = $request->get('indoor_outdoor');
//            $query->pop_type = $request->get('pop_type');
//            $query->division_id = !empty($division) ? $division['id'] : null;
//            $query->district_id = !empty($district) ? $district['id'] : null;
//            $query->upazila_id = !empty($upazila) ? $upazila['id'] : null;
//            $query->union_id = !empty($union) ? $union['id'] : null;
//            $query->village_id = !empty($village) ? $village['id'] : null;
//            $query->save();
//
//            if($query->id){
//                $returned_data['results'] = $query->id;
//            }
//            $returned_data['exist'] = null;
//
//        } else {
//            $returned_data['results'] = $existingPop['id'];
//            $returned_data['exist'] = 'exist';
//        }

        return ResponseWrapper::End($returned_data);
    }

    public function getTransmissionTjboxSummary(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = TransmissionTjbox::all()->groupBy('status')->map(function ($row) {
            return $row->count();
        });

        $countArray["active"] = !empty($query['active']) ? $query['active'] : 0;
        $countArray["pending"] = !empty($query['pending']) ? $query['pending'] : 0;
        $countArray["total"] = $countArray['active'] + $countArray['pending'];

        $returned_data['results'] = $countArray;

        return ResponseWrapper::End($returned_data);
    }
    public function getTransmissionTjboxList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';
        return ResponseWrapper::End($returned_data);

    }

    public function getTransmissionCustomerSummary(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = TransmissionCustomers::all()->groupBy('status')->map(function ($row) {
            return $row->count();
        });

        $countArray["active"] = !empty($query['active']) ? $query['active'] : 0;
        $countArray["pending"] = !empty($query['pending']) ? $query['pending'] : 0;
        $countArray["total"] = $countArray['active'] + $countArray['pending'];

        $returned_data['results'] = $countArray;

        return ResponseWrapper::End($returned_data);
    }
    public function getTransmissionCustomerList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';


        $query = TransmissionCustomers::query();
        $query->orderBy('created_at', $sortBy);
        $query->skip($totalSkip)->take(100);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(['id', 'customer_name','mobile_number','email','organization','status']);

        return ResponseWrapper::End($returned_data);

    }

    public function deleteTransmissionCustomer(Request $request, $rowId) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = TransmissionCustomers::where('id', '=', $rowId)->delete();
        return ResponseWrapper::End($returned_data);
    }

    public function getTransmissionCustomerDetails(Request $request, $rowId) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $details = TransmissionCustomers::where('id', '=', $rowId)->first();

        if(!empty($details['division_id'])){
            $details['division'] = GeoDivision::where('id', '=', $details['division_id'])->value('bn_name');
        }
        if(!empty($details['district_id'])){
            $details['district'] = GeoDistrict::where('id', '=', $details['district_id'])->value('bn_name');
        }
        if(!empty($details['upazila_id'])){
            $details['upazila'] = GeoUpazila::where('id', '=', $details['upazila_id'])->value('bn_name');
        }
        if(!empty($details['union_id'])){
            $details['union'] = GeoUnionPouroshova::where('id', '=', $details['union_id'])->value('bn_name');
        }
        if(!empty($details['village_id'])){
            $details['village'] = GeoVillage::where('id', '=', $details['village_id'])->value('bn_name');
        }
        unset($details['division_id']);
        unset($details['district_id']);
        unset($details['upazila_id']);
        unset($details['union_id']);
        unset($details['village_id']);
        unset($details['updated_at']);

        $returned_data['results'] = $details;
        return ResponseWrapper::End($returned_data);
    }

    public function getTransmissionCustomerNearestPops(Request $request, $customer_id, $latitude, $longitude) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $nearestPop = TransmissionPop::select(DB::raw("transmission_companies.name, transmission_companies.company_type, transmission_pops.id, transmission_pops.tc_id,
         transmission_pops.nttn_pop_code, transmission_pops.pop_id, transmission_pops.latitude, transmission_pops.longitude,
         ( 6371 * acos( cos( radians('$latitude') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians( latitude ) ) ) ) AS distance"))
            ->leftJoin('transmission_companies', 'transmission_companies.id', '=', 'transmission_pops.tc_id')
            ->where('transmission_pops.status', '=', 'active')
            ->havingRaw('distance < 10')
            ->orderBy('distance')
            ->first();

        $returned_data['results'] = $nearestPop;

//        $returned_data['res'] = (new \App\Classes\CustomHelpers)->calculateDistanceBetweenTwoPoints($latitude,$longitude,$nearestPop['latitude'],$nearestPop['longitude'],'MT',true,5);

        return ResponseWrapper::End($returned_data);
    }

    public function getTransmissionPopNearestCustomers(Request $request, $latitude, $longitude, $radiation) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = TransmissionCustomers::select(DB::raw("transmission_customers.customer_name, transmission_customers.latitude, transmission_customers.longitude,
         ( 6371 * acos( cos( radians('$latitude') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians( latitude ) ) ) ) AS distance"))
            ->havingRaw('distance < '. $radiation )
            ->orderBy('distance')
            ->get();

        return ResponseWrapper::End($returned_data);
    }

    public function testTransmissionCustomerNearestPops(Request $request, $latitude1, $longitude1, $latitude2, $longitude2) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $returned_data['results'] = (new \App\Classes\CustomHelpers)->calculateDistanceBetweenTwoPoints($latitude1,$longitude1,$latitude2,$longitude2,'MT',false,2);

        return ResponseWrapper::End($returned_data);
    }


    public function importTransmissionPops(Request $request, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // clean problem check table first
        TransmissionPopProblemCheck::truncate();

        if($type === 'problem_check'){
            $imported_data = new TransmissionPopImportProblemCheck();
            Excel::import($imported_data, $request->file('attachment'));
            $returned_data['results'] = 'success';
        } else if($type === 'insert') {
            $imported_data = new TransmissionPopImport();
            Excel::import($imported_data, $request->file('attachment'));
            $returned_data['results'] = 'success';
        }

        return ResponseWrapper::End($returned_data);
    }

    public function importTransmissionCustomers(Request $request, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // clean problem check table first
        TransmissionPopProblemCheck::truncate();

        if($type === 'problem_check'){
            $imported_data = new TransmissionCustomersImportProblemCheck();
            Excel::import($imported_data, $request->file('attachment'));
            $returned_data['results'] = 'success';
        } else if($type === 'insert') {
            $imported_data = new TransmissionCustomersImport();
            Excel::import($imported_data, $request->file('attachment'));
            $returned_data['results'] = 'success';
        }

        return ResponseWrapper::End($returned_data);
    }
}
