<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\TransCustomer;
use App\Models\TransLatLong;
use App\Models\TransmissionCustomers;
use App\Models\TransTjBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransTjBoxController extends Controller
{
    // Tj Box list ---
    public function transTjBoxList(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransTjBox::query();
        $query = $query->leftJoin('trans_pops', 'trans_tj_boxes.pop_id', '=', 'trans_pops.id')
        ->orderBy('trans_tj_boxes.id', 'desc')
        ->get([
            'trans_tj_boxes.id',
            'trans_pops.pop_code',
            'trans_tj_boxes.tj_box_code',
            'trans_tj_boxes.tj_box_type',
            DB::raw('(SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_tj_boxes.parent_tj_box_id) as parent_tj_box_code'),
            'trans_tj_boxes.latitude',
            'trans_tj_boxes.longitude',
            'trans_tj_boxes.address_direction',
            'trans_tj_boxes.added_by_uid',
            'trans_tj_boxes.updated_by_uid',
            'trans_tj_boxes.comments',
            'trans_tj_boxes.status',
            'trans_tj_boxes.created_at'
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

    // Tj Box list ---
    public function transTjBoxes($pop_id, $olt_port) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $query = TransTjBox::query();
        $query = $query->where('pop_id', $pop_id)->where('olt_port',$olt_port)
        ->whereIn('tj_box_type',['joining_tj','distribution_tj','customer_tj'])
        ->orderBy('trans_tj_boxes.id', 'desc')
        ->get([
            'trans_tj_boxes.id',
            'trans_tj_boxes.tj_box_code',
        ]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    public function transTjBoxesTree($tj_type, $pop_id, $parent_tj_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Convert string "null" to actual null
        if ($parent_tj_id === 'null') {
            $parent_tj_id = null;
        }

        if($tj_type === 'customer_tj'){
            $query = TransCustomer::where('pop_id', $pop_id)->where('tj_box_id', $parent_tj_id)->orderBy('trans_customers.id', 'desc');

            // Execute the query and select specific columns
            $results = $query->get([
                'trans_customers.id',
                'trans_customers.customer_name',
                'trans_customers.customer_mobile',
                'trans_customers.olt_port',
            ]);

            $formattedResults = $results->map(function ($item) {
                return [
                    'name' => $item->customer_mobile,
                    'attributes' => [
                        'id' => $item->id,
                        'customer_name' => $item->customer_name,
                        'olt_port' => $item->olt_port,
                        'module_type' => 'customer',
                    ],
                ];
            });
        }else{
            $query = TransTjBox::where('pop_id', $pop_id)->orderBy('trans_tj_boxes.id', 'desc');

            // Conditionally add the `parent_tj_box_id` filter
            if (is_null($parent_tj_id)) {
                $query->whereNull('parent_tj_box_id');
            } else {
                $query->where('parent_tj_box_id', $parent_tj_id);
            }

            // Execute the query and select specific columns
            $results = $query->get([
                'trans_tj_boxes.id',
                'trans_tj_boxes.tj_box_code',
                'trans_tj_boxes.pop_id',
                'trans_tj_boxes.olt_port',
                'trans_tj_boxes.parent_tj_box_id',
                'trans_tj_boxes.tj_box_type',
            ]);

            $formattedResults = $results->map(function ($item) {
                return [
                    'name' => $item->tj_box_code,
                    'attributes' => [
                        'id' => $item->id,
                        'module_type' => $item->tj_box_type,
                        'parent_tj_box_id' => $item->parent_tj_box_id,
                        'pop_id' => $item->pop_id,
                        'olt_port' => $item->olt_port,
                    ],
                ];
            });
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }


    // Tj Box list ---
    public function transNTTNTjBoxes($pop_id,$tj_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $query = TransTjBox::query();
        $query = $query->where('pop_id', $pop_id)
        ->whereIn('tj_box_type',['backbone_tj','backbone_join_tj'])->where('id', '!=', $tj_id)
        ->orderBy('trans_tj_boxes.id', 'desc')
        ->get([
            'trans_tj_boxes.id',
            'trans_tj_boxes.tj_box_code',
        ]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    // Summary Tj Box
    public function summaryTjBox() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // summary
        $summary = TransTjBox::selectRaw(
            'COUNT(trans_tj_boxes.id) AS total,
             COUNT(CASE WHEN trans_tj_boxes.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_tj_boxes.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    // View all in map
    public function getTransTjBoxLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransLatLong::query();
        $query->leftJoin('trans_tj_boxes','trans_tj_boxes.id', '=', 'trans_lat_longs.trans_id');
        $query->whereIn('module_type',['backbone_tj','joining_tj','distribution_tj','customer_tj']);

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
                $query->where('trans_tj_boxes.pop_id',$request->get('pop'));
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
                'trans_tj_boxes.tj_box_code as trans_code',
                'trans_lat_longs.status',
                'trans_lat_longs.latitude',
                'trans_lat_longs.longitude',
            ]);

        $returned_data['results'] = $results;
        return ResponseWrapper::End($returned_data);
    }

    // Tj Box Latest Id ---
    public function transTjBoxLatestId($tj_type) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $query = TransTjBox::where('tj_box_type', $tj_type)->latest('tj_box_code')->first('tj_box_code');

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query ?? 'null';
        return ResponseWrapper::End($returned_data);
    }
}
