<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Imports\TransDistributionLoopImport;
use App\Models\InternetUsers;
use App\Models\TransCableDetail;
use App\Models\TransImage;
use App\Models\TransLatLong;
use App\Models\TransLoop;
use App\Models\TransPop;
use App\Models\TransTjBox;
use App\Models\TransWorkerInfo;
use App\Models\User;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TransDistributionLoopController extends Controller
{
    // Distribution Loop list ---
    public function transDistributionLoopList(Request $request, $auth_id) : JsonResponse
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

        $query = TransLoop::query();
        if($base_role !== 'admin'){
            $query = $query->where('trans_loops.zone_id', $zone_id);
        }
        $query = $query->where('loop_type','distribution_loop')
        // ->leftJoin('trans_pops', 'trans_loops.pop_id', '=', 'trans_pops.id')
        // ->leftJoin('trans_tj_boxes', 'trans_loops.pop_id', '=', 'trans_tj_boxes.id')
        ->orderBy('trans_loops.id', 'desc')
        ->get([
            'trans_loops.id',
            'trans_loops.pop_id',
            DB::raw('(SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_loops.pop_id) as parent_pop_code'),
            'trans_loops.tj_box_id',
            DB::raw('(SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_loops.tj_box_id) as parent_tj_box_code'),
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

    // Distribution Loop Single Details ---
    public function transDistributionLoopDetails($auth_id, $id) : JsonResponse
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

        // Fetch the transDistributionLoop record along with related data
        $transDistributionLoop = TransLoop::query();
        if($base_role !== 'admin'){
            $transDistributionLoop = $transDistributionLoop->where('trans_pops.zone_id', $zone_id);
        }
        $transDistributionLoop = $transDistributionLoop->where('trans_loops.id', $id)
                            ->leftJoin('trans_pops', 'trans_loops.pop_id', '=', 'trans_pops.id')
                            ->leftJoin('trans_tj_boxes', 'trans_loops.tj_box_id', '=', 'trans_tj_boxes.id')
                            ->with([
                                'distributionCableDetails',
                                'distributionWorkerInfos',
                                'distributionImages',
                                'distributionLatLong'
                            ])
                            ->select(
                                'trans_loops.*',
                                'trans_pops.pop_code',  // Included in the joined select
                                'trans_pops.pop_type',   // Included in the joined select
                                'trans_tj_boxes.tj_box_code',  // Included in the joined select
                                'trans_tj_boxes.tj_box_type',   // Included in the joined select
                            )
                            ->first();

        if ($transDistributionLoop) {
            $details = [
                'id' => $transDistributionLoop->id,
                'pop_id' => $transDistributionLoop->pop_id,
                'tj_box_id' => $transDistributionLoop->tj_box_id,
                'olt_port' => $transDistributionLoop->olt_port,
                'pop_code' => TransPop::where('id',$transDistributionLoop->pop_id)->value('pop_code'),
                'tj_box_code' => TransTjBox::where('id',$transDistributionLoop->tj_box_id)->value('tj_box_code'),
                'loop_code' => $transDistributionLoop->loop_code,
                'loop_type' => $transDistributionLoop->loop_type,
                'latitude' => $transDistributionLoop->latitude,
                'longitude' => $transDistributionLoop->longitude,
                'address_direction' => $transDistributionLoop->address_direction,
                'added_by_uid' => $transDistributionLoop->added_by_uid,
                'updated_by_uid' => $transDistributionLoop->updated_by_uid,
                'comments' => $transDistributionLoop->comments,
                'status' => $transDistributionLoop->status,
                'created_at' => $transDistributionLoop->created_at,
                'updated_at' => $transDistributionLoop->updated_at,

                'cable_details' => $transDistributionLoop->distributionCableDetails->map(function ($cableDetail) {
                    if($cableDetail->cable_type === 'distribution_loop_cable'){
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

                'images' => $transDistributionLoop->distributionImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'trans_id' => $image->trans_id,
                        'module_type' => $image->module_type,
                        'image' => $image->image
                    ];
                })->toArray(),

                'worker_info' => $transDistributionLoop->distributionWorkerInfos->map(function ($workerInfo) {
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

    // Create Distribution Loop ---
    public function createTransDistributionLoop(Request $request, $auth_id) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();
            $validated = $request->validate([
                'loop_id' => 'required',
                'parent_pop_id' => 'required',
                'olt_port' => 'required',
                // 'parent_tj_box_id' => 'required',

                'latitude' => 'required',
                'longitude' => 'required',
                'address_direction' => 'required',

                'fiber_id' => 'required',
                'fiber_core' => 'required',
                'looped_fiber_start_meter' => 'required',
                'looped_fiber_end_meter' => 'required',
                'looped_fiber_length' => 'required',

                'worker_name' => 'required',
                'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'work_type' => 'required'
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
                $returned_data['message'] = 'No Pop found according to your Parent Pop Code!';
                return ResponseWrapper::End($returned_data);
            }

            $checkLoopCode = TransLoop::where('loop_code',$request->get('loop_id'))->exists();
            if($checkLoopCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'This loop id already in use!';
                return ResponseWrapper::End($returned_data);
            }

            $parentPopId = TransPop::where('pop_code', $request->get('parent_pop_id'))->value('id');
            $parentTjBoxId = TransTjBox::where('tj_box_code',$request->get('parent_tj_box_id'))->value('id');

            // create new profile
            $loop = new TransLoop();
            $loop->zone_id = $zone_id;
            $loop->pop_id = $parentPopId;
            $loop->tj_box_id = $parentTjBoxId;
            $loop->olt_port = $request->get('olt_port');
            $loop->loop_code = $request->get('loop_id');
            $loop->loop_type = 'distribution_loop';
            $loop->latitude = $request->get('latitude');
            $loop->longitude = $request->get('longitude');
            $loop->address_direction = $request->get('address_direction');
            $loop->added_by_uid = $request->get('added_by_uid');
            $loop->updated_by_uid = $request->get('updated_by_uid');
            $loop->comments = $request->get('comments');
            $loop->status =  'Active';
            $loop->save();

            // Catching the pop id
            $loopId = TransLoop::where('loop_code', $request->get('loop_id'))->value('id');

            // Pop Lat Long Info
            $address = new TransLatLong();
            $address->zone_id = $zone_id;
            $address->trans_id = $loopId;
            $address->module_type = 'distribution_loop';
            $address->division_id = TransPop::where('id', $parentPopId)->value('division_id');
            $address->district_id = TransPop::where('id', $parentPopId)->value('district_id');
            $address->upazila_id = TransPop::where('id', $parentPopId)->value('upazila_id');
            $address->union_id = TransPop::where('id', $parentPopId)->value('union_id');
            $address->latitude = $request->get('latitude');
            $address->longitude = $request->get('longitude');
            $address->status = 'Active';
            $address->save();

            // In Cable
            $cable = new TransCableDetail();
            $cable->zone_id = $zone_id;
            $cable->trans_id = $loopId;
            $cable->module_type = 'distribution_loop';
            $cable->cable_type = 'distribution_loop_cable';
            $cable->fiber_code = $request->get('fiber_id');
            $cable->fiber_core = $request->get('fiber_core');
            $cable->start_fiber_meter = $request->get('looped_fiber_start_meter');
            $cable->end_fiber_meter = $request->get('looped_fiber_end_meter');
            $cable->fiber_length = $request->get('looped_fiber_length');
            $cable->save();

            // Worker Info
            $workerInfo = new TransWorkerInfo();
            $workerInfo->zone_id = $zone_id;
            $workerInfo->trans_id = $loopId;
            $workerInfo->module_type = 'distribution_loop';
            $workerInfo->added_by_name = $request->get('worker_name');
            $workerInfo->mobile_number = $request->get('worker_mobile');
            $workerInfo->work_type = $request->get('work_type');
            $workerInfo->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Distribution loop added successfully!';
            $returned_data['results'] = $loopId;
            return ResponseWrapper::End($returned_data);
    }

    // Edit Distribution Loop ---
    public function editTransDistributionLoop(Request $request, $auth_id, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'loop_id' => 'required',
            'parent_pop_id' => 'required',
            'olt_port' => 'required',
            // 'parent_tj_box_id' => 'required',

            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',

            'fiber_id' => 'required',
            'fiber_core' => 'required',
            'looped_fiber_start_meter' => 'required',
            'looped_fiber_end_meter' => 'required',
            'looped_fiber_length' => 'required',

            'worker_name' => 'required',
            'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'work_type' => 'required'
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
            $returned_data['message'] = 'No Pop found according to your Parent Pop Code!';
            return ResponseWrapper::End($returned_data);
        }

        $parentTjBoxId = TransTjBox::where('tj_box_code',$request->get('parent_tj_box_id'))->value('id');

        // Edit Distribution Loop
        $loop = TransLoop::where('id', $id)->first();
        if($loop){
            $loop->update([
                // 'zone_id' => $zone_id,
                // 'pop_id' => $parentPopId,
                'tj_box_id' => $parentTjBoxId,
                'olt_port' => $request->get('olt_port'),
                // 'loop_code' => $request->get('loop_code'),
                'loop_type' => 'distribution_loop',
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'address_direction' => $request->get('address_direction'),
                // 'added_by_uid' => $request->get('added_by_uid'),
                'updated_by_uid' => $request->get('updated_by_uid'),
                'comments' => $request->get('comments'),
                'status' => 'Active',
            ]);
        }

        $address = TransLatLong::where('trans_id', $id)->where('module_type','distribution_loop')->first();
        if($address){
            $address->update([
                'zone_id' => $zone_id,
                'trans_id' => $id,
                'module_type' => 'distribution_loop',
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'status' => 'Active'
            ]);
        }

        $cableInfo = TransCableDetail::where('trans_id',$id)->where('module_type','distribution_loop')->where('cable_type','distribution_loop_cable')->first();
        $cableInfo->update([
            'zone_id' => $zone_id,
            'trans_id' => $id,
            'module_type' => 'distribution_loop',
            'cable_type' => 'distribution_loop_cable',
            'fiber_code' => $request->get('fiber_id'),
            'fiber_core' => $request->get('fiber_core'),
            'start_fiber_meter' => $request->get('looped_fiber_start_meter'),
            'end_fiber_meter' => $request->get('looped_fiber_end_meter'),
            'fiber_length' => $request->get('looped_fiber_length')
        ]);

        $workerInfo = TransWorkerInfo::where('trans_id', $id)->where('module_type','distribution_loop')->first();
        if($workerInfo){
            $workerInfo->update([
                'zone_id' => $zone_id,
                'trans_id' => $id,
                'module_type' => 'distribution_loop',
                'added_by_name' => $request->get('worker_name'),
                'mobile_number' => $request->get('worker_mobile'),
                'work_type' => $request->get('work_type'),
            ]);
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Distribution loop data updated successfully!';
        return ResponseWrapper::End($returned_data);
    }

    // Delete Distribution Loop ---
    public function deleteTransDistributionLoop($auth_id, $id) : JsonResponse
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
        $latLongDeleted = $latLongDeleted->where('trans_id', $id)->where('module_type','distribution_loop')->delete();

        $cableDeleted = TransCableDetail::query();
        if($base_role !== 'admin'){
            $cableDeleted = $cableDeleted->where('zone_id', $zone_id);
        }
        $cableDeleted = $cableDeleted->where('trans_id', $id)->where('module_type','distribution_loop')->delete();

        $workerDeleted = TransWorkerInfo::query();
        if($base_role !== 'admin'){
            $workerDeleted = $workerDeleted->where('zone_id', $zone_id);
        }
        $workerDeleted = $workerDeleted->where('trans_id', $id)->where('module_type','distribution_loop')->delete();

        // Fetch all images related to the pop
        $images = TransImage::query();
        if($base_role !== 'admin'){
            $images = $images->where('zone_id', $zone_id);
        }
        $images = $images->where('trans_id', $id)->where('module_type','distribution_loop')->get();

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
        $imageDeleted = $imageDeleted->where('trans_id', $id)->where('module_type','distribution_loop')->delete();

        $loopDeleted = TransLoop::query();
        if($base_role !== 'admin'){
            $loopDeleted = $loopDeleted->where('zone_id', $zone_id);
        }
        $loopDeleted = $loopDeleted->where('id', $id)->where('loop_type','distribution_loop')->delete();
        if ($latLongDeleted && $cableDeleted && $workerDeleted && $loopDeleted) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "Distribution Loop and associated images deleted successfully!";
        } else {
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Try again something went wrong!";
        }
        return ResponseWrapper::End($returned_data);
    }

    // Summary Distribution Loop ---
    public function summaryTransDistributionLoop($auth_id) : JsonResponse {
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
        $summary = TransLoop::query();
        if($base_role !== 'admin'){
            $summary = $summary->where('zone_id', $zone_id);
        }
        $summary = $summary->where('loop_type','distribution_loop')->selectRaw(
            'COUNT(trans_loops.id) AS total,
             COUNT(CASE WHEN trans_loops.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_loops.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    // Get Distribution Loop Lat-Long
    public function getTransDistributionLoopLatLong(Request $request, $auth_id) : JsonResponse {
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
        $query->where('module_type','distribution_loop');
        $query->leftJoin('trans_loops','trans_loops.id', '=', 'trans_lat_longs.trans_id');

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

    // Bulk Upload ---
    public function bulkUploadTransDistributionLoop(Request $request) : JsonResponse
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
            Excel::import(new TransDistributionLoopImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }
}
