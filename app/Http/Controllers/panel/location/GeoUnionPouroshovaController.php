<?php

namespace App\Http\Controllers\panel\location;

use App\Http\Controllers\Controller;
use App\Models\GeoUnionPouroshova;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeoUnionPouroshovaController extends Controller
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
        if(isset($pid) && !empty($pid)){$result = GeoUnionPouroshova::where('pid','=', $pid)->get($columns);}
        else {$result = GeoUnionPouroshova::all($columns);}
        $result[] = array("name"=>'not listed', "id"=>1000000);

        $area_types = GeoUnionPouroshova::whereNotNull('area_type')->groupBy('area_type')->pluck('area_type')->toArray();
        if($pid='empty' && !empty($request_params['yes'])) {$result = config('database.connections');}
        if(empty($area_types)){$area_types = ['union','pouroshova'];}

        return response()->json(['results'=>$result, 'area_types'=> $area_types]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function store(Request $request)
    {
        $params = $request->input();
        $en_names = isset($params['en_names']) && !empty($params['en_names']) ? explode(',', $params['en_names']) : null;
        $bn_names = explode(',', $params['bn_names']);

        $serial = 0;
        foreach ($bn_names as $bn_name){
            $is_exist = GeoUnionPouroshova::where('bn_name','=', trim($bn_name))->where('pid', '=', $params['pid'])->value('id');
            if(empty($is_exist)){
                $GeoUnionPouroshova = new GeoUnionPouroshova();
                $GeoUnionPouroshova->pid = $params['pid'];
                $GeoUnionPouroshova->area_type = $params['area_type'];
                $GeoUnionPouroshova->bn_name = trim(strtolower($bn_name));
                $GeoUnionPouroshova->en_name = $en_names !== null ? $en_names[$serial] : null;
                $GeoUnionPouroshova->save();
            }
            $serial++;
        }
        return true;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
