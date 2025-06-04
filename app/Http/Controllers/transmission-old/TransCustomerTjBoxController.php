<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Imports\TransCustomerTjBoxImport;
use App\Models\TransCableDetail;
use App\Models\TransCoreJoinInfo;
use App\Models\TransImage;
use App\Models\TransLatLong;
use App\Models\TransPop;
use App\Models\TransTjBox;
use App\Models\TransWorkerInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TransCustomerTjBoxController extends Controller
{
    // Customer Tj Box list ---
    public function transCustomerTjBoxList(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransTjBox::query();
        $query = $query->where('tj_box_type','customer_tj')->leftJoin('trans_pops', 'trans_tj_boxes.pop_id', '=', 'trans_pops.id')
        ->orderBy('trans_tj_boxes.id', 'desc')
        ->get([
            'trans_tj_boxes.id',
            'trans_pops.pop_code',
            'trans_tj_boxes.tj_box_code',
            'trans_tj_boxes.tj_box_type',
            DB::raw('(SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_tj_boxes.parent_tj_box_id) as parent_tj_box_code'),
            'trans_tj_boxes.customer_name',
            'trans_tj_boxes.customer_mobile',
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

    // Customer Tj Box Single Details ---
    public function transCustomerTjBoxDetails($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Fetch the transCustomerTjBox record along with related data
        $transCustomerTjBox = TransTjBox::leftJoin('trans_pops', 'trans_tj_boxes.pop_id', '=', 'trans_pops.id')
                            ->with([
                                'customerCableDetails',
                                'customerCoreJoinInfo',
                                'customerWorkerInfos',
                                'customerImages',
                                'customerLatLong'
                            ])
                            ->select(
                                'trans_tj_boxes.*',
                                'trans_pops.pop_code',  // Included in the joined select
                                'trans_pops.pop_type',   // Included in the joined select
                            )
                            ->where('trans_tj_boxes.id', $id)
                            ->first();

        if ($transCustomerTjBox) {
            $details = [
                'id' => $transCustomerTjBox->id,
                'pop_id' => $transCustomerTjBox->pop_id,
                'pop_code' => TransPop::where('id',$transCustomerTjBox->pop_id)->value('pop_code'),
                'tj_box_code' => $transCustomerTjBox->tj_box_code,
                'tj_box_type' => $transCustomerTjBox->tj_box_type,
                'olt_port' => $transCustomerTjBox->olt_port,
                'parent_tj_box_code' => TransTjBox::where('id',$transCustomerTjBox->parent_tj_box_id)->value('tj_box_code'),
                'customer_name' => $transCustomerTjBox->customer_name,
                'customer_mobile' => $transCustomerTjBox->customer_mobile,
                'latitude' => $transCustomerTjBox->latitude,
                'longitude' => $transCustomerTjBox->longitude,
                'address_direction' => $transCustomerTjBox->address_direction,
                'added_by_uid' => $transCustomerTjBox->added_by_uid,
                'updated_by_uid' => $transCustomerTjBox->updated_by_uid,
                'comments' => $transCustomerTjBox->comments,
                'status' => $transCustomerTjBox->status,
                'created_at' => $transCustomerTjBox->created_at,
                'updated_at' => $transCustomerTjBox->updated_at,

                'in_cable_details' => $transCustomerTjBox->customerCableDetails->map(function ($cableDetail) {
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

                'core_join_infos' => $transCustomerTjBox->customerCoreJoinInfo->map(function ($joinInfos) {
                    return [
                        'id' => $joinInfos->id,
                        'trans_id' => $joinInfos->trans_id,
                        'module_type' => $joinInfos->module_type,
                        'in_fiber_id' => TransCableDetail::where('id', $joinInfos->in_fiber_id)->value('fiber_code'),
                        'joining_core_color' => $joinInfos->joining_core_color,
                        'db_signal' => $joinInfos->db_signal
                    ];
                })->filter()->values()->toArray(),

                'images' => $transCustomerTjBox->customerImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'trans_id' => $image->trans_id,
                        'module_type' => $image->module_type,
                        'image' => $image->image
                    ];
                })->toArray(),

                'worker_info' => $transCustomerTjBox->customerWorkerInfos->map(function ($workerInfo) {
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

    // Create Customer Tj Box ---
    public function createTransCustomerTjBox(Request $request) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();
            $validated = $request->validate([
                'tj_box_id' => 'required',
                'parent_pop_id' => 'required',
                'olt_port' => 'required',
                // 'parent_tj_box_id' => 'required',

                'customer_name' => 'required',
                'customer_mobile' => 'required',

                'latitude' => 'required',
                'longitude' => 'required',
                'address_direction' => 'required',

                'in_fiber_id' => 'required',
                'in_fiber_core' => 'required',
                'in_fiber_start_meter' => 'required',
                'in_fiber_end_meter' => 'required',
                'in_fiber_length' => 'required',

                'core_join_in_fiber_id' => 'required',
                'core_join_joining_core_color' => 'required',
                'core_join_db_signal' => 'required',

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

            $parentPopCode = TransPop::where('pop_code',$request->get('parent_pop_id'))->exists();
            if(!$parentPopCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No Branch Pop found according to your Parent Pop Code!';
                return ResponseWrapper::End($returned_data);
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
            $tjBox->pop_id = $parentPopId;
            $tjBox->tj_box_code = $request->get('tj_box_id');
            $tjBox->tj_box_type = 'customer_tj';
            $tjBox->olt_port = $request->get('olt_port');
            $tjBox->parent_tj_box_id = $parentTjBoxId;
            $tjBox->customer_name = $request->get('customer_name');
            $tjBox->customer_mobile = $request->get('customer_mobile');
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
            $address->trans_id = $tjId;
            $address->module_type = 'customer_tj';
            $address->division_id = TransPop::where('id', $parentPopId)->value('division_id');
            $address->district_id = TransPop::where('id', $parentPopId)->value('district_id');
            $address->upazila_id = TransPop::where('id', $parentPopId)->value('upazila_id');
            $address->union_id = TransPop::where('id', $parentPopId)->value('union_id');
            $address->latitude = $request->get('latitude');
            $address->longitude = $request->get('longitude');
            $address->status = 'Active';
            $address->save();

            //inCable
            $inCableModel = new TransCableDetail();
            $inCableModel->trans_id = $tjId;
            $inCableModel->module_type = 'customer_tj';
            $inCableModel->cable_type = 'in_cable';
            $inCableModel->fiber_code = $request->get('in_fiber_id');
            $inCableModel->fiber_core = $request->get('in_fiber_core');
            $inCableModel->start_fiber_meter = $request->get('in_fiber_start_meter');
            $inCableModel->end_fiber_meter = $request->get('in_fiber_end_meter');
            $inCableModel->fiber_length = $request->get('in_fiber_length');
            $inCableModel->save();

            //Core Join Info
            $coreJoin = new TransCoreJoinInfo();
            $coreJoin->trans_id = $tjId;
            $coreJoin->module_type = 'customer_tj';
            $coreJoin->in_fiber_id = TransCableDetail::where('fiber_code',$request->get('core_join_in_fiber_id'))->where('trans_id',$tjId)->where('cable_type','in_cable')->value('id') ?? null;
            $coreJoin->joining_core_color = $request->get('core_join_joining_core_color');
            $coreJoin->db_signal = $request->get('core_join_db_signal');
            $coreJoin->save();

            $workerInfo = new TransWorkerInfo();
            $workerInfo->trans_id = $tjId;
            $workerInfo->module_type = 'customer_tj';
            $workerInfo->added_by_name = $request->get('worker_name');
            $workerInfo->mobile_number = $request->get('worker_mobile');
            $workerInfo->work_type = $request->get('work_type');
            $workerInfo->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Customer tj box added successfully!';
            $returned_data['results'] = $tjId;
            return ResponseWrapper::End($returned_data);
    }

    // Edit Customer Tj Box ---
    public function editTransCustomerTjBox(Request $request, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'tj_box_id' => 'required',
            'parent_pop_id' => 'required',
            'olt_port' => 'required',
            // 'parent_tj_box_id' => 'required',

            'customer_name' => 'required',
            'customer_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],

            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',

            'in_fiber_id' => 'required',
            'in_fiber_core' => 'required',
            'in_fiber_start_meter' => 'required',
            'in_fiber_end_meter' => 'required',
            'in_fiber_length' => 'required',

            'core_join_in_fiber_id' => 'required',
            'core_join_joining_core_color' => 'required',
            'core_join_db_signal' => 'required',

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

        $parentPopCode = TransPop::where('pop_code',$request->get('parent_pop_id'))->exists();
        if(!$parentPopCode){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'No Branch Pop found according to your Parent Pop Code!';
            return ResponseWrapper::End($returned_data);
        }

        $parentTjBoxId = TransTjBox::where('tj_box_code',$request->get('parent_tj_box_id'))->value('id');

        // Edit Pop - backbone_tj
        $TjBox = TransTjBox::where('id', $id)->first();
        if($TjBox){
            $TjBox->update([
                // 'pop_id' => $parentPopId,
                // 'tj_box_code' => $request->get('tj_box_code'),
                'tj_box_type' => 'customer_tj',
                'olt_port' => $request->get('olt_port'),
                'parent_tj_box_id' => $parentTjBoxId,
                'customer_name' => $request->get('customer_name'),
                'customer_mobile' => $request->get('customer_mobile'),
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
                'trans_id' => $id,
                'module_type' => 'customer_tj',
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'status' => 'Active'
            ]);
        }

        $inCableInfo = TransCableDetail::where('trans_id',$id)->where('module_type','customer_tj')->where('cable_type','in_cable')->first();
        if($inCableInfo){
            $inCableInfo->update([
                'trans_id' => $id,
                'module_type' => 'customer_tj',
                'cable_type' => 'in_cable',
                'fiber_code' => $request->get('in_fiber_id'),
                'fiber_core' => $request->get('in_fiber_core'),
                'start_fiber_meter' => $request->get('in_fiber_start_meter'),
                'end_fiber_meter' => $request->get('in_fiber_end_meter'),
                'fiber_length' => $request->get('in_fiber_length')
            ]);
        }

        $coreJoin = TransCoreJoinInfo::where('trans_id', $id)->where('module_type', 'customer_tj')->first();
        if($coreJoin){
            $coreJoin->update([
                'trans_id' => $id,
                'module_type' => 'customer_tj',
                'in_fiber_id' => TransCableDetail::where('fiber_code',$request->get('core_join_in_fiber_id'))->where('trans_id',$id)->where('cable_type','in_cable')->value('id') ?? null,
                'joining_core_color' => $request->get('core_join_joining_core_color'),
                'db_signal' => $request->get('core_join_db_signal'),
            ]);
        }

        $workerInfo = TransWorkerInfo::where('trans_id', $id)->first();
        if($workerInfo){
            $workerInfo->update([
                'trans_id' => $id,
                'module_type' => 'customer_tj',
                'added_by_name' => $request->get('worker_name'),
                'mobile_number' => $request->get('worker_mobile'),
                'work_type' => $request->get('work_type'),
            ]);
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Customer Tj Box data updated successfully!';
        return ResponseWrapper::End($returned_data);
    }

    // Delete Customer Tj Box ---
    public function deleteTransCustomerTjBox($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $latLongDeleted = TransLatLong::where('trans_id', $id)->where('module_type','customer_tj')->delete();
        $joinInfoDeleted = TransCoreJoinInfo::where('trans_id', $id)->where('module_type','customer_tj')->delete();
        $cableDeleted = TransCableDetail::where('trans_id', $id)->where('module_type','customer_tj')->delete();
        $workerDeleted = TransWorkerInfo::where('trans_id', $id)->where('module_type','customer_tj')->delete();

        // Fetch all images related to the pop
        $images = TransImage::where('trans_id', $id)->where('module_type','customer_tj')->get();

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
        $imageDeleted = TransImage::where('trans_id', $id)->where('module_type','customer_tj')->delete();
        $customerTjDeleted = TransTjBox::where('id', $id)->where('tj_box_type','customer_tj')->delete();
        if ($latLongDeleted && $joinInfoDeleted && $cableDeleted && $workerDeleted && $customerTjDeleted) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "Customer Tj and associated images deleted successfully!";
        } else {
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Try again something went wrong!";
        }
        return ResponseWrapper::End($returned_data);
    }

    // Summary Tj Box ---
    public function summaryTransCustomerTjBox() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // summary
        $summary = TransTjBox::where('tj_box_type','customer_tj')->selectRaw(
            'COUNT(trans_tj_boxes.id) AS total,
             COUNT(CASE WHEN trans_tj_boxes.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_tj_boxes.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    // Get Customer Tj Box Lat-Long
    public function getTransCustomerTjBoxLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransLatLong::query();
        $query->leftJoin('trans_tj_boxes','trans_tj_boxes.id', '=', 'trans_lat_longs.trans_id');
        $query->where('module_type','customer_tj');

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

    // Bulk Upload ---
    public function bulkUploadTransCustomerTjBox(Request $request) : JsonResponse
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
            Excel::import(new TransCustomerTjBoxImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }
}
