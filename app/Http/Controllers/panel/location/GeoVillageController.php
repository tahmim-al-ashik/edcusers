<?php

namespace App\Http\Controllers\panel\location;

use App\Http\Controllers\Controller;
use App\Models\GeoVillage;
use Illuminate\Http\Request;

class GeoVillageController extends Controller
{
    public function sharedIndex(Request $request){

        $request_params = $request->input();
        $columns = ['*'];

        if(isset($request_params['language']) && $request_params['language'] === 'en'){
            $columns[] = 'en_name as name';
        } else if(isset($request_params['language']) && $request_params['language'] === 'bn'){
            $columns[] = 'bn_name as name';
        }
        $pid = isset($request_params['pid']) && !empty($request_params['pid']) ? $request_params['pid'] : null;


        if(isset($pid) && !empty($pid)){
            $result = GeoVillage::where('pid','=', $pid)->get($columns);
        } else {
            $result = GeoVillage::all($columns);
        }

        $result[] = array("name"=>'not listed', "id"=>1000000);

        $area_types = GeoVillage::whereNotNull('area_type')->groupBy('area_type')->pluck('area_type')->toArray();
        if(empty($area_types)){
            $area_types = ['village'];
        }

        return response()->json(['results'=>$result, 'area_types'=> $area_types]);
    }

    public function index(Request $request){

        $request_params = $request->input();
        $result = array();

        $pid = isset($request_params['pid']) && !empty($request_params['pid']) ? $request_params['pid'] : null;
        if(isset($pid) && !empty($pid)){
            $result = GeoVillage::where('pid','=', $pid)->get();
        }

        return $result;
    }

    public function store(Request $request)
    {
        $params = $request->input();
        $en_names = isset($params['en_names']) && !empty($params['en_names']) ? explode(',', $params['en_names']) : null;
        $bn_names = explode(',', $params['bn_names']);

        $serial = 0;
        foreach ($bn_names as $bn_name){
            $is_exist = GeoVillage::where('bn_name','=', trim($bn_name))->where('pid', '=', $params['pid'])->value('id');
            if(empty($is_exist)){
                $GeoVillage = new GeoVillage();
                $GeoVillage->pid = $params['pid'];
                $GeoVillage->area_type = $params['area_type'];
                $GeoVillage->bn_name = trim(strtolower($bn_name));
                $GeoVillage->en_name = $en_names !== null ? $en_names[$serial] : null;
                $GeoVillage->save();
            }
            $serial++;
        }
        return true;
    }
}
