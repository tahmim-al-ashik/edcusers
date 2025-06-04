<?php

namespace App\Http\Controllers\panel\location;

use App\Http\Controllers\Controller;
use App\Models\GeoDistrict;
use Illuminate\Http\Request;

class GeoDistrictController extends Controller
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
            return GeoDistrict::where('pid','=', $pid)->get($columns);
        }
        return GeoDistrict::all($columns);
    }


    public function store(Request $request)
    {
        $params = $request->input();
        $en_names = isset($params['en_names']) && !empty($params['en_names']) ? explode(',', $params['en_names']) : null;
        $bn_names = explode(',', $params['bn_names']);

        $serial = 0;
        foreach ($bn_names as $bn_name){
            $is_exist = GeoDistrict::where('bn_name','=', trim($bn_name))->where('pid', '=', $params['pid'])->value('id');
            if(empty($is_exist)){
                $newDistrict = new GeoDistrict();
                $newDistrict->pid = $params['pid'];
                $newDistrict->bn_name = trim(strtolower($bn_name));
                $newDistrict->en_name = $en_names !== null ? $en_names[$serial] : null;
                $newDistrict->save();
            }
            $serial++;
        }
        return true;
    }
}
