<?php

namespace App\Http\Controllers\panel\location;

use App\Http\Controllers\Controller;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use Illuminate\Http\Request;

class GeoLocationController extends Controller
{

    public function getDivision(){
        return GeoDivision::all();
    }

    public function getDistrict($pid = null){
        if(isset($pid) && !empty($pid)){
            return GeoDistrict::where('pid','=', $pid)->get();
        }
        return GeoDistrict::all();
    }

    public function getUpazila($pid = null){
        if(isset($pid) && !empty($pid)){
            return GeoUpazila::where('pid','=', $pid)->get();
        }
        return GeoUpazila::all();
    }

    public function getUnionPouroshova($pid = null){
        if(isset($pid) && !empty($pid)){
            return GeoUnionPouroshova::where('pid','=', $pid)->get();
        }
        return GeoUnionPouroshova::all();
    }

    public function getVillage($pid = null){
        if(isset($pid) && !empty($pid)){
            return GeoVillage::where('pid','=', $pid)->get();
        }
        return GeoVillage::all();
    }

    public function get_location(Request $request){
        $response_data = [];
        $request_params = $request->input();
        $pid = isset($request_params['pid']) && !empty($request_params['pid']) ? $request_params['pid'] : null;


        if($request_params['type'] === 'division'){
            $response_data = $this->getDivision();
        } else if($request_params['type'] === 'district'){
            $response_data = $this->getDistrict($pid);
        } else if($request_params['type'] === 'upazila'){
            $response_data = $this->getUpazila($pid);
        } else if($request_params['type'] === 'union_pouroshova'){
            $response_data = $this->getUnionPouroshova($pid);
        } else if($request_params['type'] === 'village'){
            $response_data = $this->getVillage($pid);
        }
        return response()->json($response_data);
    }
}
