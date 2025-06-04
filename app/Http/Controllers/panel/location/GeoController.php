<?php

namespace App\Http\Controllers\panel\location;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\NetworkSupportCenter;
use App\Models\SalesPoint;
use App\Models\UserLatlong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeoController extends Controller
{

    public function sharedTotalLocationCount(){
        return array(
            "division"=> GeoDivision::count(),
            "district"=> GeoDistrict::count(),
            "upazila"=> GeoUpazila::count(),
            "union_pouroshova"=> GeoUnionPouroshova::count(),
            "village"=> GeoVillage::count(),
        );
    }

    public function geoItemDelete(Request $request, $id){

        $location_type = $request->get('type');
        $deleted = '';
        if($location_type === 'village'){
            $deleted = GeoVillage::destroy($id);
        } else if($location_type === 'union_pouroshova'){
            $deleted = GeoUnionPouroshova::destroy($id);
        } else if($location_type === 'upazila'){
            $deleted = GeoUpazila::destroy($id);
        } else if($location_type === 'district'){
            $deleted = GeoDistrict::destroy($id);
        }

        return response()->json(['status'=>$deleted]);
    }

    public function geoItemUpdate(Request $request, $id)
    {
        $params = $request->input();

        $location = null;
        if($params['type'] === 'village'){
            $location = GeoVillage::find($id);
            $location['area_type'] = $params['area_type'];
        } else if($params['type'] === 'union_pouroshova'){
            $location = GeoUnionPouroshova::find($id);
            $location['area_type'] = $params['area_type'];
        } else if($params['type'] === 'upazila'){
            $location = GeoUpazila::find($id);
        } else if($params['type'] === 'district'){
            $location = GeoDistrict::find($id);
        }

        if($location){
            $location['bn_name'] = $params['bn_name'];
            $location['en_name'] = $params['en_name'];
            $location->save();
        }
        return $location;
    }

    public function getCoverageLatLng(){
        $str = file_get_contents(base_path() . '/assets/data_files/all_coverage_lat_lng.json');
        return json_decode($str, true);
    }

    public function sharedGetAreaList(Request $request, $area_type, $pid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        if($area_type === 'division'){
            $returned_data['results'] = GeoDivision::all();
        } else if($area_type === 'district'){
            $returned_data['results'] = GeoDistrict::where('pid', '=', $pid)->get();
        } else if($area_type === 'upazila'){
            $returned_data['results'] = GeoUpazila::where('pid', '=', $pid)->get();
        } else if($area_type === 'union'){
            $returned_data['results'] = GeoUnionPouroshova::where('pid', '=', $pid)->get();
        } else if($area_type === 'village'){
            $returned_data['results'] = GeoVillage::where('pid', '=', $pid)->get();
        }

        return ResponseWrapper::End($returned_data);
    }


    public function getZoneInfo($latitude, $longitude) {
        return NetworkSupportCenter::select(DB::raw("zone_name,zone_id, ( 6371 * acos( cos( radians('$latitude') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians( latitude ) ) ) ) AS distance"))->where('status', '=', 'active')->havingRaw('distance < 7')->orderBy('distance')->first();
    }


    public function getNearestZone(Request $request, $latitude, $longitude) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = NetworkSupportCenter::select(DB::raw("zone_name,zone_id, ( 6371 * acos( cos( radians('$latitude') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians( latitude ) ) ) ) AS distance"))->where('status', '=', 'active')->havingRaw('distance < 7')->orderBy('distance')->first();
        return ResponseWrapper::End($returned_data);
    }

    public function getAllLatLong() : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = UserLatlong::all();
        return ResponseWrapper::End($returned_data);
    }
}
