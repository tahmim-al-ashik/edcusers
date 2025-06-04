<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Imports\TransBackboneTjBoxImport;
use App\Models\InternetUsers;
use App\Models\TransCableDetail;
use App\Models\TransImage;
use App\Models\TransLatLong;
use App\Models\TransPop;
use App\Models\TransCoreJoinInfo;
use App\Models\TransTjBox;
use App\Models\TransWorkerInfo;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransBackboneTjBoxController extends Controller
{
    // Backbone Tj Box list ---
    public function transBackboneTjBoxList(Request $request, $auth_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $base_role = User::where('id', $auth_id)->value('base_role');
        if($base_role === 'agent') {
            $zone_id = CorporateAgent::where('uid', $auth_id)->value('client_id');
        }elseif($base_role === 'sub_agent') {
            $zone_id = CorporateSubAgent::where('uid', $auth_id)->value('client_id');
        }else {
            $zone_id = InternetUsers::where('uid', $auth_id)->value('zone_id');
        }
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;
        if (!$zone_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Zone not found!";
            return ResponseWrapper::End($returned_data);
        }

        $query = TransTjBox::query();
        if($base_role !== 'admin'){
            $query = $query->where('trans_tj_boxes.zone_id', $zone_id);
        }
        $query = $query->where('tj_box_type','backbone_tj')->leftJoin('trans_pops', 'trans_tj_boxes.pop_id', '=', 'trans_pops.id')
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

    // Backbone Tj Box Single Details ---
    public function transBackboneTjBoxDetails($auth_id, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $base_role = User::where('id', $auth_id)->value('base_role');
        if($base_role === 'agent') {
            $zone_id = CorporateAgent::where('uid', $auth_id)->value('client_id');
        }elseif($base_role === 'sub_agent') {
            $zone_id = CorporateSubAgent::where('uid', $auth_id)->value('client_id');
        }else {
            $zone_id = InternetUsers::where('uid', $auth_id)->value('zone_id');
        }
        if (!$zone_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Zone not found!";
            return ResponseWrapper::End($returned_data);
        }

        // Fetch the transBackboneTjBox record along with related data
        $transBackboneTjBox = TransTjBox::query();
        if($base_role !== 'admin'){
            $transBackboneTjBox = $transBackboneTjBox->where('trans_tj_boxes.zone_id', $zone_id);
        }
        $transBackboneTjBox = $transBackboneTjBox->where('trans_tj_boxes.id', $id)
                            ->leftJoin('trans_pops', 'trans_tj_boxes.pop_id', '=', 'trans_pops.id')
                            ->with([
                                'backboneCableDetails',
                                'backboneCoreJoinInfo',
                                'backboneWorkerInfos',
                                'backboneImages',
                                'backboneLatLong'
                            ])
                            ->select(
                                'trans_tj_boxes.*',
                                'trans_pops.pop_code',  // Included in the joined select
                                'trans_pops.pop_type',   // Included in the joined select
                            )
                            ->first();

        if ($transBackboneTjBox) {
            $details = [
                'id' => $transBackboneTjBox->id,
                'pop_code' => TransPop::where('id',$transBackboneTjBox->pop_id)->value('pop_code'),
                'tj_box_code' => $transBackboneTjBox->tj_box_code,
                'tj_box_type' => $transBackboneTjBox->tj_box_type,
                'parent_tj_box_code' => TransTjBox::where('id',$transBackboneTjBox->parent_tj_box_id)->value('tj_box_code'),
                'latitude' => $transBackboneTjBox->latitude,
                'longitude' => $transBackboneTjBox->longitude,
                'address_direction' => $transBackboneTjBox->address_direction,
                'added_by_uid' => $transBackboneTjBox->added_by_uid,
                'updated_by_uid' => $transBackboneTjBox->updated_by_uid,
                'comments' => $transBackboneTjBox->comments,
                'status' => $transBackboneTjBox->status,
                'created_at' => $transBackboneTjBox->created_at,
                'updated_at' => $transBackboneTjBox->updated_at,

                'out_cable_details' => $transBackboneTjBox->backboneCableDetails->map(function ($cableDetail) {
                    if($cableDetail->cable_type === 'out_cable'){
                        return [
                            'id' => $cableDetail->id,
                            'cable_type' => $cableDetail->cable_type,
                            'fiber_code' => $cableDetail->fiber_code,
                            'fiber_core' => $cableDetail->fiber_core,
                            'start_fiber_meter' => $cableDetail->start_fiber_meter,
                            'end_fiber_meter' => $cableDetail->end_fiber_meter,
                            'fiber_length' => $cableDetail->fiber_length
                        ];
                    }
                })->filter()->values()->toArray(),

                'in_cable_details' => $transBackboneTjBox->backboneCableDetails->map(function ($cableDetail) {
                    if($cableDetail->cable_type === 'in_cable'){
                        return [
                            'id' => $cableDetail->id,
                            'cable_type' => $cableDetail->cable_type,
                            'fiber_code' => $cableDetail->fiber_code,
                            'fiber_core' => $cableDetail->fiber_core,
                            'start_fiber_meter' => $cableDetail->start_fiber_meter,
                            'end_fiber_meter' => $cableDetail->end_fiber_meter,
                            'fiber_length' => $cableDetail->fiber_length
                        ];
                    }
                })->filter()->values()->toArray(),

                'core_join_infos' => $transBackboneTjBox->backboneCoreJoinInfo->map(function ($joinInfos) {
                    return [
                        'id' => $joinInfos->id,
                        'trans_id' => $joinInfos->trans_id,
                        'module_type' => $joinInfos->module_type,
                        'in_fiber_id' => TransCableDetail::where('id', $joinInfos->in_fiber_id)->value('fiber_code'),
                        'out_fiber_id' => TransCableDetail::where('id', $joinInfos->out_fiber_id)->value('fiber_code'),
                        'joining_core_color' => $joinInfos->joining_core_color,
                        'db_signal' => $joinInfos->db_signal
                    ];
                })->filter()->values()->toArray(),

                'images' => $transBackboneTjBox->backboneImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'trans_id' => $image->trans_id,
                        'module_type' => $image->module_type,
                        'image' => $image->image
                    ];
                })->toArray(),

                'worker_info' => $transBackboneTjBox->backboneWorkerInfos->map(function ($workerInfo) {
                    return [
                        'id' => $workerInfo->id,
                        'added_by_name' => $workerInfo->added_by_name,
                        'mobile_number' => $workerInfo->mobile_number,
                        'work_type' => $workerInfo->work_type,
                        'updated_at' => $workerInfo->updated_at,
                    ];
                })->toArray(),
            ];

            $returned_data['status'] = 'success';
            $returned_data['results']['details'] = [$details];
            $returned_data['message'] = '';
        } else {
            $returned_data['status'] = 'error';
            $returned_data['results']['details'] = '';
            $returned_data['message'] = 'Id Not Match!';
        }

        return ResponseWrapper::End($returned_data);
    }

    // Create Backbone Tj Box ---
    public function createTransBackboneTjBox(Request $request, $auth_id) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();
            $validated = $request->validate([
                'tj_box_id' => 'required',
                'parent_pop_id' => 'required',
                // 'parent_tj_box_id' => 'required',

                'latitude' => 'required',
                'longitude' => 'required',
                // 'address_direction' => 'required',

                'in_fiber_information.[*].in_fiber_id' => 'required',
                'in_fiber_information.[*].in_fiber_core' => 'required',
                'in_fiber_information.[*].in_cable_start_meter' => 'required',
                'in_fiber_information.[*].in_cable_end_meter' => 'required',
                'in_fiber_information.[*].in_cable_length' => 'required',

                'out_fiber_information.[*].out_fiber_id' => 'required',
                'out_fiber_information.[*].out_fiber_core' => 'required',
                'out_fiber_information.[*].out_fiber_start_meter' => 'required',
                'out_fiber_information.[*].out_fiber_end_meter' => 'required',

                'core_join_information.[*].in_fiber_id' => 'required',
                'core_join_information.[*].out_fiber_id' => 'required',
                'core_join_information.[*].joining_core_color' => 'required',
                'core_join_information.[*].db_signal' => 'required',

                'worker_name' => 'required',
                'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'work_type' => 'required',
                // 'comments' => 'required',
            ]);

            if(!$validated){
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Validation Failed!";
                return ResponseWrapper::End($returned_data);
            }

            $base_role = User::where('id', $auth_id)->value('base_role');
            if($base_role === 'agent') {
                $zone_id = CorporateAgent::where('uid', $auth_id)->value('client_id');
            }elseif($base_role === 'sub_agent') {
                $zone_id = CorporateSubAgent::where('uid', $auth_id)->value('client_id');
            }else {
                $zone_id = InternetUsers::where('uid', $auth_id)->value('zone_id');
            }
            if (!$zone_id) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Zone not found!";
                return ResponseWrapper::End($returned_data);
            }

            $parentPopCode = TransPop::where('pop_code',$request->get('parent_pop_id'))->exists();
            if(!$parentPopCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No Branch Pop found according to your Parent Pop Code!';
                return ResponseWrapper::End($returned_data);
            }

            if($request->get('parent_tj_box_id')){
                $parentTjBoxCode = TransTjBox::where('tj_box_code',$request->get('parent_tj_box_id'))->exists();
                if(!$parentTjBoxCode){
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'No Tj Box found according to your Parent Tj Box Code!';
                    return ResponseWrapper::End($returned_data);
                }
            }

            $checkTjBoxCode = TransTjBox::where('tj_box_code',$request->get('tj_box_id'))->exists();
            if($checkTjBoxCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'This tj-box id already in use!';
                return ResponseWrapper::End($returned_data);
            }

            $parentPopId = TransPop::where('pop_code', $request->get('parent_pop_id'))->value('id');
            $parentTjBoxId = TransTjBox::where('tj_box_code',$request->get('parent_tj_box_id'))->value('id');

            // create new profile
            $tjBox = new TransTjBox();
            $tjBox->zone_id = $zone_id;
            $tjBox->pop_id = $parentPopId;
            $tjBox->tj_box_code = $request->get('tj_box_id');
            $tjBox->tj_box_type = 'backbone_tj';
            $tjBox->parent_tj_box_id = $parentTjBoxId;
            $tjBox->latitude = $request->get('latitude');
            $tjBox->longitude = $request->get('longitude');
            $tjBox->address_direction = $request->get('address_direction');
            $tjBox->added_by_uid = $request->get('added_by_uid');
            $tjBox->updated_by_uid = $request->get('updated_by_uid');
            $tjBox->comments = $request->get('comments');
            $tjBox->status =  'Active';
            $tjBox->save();

            // Catching the pop id
            $tjId = TransTjBox::where('tj_box_code', $request->get('tj_box_id'))->value('id');

            // Pop Lat Long Info
            $address = new TransLatLong();
            $address->zone_id = $zone_id;
            $address->trans_id = $tjId;
            $address->module_type = 'backbone_tj';
            $address->division_id = TransPop::where('id', $parentPopId)->value('division_id');
            $address->district_id = TransPop::where('id', $parentPopId)->value('district_id');
            $address->upazila_id = TransPop::where('id', $parentPopId)->value('upazila_id');
            $address->union_id = TransPop::where('id', $parentPopId)->value('union_id');
            $address->latitude = $request->get('latitude');
            $address->longitude = $request->get('longitude');
            $address->status = 'Active';
            $address->save();

            // Multiple inCable
            $inCableData = $request->get('in_fiber_information');
            foreach ($inCableData as $inCable) {
                $inCableModel = new TransCableDetail();
                $inCableModel->zone_id = $zone_id;
                $inCableModel->trans_id = $tjId;
                $inCableModel->module_type = 'backbone_tj';
                $inCableModel->cable_type = 'in_cable';
                $inCableModel->fiber_code = $inCable['in_fiber_id'];
                $inCableModel->fiber_core = $inCable['in_fiber_core'];
                $inCableModel->start_fiber_meter = $inCable['in_fiber_start_meter'];
                $inCableModel->end_fiber_meter = $inCable['in_fiber_end_meter'];
                $inCableModel->fiber_length = $inCable['in_fiber_length'];
                $inCableModel->save();
            }

            // Multiple outCable
            $outCableData = $request->get('out_fiber_information');
            foreach ($outCableData as $outCable) {
                $outCableModel = new TransCableDetail();
                $outCableModel->zone_id = $zone_id;
                $outCableModel->trans_id = $tjId;
                $outCableModel->module_type = 'backbone_tj';
                $outCableModel->cable_type = 'out_cable';
                $outCableModel->fiber_code = $outCable['out_fiber_id'];
                $outCableModel->fiber_core = $outCable['out_fiber_core'];
                $outCableModel->start_fiber_meter = $outCable['out_fiber_start_meter'];
                $outCableModel->end_fiber_meter = $outCable['out_fiber_end_meter'];
                $outCableModel->fiber_length = $outCable['out_fiber_length'];
                $outCableModel->save();
            }

            // Multiple Core Join Info
            $coreJoinInfos = $request->get('core_join_information');
            foreach ($coreJoinInfos as $coreJoinInfo) {
                $coreJoin = new TransCoreJoinInfo();
                $coreJoin->zone_id = $zone_id;
                $coreJoin->trans_id = $tjId;
                $coreJoin->module_type = 'backbone_tj';
                $coreJoin->in_fiber_id = TransCableDetail::where('fiber_code',$coreJoinInfo['in_fiber_id'])->where('trans_id',$tjId)->where('cable_type','in_cable')->value('id') ?? null;
                $coreJoin->out_fiber_id = TransCableDetail::where('fiber_code',$coreJoinInfo['out_fiber_id'])->where('trans_id',$tjId)->where('cable_type','out_cable')->value('id') ?? null;
                $coreJoin->joining_core_color = $coreJoinInfo['joining_core_color'];
                $coreJoin->db_signal = $coreJoinInfo['db_signal'];
                $coreJoin->save();
            }

            $workerInfo = new TransWorkerInfo();
            $workerInfo->zone_id = $zone_id;
            $workerInfo->trans_id = $tjId;
            $workerInfo->module_type = 'backbone_tj';
            $workerInfo->added_by_name = $request->get('worker_name');
            $workerInfo->mobile_number = $request->get('worker_mobile');
            $workerInfo->work_type = $request->get('work_type');
            $workerInfo->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Backbone tj box added successfully!';
            $returned_data['results'] = $tjId;
            return ResponseWrapper::End($returned_data);
    }

    // Edit Backbone Tj Box ---
    public function editTransBackboneTjBox(Request $request, $auth_id, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'tj_box_id' => 'required',
            'parent_pop_id' => 'required',
            // 'parent_tj_box_id' => 'required',

            'latitude' => 'required',
            'longitude' => 'required',
            // 'address_direction' => 'required',

            'in_fiber_information.[*].in_fiber_id' => 'required',
            'in_fiber_information.[*].in_fiber_core' => 'required',
            'in_fiber_information.[*].in_cable_start_meter' => 'required',
            'in_fiber_information.[*].in_cable_end_meter' => 'required',
            'in_fiber_information.[*].in_cable_length' => 'required',

            'out_fiber_information.[*].out_fiber_id' => 'required',
            'out_fiber_information.[*].out_fiber_core' => 'required',
            'out_fiber_information.[*].out_fiber_start_meter' => 'required',
            'out_fiber_information.[*].out_fiber_end_meter' => 'required',

            'core_join_information.[*].in_fiber_id' => 'required',
            'core_join_information.[*].out_fiber_id' => 'required',
            'core_join_information.[*].joining_core_color' => 'required',
            'core_join_information.[*].db_signal' => 'required',

            'worker_name' => 'required',
            'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'work_type' => 'required',
            // 'comments' => 'required',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        $base_role = User::where('id', $auth_id)->value('base_role');
        if($base_role === 'agent') {
            $zone_id = CorporateAgent::where('uid', $auth_id)->value('client_id');
        }elseif($base_role === 'sub_agent') {
            $zone_id = CorporateSubAgent::where('uid', $auth_id)->value('client_id');
        }else {
            $zone_id = InternetUsers::where('uid', $auth_id)->value('zone_id');
        }
        if (!$zone_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Zone not found!";
            return ResponseWrapper::End($returned_data);
        }

        $parentPopCode = TransPop::where('pop_code',$request->get('parent_pop_id'))->exists();
        if(!$parentPopCode){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'No Branch Pop found according to your Parent Pop Code!';
            return ResponseWrapper::End($returned_data);
        }

        if($request->get('parent_tj_box_id')){
            $checkTjBoxCode = TransTjBox::where('tj_box_code',$request->get('parent_tj_box_id'))->exists();
            if(!$checkTjBoxCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No Tj Box found according to your Parent Tj Code!!';
                return ResponseWrapper::End($returned_data);
            }
        }

        $parentTjBoxId = TransTjBox::where('tj_box_code',$request->get('parent_tj_box_id'))->value('id');

        // Edit Pop - backbone_tj
        $TjBox = TransTjBox::where('id', $id)->first();
        if($TjBox){
            $TjBox->update([
                // 'zone_id' => $zone_id,
                // 'pop_id' => $parentPopId,
                // 'tj_box_code' => $request->get('tj_box_code'),
                'tj_box_type' => 'backbone_tj',
                'parent_tj_box_id' => $parentTjBoxId,
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'address_direction' => $request->get('address_direction'),
                // 'added_by_uid' => $request->get('added_by_uid'),
                'updated_by_uid' => $request->get('updated_by_uid'),
                'comments' => $request->get('comments'),
                'status' => 'Active',
            ]);
        }

        $address = TransLatLong::where('trans_id', $id)->first();
        if($address){
            $address->update([
                'zone_id' => $zone_id,
                'trans_id' => $id,
                'module_type' => 'backbone_tj',
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'status' => 'Active'
            ]);
        }

        $inCableData = $request->get('in_fiber_information');
        foreach($inCableData as $inCableItem){
            $cableId = $inCableItem['id'] ?? null;
            if($cableId){
                $inCableInfo = TransCableDetail::where('trans_id',$id)->where('cable_type','in_cable')->where('id',$inCableItem['id'])->first();
                $inCableInfo->update([
                    'zone_id' => $zone_id,
                    'trans_id' => $id,
                    'module_type' => 'backbone_tj',
                    'cable_type' => 'in_cable',
                    'fiber_code' => $inCableItem['in_fiber_id'],
                    'fiber_core' => $inCableItem['in_fiber_core'],
                    'start_fiber_meter' => $inCableItem['in_fiber_start_meter'],
                    'end_fiber_meter' => $inCableItem['in_fiber_end_meter'],
                    'fiber_length' => $inCableItem['in_fiber_length']
                ]);
            }else{
                TransCableDetail::create([
                    'zone_id' => $zone_id,
                    'trans_id' => $id,
                    'module_type' => 'backbone_tj',
                    'cable_type' => 'in_cable',
                    'fiber_code' => $inCableItem['in_fiber_id'],
                    'fiber_core' => $inCableItem['in_fiber_core'],
                    'start_fiber_meter' => $inCableItem['in_fiber_start_meter'],
                    'end_fiber_meter' => $inCableItem['in_fiber_end_meter'],
                    'fiber_length' => $inCableItem['in_fiber_length']
                ]);
            }
        }

        $outCableData = $request->get('out_fiber_information');
        foreach($outCableData as $outCableDataItem){
            $cableId = $outCableDataItem['id'] ?? null;
            if($cableId){
                $outCableInfo = TransCableDetail::where('trans_id', $id)->where('cable_type','out_cable')->where('id',$outCableDataItem['id'])->first();
                $outCableInfo->update([
                    'zone_id' => $zone_id,
                    'trans_id' => $id,
                    'module_type' => 'backbone_tj',
                    'cable_type' => 'out_cable',
                    'fiber_code' => $outCableDataItem['out_fiber_id'],
                    'fiber_core' => $outCableDataItem['out_fiber_core'],
                    'start_fiber_meter' => $outCableDataItem['out_fiber_start_meter'],
                    'end_fiber_meter' => $outCableDataItem['out_fiber_end_meter'],
                    'fiber_length' => $outCableDataItem['out_fiber_length']
                ]);
            }else{
                TransCableDetail::create([
                    'zone_id' => $zone_id,
                    'trans_id' => $id,
                    'module_type' => 'backbone_tj',
                    'cable_type' => 'out_cable',
                    'fiber_code' => $outCableDataItem['out_fiber_id'],
                    'fiber_core' => $outCableDataItem['out_fiber_core'],
                    'start_fiber_meter' => $outCableDataItem['out_fiber_start_meter'],
                    'end_fiber_meter' => $outCableDataItem['out_fiber_end_meter'],
                    'fiber_length' => $outCableDataItem['out_fiber_length']
                ]);
            }
        }

        $coreJoinInfos = $request->get('core_join_information');
        foreach ($coreJoinInfos as $coreJoinInfo) {
            $deviceId = $coreJoinInfo['id'] ?? null;
            if($deviceId){
                $coreJoin = TransCoreJoinInfo::where('trans_id', $id)->where('id', $coreJoinInfo['id'])->first();
                $coreJoin->update([
                    'zone_id' => $zone_id,
                    'trans_id' => $id,
                    'module_type' => 'backbone_tj',
                    'in_fiber_id' => TransCableDetail::where('fiber_code',$coreJoinInfo['in_fiber_id'])->where('trans_id',$id)->where('cable_type','in_cable')->value('id') ?? null,
                    'out_fiber_id' => TransCableDetail::where('fiber_code',$coreJoinInfo['out_fiber_id'])->where('trans_id',$id)->where('cable_type','out_cable')->value('id') ?? null,
                    'joining_core_color' => $coreJoinInfo['joining_core_color'],
                    'db_signal' => $coreJoinInfo['db_signal'],
                ]);
            }else{
                TransCoreJoinInfo::create([
                    'zone_id' => $zone_id,
                    'trans_id' => $id,
                    'module_type' => 'backbone_tj',
                    'in_fiber_id' => TransCableDetail::where('fiber_code',$coreJoinInfo['in_fiber_id'])->where('trans_id',$id)->where('cable_type','in_cable')->value('id') ?? null,
                    'out_fiber_id' => TransCableDetail::where('fiber_code',$coreJoinInfo['out_fiber_id'])->where('trans_id',$id)->where('cable_type','out_cable')->value('id') ?? null,
                    'joining_core_color' => $coreJoinInfo['joining_core_color'],
                    'db_signal' => $coreJoinInfo['db_signal'],
                ]);
            }
        }

        $workerInfo = TransWorkerInfo::where('trans_id', $id)->first();
        if($workerInfo){
            $workerInfo->update([
                'zone_id' => $zone_id,
                'trans_id' => $id,
                'module_type' => 'backbone_tj',
                'added_by_name' => $request->get('worker_name'),
                'mobile_number' => $request->get('worker_mobile'),
                'work_type' => $request->get('work_type'),
            ]);
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Backbone Tj Box data updated successfully!';
        return ResponseWrapper::End($returned_data);
    }

    // Delete Backbone Tj Box ---
    public function deleteTransBackboneTjBox($auth_id, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $base_role = User::where('id', $auth_id)->value('base_role');
        if($base_role === 'agent') {
            $zone_id = CorporateAgent::where('uid', $auth_id)->value('client_id');
        }elseif($base_role === 'sub_agent') {
            $zone_id = CorporateSubAgent::where('uid', $auth_id)->value('client_id');
        }else {
            $zone_id = InternetUsers::where('uid', $auth_id)->value('zone_id');
        }
        if (!$zone_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Zone not found!";
            return ResponseWrapper::End($returned_data);
        }

        $latLongDeleted = TransLatLong::query();
        if($base_role !== 'admin'){
            $latLongDeleted = $latLongDeleted->where('zone_id', $zone_id);
        }
        $latLongDeleted = $latLongDeleted->where('trans_id', $id)->where('module_type','backbone_tj')->delete();

        $joinInfoDeleted = TransCoreJoinInfo::query();
        if($base_role !== 'admin'){
            $joinInfoDeleted = $joinInfoDeleted->where('zone_id', $zone_id);
        }
        $joinInfoDeleted = $joinInfoDeleted->where('trans_id', $id)->where('module_type','backbone_tj')->delete();

        $cableDeleted = TransCableDetail::query();
        if($base_role !== 'admin'){
            $cableDeleted = $cableDeleted->where('zone_id', $zone_id);
        }
        $cableDeleted = $cableDeleted->where('trans_id', $id)->where('module_type','backbone_tj')->delete();

        $workerDeleted = TransWorkerInfo::query();
        if($base_role !== 'admin'){
            $workerDeleted = $workerDeleted->where('zone_id', $zone_id);
        }
        $workerDeleted = $workerDeleted->where('trans_id', $id)->where('module_type','backbone_tj')->delete();

        // Fetch all images related to the pop
        $images = TransImage::where('trans_id', $id)->where('zone_id', $zone_id)->where('module_type','backbone_tj')->get();

        // Attempt to delete each image from the directory
        foreach ($images as $image) {
            $imagePath = public_path($image->image);
            if (file_exists($imagePath)) {
                if (!unlink($imagePath)) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = "Failed to delete image: {$image->image}";
                    return ResponseWrapper::End($returned_data);
                }
            }
        }

        // Delete all images from the database
        $imageDeleted = TransImage::query();
        if($base_role !== 'admin'){
            $imageDeleted = $imageDeleted->where('zone_id', $zone_id);
        }
        $imageDeleted = $imageDeleted->where('trans_id', $id)->where('module_type','backbone_tj')->delete();

        $backboneTjDeleted = TransTjBox::query();
        if($base_role !== 'admin'){
            $backboneTjDeleted = $backboneTjDeleted->where('zone_id', $zone_id);
        }
        $backboneTjDeleted = $backboneTjDeleted->where('id', $id)->where('tj_box_type','backbone_tj')->delete();
        if ($latLongDeleted && $joinInfoDeleted && $cableDeleted && $workerDeleted && $backboneTjDeleted) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "Backbone Tj and associated images deleted successfully!";
        } else {
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Try again something went wrong!";
        }
        return ResponseWrapper::End($returned_data);
    }

    // Summary Tj Box ---
    public function summaryTransBackboneTjBox($auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $base_role = User::where('id', $auth_id)->value('base_role');
        if($base_role === 'agent') {
            $zone_id = CorporateAgent::where('uid', $auth_id)->value('client_id');
        }elseif($base_role === 'sub_agent') {
            $zone_id = CorporateSubAgent::where('uid', $auth_id)->value('client_id');
        }else {
            $zone_id = InternetUsers::where('uid', $auth_id)->value('zone_id');
        }
        if (!$zone_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Zone not found!";
            return ResponseWrapper::End($returned_data);
        }

        // summary
        $summary = TransTjBox::query();
        if($base_role !== 'admin'){
            $summary = $summary->where('zone_id', $zone_id);
        }
        $summary = $summary->where('tj_box_type','backbone_tj')->selectRaw(
            'COUNT(trans_tj_boxes.id) AS total,
             COUNT(CASE WHEN trans_tj_boxes.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_tj_boxes.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    // Get Backbone Tj Box Lat-Long
    public function getTransBackboneTjBoxLatLong(Request $request, $auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $base_role = User::where('id', $auth_id)->value('base_role');
        if($base_role === 'agent') {
            $zone_id = CorporateAgent::where('uid', $auth_id)->value('client_id');
        }elseif($base_role === 'sub_agent') {
            $zone_id = CorporateSubAgent::where('uid', $auth_id)->value('client_id');
        }else {
            $zone_id = InternetUsers::where('uid', $auth_id)->value('zone_id');
        }
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;
        if (!$zone_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Zone not found!";
            return ResponseWrapper::End($returned_data);
        }

        $query = TransLatLong::query();
        if($base_role !== 'admin'){
            $query = $query->where('trans_lat_longs.zone_id', $zone_id);
        }
        $query->leftJoin('trans_tj_boxes','trans_tj_boxes.id', '=', 'trans_lat_longs.trans_id');
        $query->where('module_type','backbone_tj');

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

    // Bulk Upload ---
    public function bulkUploadTransBackboneTjBox(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Validate file upload
        $validated = $request->validate([
            'file' => 'required|mimes:xlsx',
        ], [
            'file.required' => 'Please upload a file.',
            'file.mimes' => 'The file must be a valid XLSX file.',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Handle the file upload and processing
        try {
            Excel::import(new TransBackboneTjBoxImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }
}
