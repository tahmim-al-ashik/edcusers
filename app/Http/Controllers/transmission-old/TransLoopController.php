<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\TransLatLong;
use App\Models\TransLoop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransLoopController extends Controller
{
    // Loop list ---
    public function transLoopList(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransLoop::query();
        $query = $query->leftJoin('trans_tj_boxes', 'trans_loops.tj_box_id', '=', 'trans_tj_boxes.id')
        ->leftJoin('trans_pops', 'trans_loops.pop_id', '=', 'trans_pops.id')
        ->orderBy('trans_tj_boxes.id', 'desc')
        ->get([
            'trans_loops.id',
            'trans_loops.pop_id',
            DB::raw('(SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_loops.pop_id) as pop_code'),
            'trans_loops.tj_box_id',
            DB::raw('(SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_loops.tj_box_id) as tj_box_code'),
            'trans_loops.loop_code',
            'trans_loops.loop_type',
            'trans_loops.latitude',
            'trans_loops.longitude',
            'trans_loops.address_direction',
            'trans_loops.added_by_uid',
            'trans_loops.updated_by_uid',
            'trans_loops.comments',
            'trans_loops.status',
            'trans_loops.created_at'
        ]);

        if(!empty($request->get('skip')) && $request->get('skip') !== 'none'){
            $query->skip($totalSkip)->take($totalLimit);
        }
        $query->skip($totalSkip)->take($totalLimit);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    // Summary Tj Box
    public function summaryLoop() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // summary
        $summary = TransLoop::selectRaw(
            'COUNT(trans_loops.id) AS total,
             COUNT(CASE WHEN trans_loops.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_loops.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    // View all in map
    public function getTransLoopLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransLatLong::query();
        $query->leftJoin('trans_loops','trans_loops.id', '=', 'trans_lat_longs.trans_id');
        $query->whereIn('module_type',['reserved_loop','distribution_loop']);

        if (!empty($request->get('division')) && $request->get('division') != 'undefined') {
            $query->where('trans_lat_longs.division_id', $request->get('division'));
        }
        if (!empty($request->get('district')) && $request->get('district') != 'undefined') {
            $query->where('trans_lat_longs.district_id', $request->get('district'));
        }
        if (!empty($request->get('upazila')) && $request->get('upazila') != 'undefined') {
            $query->where('trans_lat_longs.upazila_id', $request->get('upazila'));
        }
        if (!empty($request->get('union')) && $request->get('union') != 'undefined') {
            $query->where('trans_lat_longs.union_id', $request->get('union'));
        }
        if(!empty($request->get('pop'))){
            if($request->get('pop') !== 'all'){
                $query->where('trans_loops.pop_id',$request->get('pop'));
            }
        }
        if(!empty($request->get('status'))){
            if($request->get('status') !== 'all'){
                $query->where('trans_lat_longs.status',$request->get('status'));
            }
        }

        $results = $query->skip($totalSkip)->take($totalLimit)
            ->get([
                'trans_lat_longs.id',
                'trans_lat_longs.trans_id',
                'trans_lat_longs.module_type',
                'trans_loops.loop_code as trans_code',
                'trans_lat_longs.status',
                'trans_lat_longs.latitude',
                'trans_lat_longs.longitude',
            ]);

        $returned_data['results'] = $results;
        return ResponseWrapper::End($returned_data);
    }

    // Loop Latest Id ---
    public function transLoopLatestId($loop_type) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $query = TransLoop::where('loop_type', $loop_type)->latest('loop_code')->first('loop_code');

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query ?? 'null';
        return ResponseWrapper::End($returned_data);
    }
}
