<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\TransCableDetail;
use App\Models\TransCompany;
use App\Models\TransLatLong;
use App\Models\TransPop;
use App\Models\TransPopDeviceInfo;
use App\Models\TransPopOutputDeviceInfo;
use App\Models\TransWorkerInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TransBranchPopImport;
use App\Models\TransImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Undefined;

class TransBranchPopController extends Controller
{
    // Transmission Branch pop list ---
    public function transBranchPopList(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransPop::query();
        $query = $query->where('pop_type', 'branch')
            ->leftJoin('trans_companies', 'trans_pops.company_id', '=', 'trans_companies.id')
            ->orderBy('trans_pops.id', 'desc')
            ->get([
                'trans_pops.id',
                'trans_pops.company_id',
                'trans_companies.company_name',
                'trans_pops.pop_code',
                'trans_pops.pop_type',
                DB::raw('(SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_pops.nttn_pop_id) as nttn_pop_id'),
                DB::raw('(SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_pops.backup_nttn_pop_id) as backup_nttn_pop_id'),
                'trans_pops.pop_main_type',
                'trans_pops.division_id',
                'trans_pops.district_id',
                'trans_pops.upazila_id',
                'trans_pops.union_id',
                'trans_pops.status',
                'trans_pops.created_at'
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

    // Transmission Branch pop list ---
    public function transBranchPops (Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $query = TransPop::query();
        $query = $query->where('pop_type', 'branch')->orderBy('trans_pops.id', 'desc')
            ->get([
                'trans_pops.id',
                'trans_pops.pop_code'
            ]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    // Transmission Branch pop list for tree ---
    public function transBranchPopsTree () : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $query = TransPop::query();
        $query = $query->where('pop_type', 'branch')->orderBy('trans_pops.id', 'desc')
            ->get([
                'trans_pops.id',
                'trans_pops.pop_code as name',
                'trans_pops.pop_type'
            ]);

        $formattedResults = $query->map(function ($item) {
            return [
                'name' => $item->name,
                'attributes' => [
                    'id' => $item->id,
                    'module_type' => $item->pop_type,
                ],
            ];
        });

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $formattedResults;

        return ResponseWrapper::End($returned_data);
    }

    // Transmission Single Pop Details ---
    public function transBranchPopDetails($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Fetch the TransPop record along with related data
        $transPop = TransPop::leftJoin('trans_companies', 'trans_pops.company_id', '=', 'trans_companies.id')
                            ->with([
                                'branchDeviceInfos',
                                'branchOutputDeviceInfos',
                                'branchCableDetails',
                                'branchCoreJoinInfo',
                                'branchWorkerInfos'
                            ])
                            ->select(
                                'trans_pops.*',
                                'trans_companies.company_name',  // Included in the joined select
                                'trans_companies.company_type',   // Included in the joined select
                            )
                            ->where('trans_pops.id', $id)
                            ->first();

        if ($transPop) {
            $nttn_pop_id = TransPop::where('id', $transPop->nttn_pop_id)->first();
            $details = [
                'id' => $transPop->id,
                'company_id' => $transPop->company_id,
                'company_name' => $transPop->company_name,
                'company_type' => $transPop->company_type,
                'pop_code' => $transPop->pop_code,
                'pop_type' => $transPop->pop_type,
                'pop_main_type' => $transPop->pop_main_type,
                'parent_pop_id' => $transPop->parent_pop_id,
                'nttn_pop_id' => $transPop->nttn_pop_id,
                'nttn_pop_code' => $nttn_pop_id->pop_code,
                'nttn_pop_lat' => $nttn_pop_id->latitude,
                'nttn_pop_long' => $nttn_pop_id->longitude,
                'backup_nttn_pop_id' => TransPop::where('id', $transPop->backup_nttn_pop_id)->value('pop_code'),
                'scr_id' => $transPop->scr_id,
                'db_signal' => $transPop->db_signal,
                'division_id' => $transPop->division_id,
                'district_id' => $transPop->district_id,
                'upazila_id' => $transPop->upazila_id,
                'union_id' => $transPop->union_id,
                'division_name' => GeoDivision::where('id', $transPop->division_id)->value('en_name') ,
                'district_name' => GeoDistrict::where('id', $transPop->district_id)->value('en_name'),
                'upazila_name' => GeoUpazila::where('id', $transPop->upazila_id)->value('en_name'),
                'union_name' => GeoUnionPouroshova::where('id', $transPop->union_id)->value('en_name'),
                'village_name' => $transPop->village_name,
                'address_direction' => $transPop->address_direction,
                'latitude' => $transPop->latitude,
                'longitude' => $transPop->longitude,
                'image' => $transPop->image,
                'added_by_uid' => $transPop->added_by_uid,
                'updated_by_uid' => $transPop->updated_by_uid,
                'comments' => $transPop->comments,
                'status' => $transPop->status,
                'created_at' => $transPop->created_at,
                'updated_at' => $transPop->updated_at,

                'out_cable_details' => $transPop->branchCableDetails->map(function ($cableDetail) {
                    if($cableDetail->cable_type === 'out_cable'){
                        return [
                            'id' => $cableDetail->id,
                            'cable_type' => $cableDetail->cable_type,
                            'fiber_code' => $cableDetail->fiber_code,
                            'fiber_core' => $cableDetail->fiber_core,
                            'core_capacity' => $cableDetail->core_capacity,
                            'start_fiber_meter' => $cableDetail->start_fiber_meter,
                            'end_fiber_meter' => $cableDetail->end_fiber_meter,
                            'fiber_length' => $cableDetail->fiber_length,
                            'joining_core_color' => $cableDetail->joining_core_color,
                            'db_signal' => $cableDetail->db_signal,
                            'connected_port_number' => $cableDetail->connected_port_number,
                        ];
                    }
                })->filter()->values()->toArray(),

                'in_cable_details' => $transPop->branchCableDetails->map(function ($cableDetail) {
                    if($cableDetail->cable_type === 'in_cable'){
                        return [
                            'id' => $cableDetail->id,
                            'cable_type' => $cableDetail->cable_type,
                            'fiber_code' => $cableDetail->fiber_code,
                            'fiber_core' => $cableDetail->fiber_core,
                            'core_capacity' => $cableDetail->core_capacity,
                            'start_fiber_meter' => $cableDetail->start_fiber_meter,
                            'end_fiber_meter' => $cableDetail->end_fiber_meter,
                            'fiber_length' => $cableDetail->fiber_length,
                            'joining_core_color' => $cableDetail->joining_core_color,
                            'db_signal' => $cableDetail->db_signal,
                            'connected_port_number' => $cableDetail->connected_port_number,
                        ];
                    }
                })->filter()->values()->toArray(),

                'pop_input_device_info' => $transPop->branchDeviceInfos->map(function ($deviceInfo) {
                    return [
                        'id' => $deviceInfo->id,
                        'sfp_brand_name' => $deviceInfo->sfp_brand_name,
                        'sfp_type' => $deviceInfo->sfp_type,
                        'sfp_capacity' => $deviceInfo->sfp_capacity,
                        'input_device_port_type' => $deviceInfo->input_device_port_type,
                        'port_capacity' => $deviceInfo->port_capacity,
                        'incoming_fiber_connected_port_number' => $deviceInfo->incoming_fiber_connected_port_number
                    ];
                })->toArray(),

                'pop_rak_info' => $transPop->branchDeviceInfos->map(function ($deviceInfo) {
                    return [
                        'id' => $deviceInfo->id,
                        'rak_brand_name' => $deviceInfo->rak_brand_name,
                        'rak_capacity' => $deviceInfo->rak_capacity,
                    ];
                })->toArray(),

                'pop_mk_device_info' => $transPop->branchDeviceInfos->map(function ($deviceInfo) {
                    return [
                        'id' => $deviceInfo->id,
                        'mk_brand_name' => $deviceInfo->mk_brand_name,
                        'mk_capacity' => $deviceInfo->mk_capacity,
                        'mk_port_number' => $deviceInfo->mk_port_number,
                        'mk_serial_no' => $deviceInfo->mk_serial_no,
                        'mk_device_id' => $deviceInfo->mk_device_id,
                        'mk_power_consumption' => $deviceInfo->mk_power_consumption,
                        'mk_mac_address' => $deviceInfo->mk_mac_address
                    ];
                })->toArray(),

                'pop_output_device_info' => $transPop->branchOutputDeviceInfos->map(function ($deviceInfo) {
                    return [
                        'id' => $deviceInfo->id,
                        'output_device_type' => $deviceInfo->output_device_type,
                        'output_device_port_type' => $deviceInfo->output_device_port_type,
                        'output_device_port_number' => $deviceInfo->output_device_port_number,
                        'output_device_brand_name' => $deviceInfo->output_device_brand_name,
                        'output_device_connection_capacity' => $deviceInfo->output_device_connection_capacity,
                        'output_device_serial_no' => $deviceInfo->output_device_serial_no,
                        'output_device_id' => $deviceInfo->output_device_id,
                        'output_device_power_consumption' => $deviceInfo->output_device_power_consumption,
                    ];
                })->filter()->values()->toArray(),

                'images' => $transPop->branchImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'trans_id' => $image->trans_id,
                        'module_type' => $image->module_type,
                        'image' => $image->image
                    ];
                })->toArray(),

                'worker_info' => $transPop->branchWorkerInfos->map(function ($workerInfo) {
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

    // Create Transmission Pop ---
    public function createTransBranchPop(Request $request) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();
            $validated = $request->validate([
                'pop_id' => 'required',
                'pop_type' => 'required',
                'provider_id' => 'required',
                'nttn_pop_id' => 'required',

                'division' => 'required',
                'district' => 'required',
                'union' => 'required',
                'upazila' => 'required',
                'village' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'address_direction' => 'required',
                'added_by_uid' => 'required',
                'updated_by_uid' => 'required',

                'in_fiber_information.[*].in_fiber_id' => 'required',
                'in_fiber_information.[*].in_fiber_core' => 'required',
                'in_fiber_information.[*].in_cable_start_meter' => 'required',
                'in_fiber_information.[*].in_cable_end_meter' => 'required',
                'in_fiber_information.[*].in_cable_length' => 'required',
                'in_fiber_information.[*].in_fiber_joining_core_color' => 'required',
                'in_fiber_information.[*].db_signal' => 'required',

                'out_fiber_information.[*].out_fiber_id' => 'required',
                'out_fiber_information.[*].out_fiber_core' => 'required',
                'out_fiber_information.[*].out_fiber_start_meter' => 'required',
                'out_fiber_information.[*].out_fiber_end_meter' => 'required',
                'out_fiber_information.[*].out_fiber_length' => 'required',
                'out_fiber_information.[*].out_fiber_connected_port_number' => 'required',

                'input_sfp_mc_model' => 'required',
                'input_device_port_type' => 'required',
                'incoming_fiber_connected_port_number' => 'required',

                'output_device_information.[*].output_device' => 'required',
                'output_device_information.[*].output_device_port_number' => 'required',
                'output_device_information.[*].output_device_model' => 'required',
                'output_device_information.[*].connection_capacity' => 'required',
                'output_device_information.[*].serial_no' => 'required',
                'output_device_information.[*].device_id' => 'required',
                'output_device_information.[*].power_consumption' => 'required',

                'worker_name' => 'required',
                'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'work_type' => 'required',
            ]);

            if(!$validated){
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Validation Failed!";
                return ResponseWrapper::End($returned_data);
            }

            $providerId = TransCompany::where('id',$request->get('provider_id'))->exists();
            if(!$providerId){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No Company found according to your provider Id!';
                return ResponseWrapper::End($returned_data);
            }

            $nttnPopCode = TransPop::where('pop_code',$request->get('nttn_pop_id'))->where('pop_type','nttn')->exists();
            if(!$nttnPopCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No NTTN Pop found according to your NTTN Pop Code!';
                return ResponseWrapper::End($returned_data);
            }

            if($request->get('backup_nttn_pop_id')){
                $backupNttnPopCode = TransPop::where('pop_code',$request->get('backup_nttn_pop_id'))->where('pop_type','nttn')->exists();
                if(!$backupNttnPopCode){
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'No Backup NTTN Pop found according to your NTTN Pop Code!';
                    return ResponseWrapper::End($returned_data);
                }
            }

            $checkPopCode = TransPop::where('pop_code',$request->get('pop_id'))->exists();
            if($checkPopCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'This pop id already in use!';
                return ResponseWrapper::End($returned_data);
            }

            // create new profile
            $pop = new TransPop();
            $pop->company_id = $request->get('provider_id');
            $pop->pop_code = $request->get('pop_id');
            $pop->pop_type = 'branch';
            $pop->pop_main_type = $request->get('pop_type');
            $pop->nttn_pop_id  = TransPop::where('pop_code', $request->get('nttn_pop_id'))->value('id');
            $pop->backup_nttn_pop_id   = TransPop::where('pop_code', $request->get('backup_nttn_pop_id'))->value('id');
            $pop->scr_id = $request->get('scr_id');
            $pop->division_id = $request->get('division');
            $pop->district_id = $request->get('district');
            $pop->upazila_id = $request->get('upazila');
            $pop->union_id = $request->get('union');
            $pop->village_name = $request->get('village');
            $pop->address_direction = $request->get('address_direction');
            $pop->latitude = $request->get('latitude');
            $pop->longitude = $request->get('longitude');
            $pop->added_by_uid = $request->get('added_by_uid');
            $pop->updated_by_uid = $request->get('updated_by_uid');
            $pop->comments = $request->get('comments');
            $pop->status = 'Active';
            $pop->save();

            // Catching the pop id
            $popId = TransPop::where('pop_code', $request->get('pop_id'))->value('id');

            // Pop Lat Long Info
            $address = new TransLatLong();
            $address->trans_id = $popId;
            $address->module_type = 'branch';
            $address->division_id = $request->get('division');
            $address->district_id = $request->get('district');
            $address->upazila_id = $request->get('upazila');
            $address->union_id = $request->get('union');
            $address->latitude = $request->get('latitude');
            $address->longitude = $request->get('longitude');
            $address->status = 'Active';
            $address->save();

            // Multiple inCable
            $inCableData = $request->get('in_fiber_information');
            if($inCableData){
                foreach ($inCableData as $inCable) {
                    $inCableModel = new TransCableDetail();
                    $inCableModel->trans_id = $popId;
                    $inCableModel->module_type = 'branch';
                    $inCableModel->cable_type = 'in_cable';
                    $inCableModel->fiber_code = $inCable['in_fiber_id'];
                    $inCableModel->fiber_core = $inCable['in_fiber_core'];
                    $inCableModel->start_fiber_meter = $inCable['in_fiber_start_meter'];
                    $inCableModel->end_fiber_meter = $inCable['in_fiber_end_meter'];
                    $inCableModel->fiber_length = $inCable['in_fiber_length'];
                    $inCableModel->joining_core_color = $inCable['in_fiber_joining_core_color'];
                    $inCableModel->db_signal = $inCable['db_signal'];
                    $inCableModel->save();
                }
            }
            

            // Multiple outCable
            $outCableData = $request->get('out_fiber_information');
            if($outCableData){
                foreach ($outCableData as $outCable) {
                    $outCableModel = new TransCableDetail();
                    $outCableModel->trans_id = $popId;
                    $outCableModel->module_type = 'branch';
                    $outCableModel->cable_type = 'out_cable';
                    $outCableModel->fiber_code = $outCable['out_fiber_id'];
                    $outCableModel->fiber_core = $outCable['out_fiber_core'];
                    $outCableModel->start_fiber_meter = $outCable['out_fiber_start_meter'];
                    $outCableModel->end_fiber_meter = $outCable['out_fiber_end_meter'];
                    $outCableModel->fiber_length = $outCable['out_fiber_length'];
                    $outCableModel->connected_port_number = $outCable['out_fiber_connected_port_number'];
                    $outCableModel->save();
                }
            }

            // Pop Device Info
            $popDeviceInfo = new TransPopDeviceInfo();
            $popDeviceInfo->trans_id = $popId;
            $popDeviceInfo->module_type = 'branch';
            $popDeviceInfo->sfp_brand_name = $request->get('input_sfp_mc_model');
            $popDeviceInfo->sfp_type = $request->get('input_sfp_type');
            $popDeviceInfo->sfp_capacity = $request->get('input_sfp_capacity');
            $popDeviceInfo->input_device_port_type = $request->get('input_device_port_type');
            // $popDeviceInfo->port_capacity = $request->get('');
            $popDeviceInfo->incoming_fiber_connected_port_number = $request->get('incoming_fiber_connected_port_number');
            $popDeviceInfo->mk_brand_name = $request->get('mikrotik_model');
            $popDeviceInfo->mk_capacity = $request->get('mikrotik_capacity');
            $popDeviceInfo->mk_port_number = $request->get('mikrotik_port_number');
            $popDeviceInfo->mk_serial_no = $request->get('mikrotik_serial_no');
            $popDeviceInfo->mk_device_id = $request->get('mikrotik_device_id');
            $popDeviceInfo->mk_power_consumption = $request->get('mikrotik_power_consumption');
            $popDeviceInfo->mk_mac_address = $request->get('mikrotik_mac');
            $popDeviceInfo->rak_brand_name = $request->get('rak_model');
            $popDeviceInfo->rak_capacity = $request->get('rak_capacity');
            $popDeviceInfo->save();

            // Multiple Pop Out Put Device
            $popOutputDeviceInfos = $request->get('output_device_information');
            if($popOutputDeviceInfos){
                foreach ($popOutputDeviceInfos as $popOutputDeviceInfo) {
                    $popOutputModel = new TransPopOutputDeviceInfo();
                    $popOutputModel->trans_id = $popId;
                    $popOutputModel->module_type = 'branch';
                    $popOutputModel->output_device_type = $popOutputDeviceInfo['output_device'];
                    $popOutputModel->output_device_port_type = $popOutputDeviceInfo['output_device_port_type'];
                    $popOutputModel->output_device_port_number = $popOutputDeviceInfo['output_device_port_number'];
                    $popOutputModel->output_device_brand_name = $popOutputDeviceInfo['output_device_model'];
                    $popOutputModel->output_device_connection_capacity = $popOutputDeviceInfo['connection_capacity'];
                    $popOutputModel->output_device_serial_no = $popOutputDeviceInfo['serial_no'];
                    $popOutputModel->output_device_id = $popOutputDeviceInfo['device_id'];
                    $popOutputModel->output_device_power_consumption = $popOutputDeviceInfo['power_consumption'];
                    $popOutputModel->save();
                }
            }
            

            $workerInfo = new TransWorkerInfo();
            $workerInfo->trans_id = $popId;
            $workerInfo->module_type = 'branch';
            $workerInfo->added_by_name = $request->get('worker_name');
            $workerInfo->mobile_number = $request->get('worker_mobile');
            $workerInfo->work_type = $request->get('work_type');
            $workerInfo->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Branch pop added successfully!';
            $returned_data['results'] = $popId;
            return ResponseWrapper::End($returned_data);
    }

    // Edit Transmission Branch Pop ---
    public function editTransBranchPop(Request $request, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'pop_id' => 'required',
            'pop_type' => 'required',
            'provider_id' => 'required',
            'nttn_pop_id' => 'required',

            'division' => 'required',
            'district' => 'required',
            'union' => 'required',
            'upazila' => 'required',
            'village' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',
            'added_by_uid' => 'required',
            'updated_by_uid' => 'required',

            'in_fiber_information.[*].in_fiber_id' => 'required',
            'in_fiber_information.[*].in_fiber_core' => 'required',
            'in_fiber_information.[*].in_cable_start_meter' => 'required',
            'in_fiber_information.[*].in_cable_end_meter' => 'required',
            'in_fiber_information.[*].in_cable_length' => 'required',
            'in_fiber_information.[*].in_fiber_joining_core_color' => 'required',
            'in_fiber_information.[*].db_signal' => 'required',

            'out_fiber_information.[*].out_fiber_id' => 'required',
            'out_fiber_information.[*].out_fiber_core' => 'required',
            'out_fiber_information.[*].out_fiber_start_meter' => 'required',
            'out_fiber_information.[*].out_fiber_end_meter' => 'required',
            'out_fiber_information.[*].out_fiber_length' => 'required',
            'out_fiber_information.[*].out_fiber_connected_port_number' => 'required',

            'input_sfp_mc_model' => 'required',
            'input_device_port_type' => 'required',
            'incoming_fiber_connected_port_number' => 'required',

            'output_device_information.[*].output_device' => 'required',
            'output_device_information.[*].output_device_port_number' => 'required',
            'output_device_information.[*].output_device_model' => 'required',
            'output_device_information.[*].connection_capacity' => 'required',
            'output_device_information.[*].serial_no' => 'required',
            'output_device_information.[*].device_id' => 'required',
            'output_device_information.[*].power_consumption' => 'required',

            'worker_name' => 'required',
            'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'work_type' => 'required',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        $nttnPopCode = TransPop::where('pop_code',$request->get('nttn_pop_id'))->where('pop_type','nttn')->exists();
        if(!$nttnPopCode){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'No NTTN Pop found according to your NTTN Pop Code!';
            return ResponseWrapper::End($returned_data);
        }

        if($request->get('backup_nttn_pop_id')){
            $backupNttnPopCode = TransPop::where('pop_code',$request->get('backup_nttn_pop_id'))->where('pop_type','nttn')->exists();
            if(!$backupNttnPopCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No Backup NTTN Pop found according to your NTTN Pop Code!';
                return ResponseWrapper::End($returned_data);
            }
        }

        // Edit Pop
        $pop = TransPop::where('id', $id)->first();
        if($pop){
            $pop->update([
                'company_id' => $request->get('provider_id'),
                'pop_code' => $request->get('pop_id'),
                'pop_type' => 'branch',
                'pop_main_type' => $request->get('pop_type'),
                'nttn_pop_id' => TransPop::where('pop_code', $request->get('nttn_pop_id'))->value('id'),
                'backup_nttn_pop_id' => TransPop::where('pop_code', $request->get('backup_nttn_pop_id'))->value('id'),
                'scr_id' => $request->get('scr_id'),
                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'union_id' => $request->get('union'),
                'village_name' => $request->get('village'),
                'address_direction' => $request->get('address_direction'),
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'added_by_uid' => $request->get('added_by_uid'),
                'updated_by_uid' => $request->get('updated_by_uid'),
                'comments' => $request->get('comments'),
                'status' => 'Active',
            ]);
        }

        $address = TransLatLong::where('trans_id', $id)->first();
        if($address){
            $address->update([
                'trans_id' => $id,
                'module_type' => 'branch',
                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'union_id' => $request->get('union'),
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'status' => 'Active'
            ]);
        }else{
            $address = new TransLatLong();
            $address->trans_id = $id;
            $address->module_type = 'branch';
            $address->division_id = $request->get('division');
            $address->district_id = $request->get('district');
            $address->upazila_id = $request->get('upazila');
            $address->union_id = $request->get('union');
            $address->latitude = $request->get('latitude');
            $address->longitude = $request->get('longitude');
            $address->status = 'Active';
            $address->save();
        }

        $inCableData = $request->get('in_fiber_information');
        if($inCableData){
            foreach($inCableData as $inCableItem){
                $cableId = $inCableItem['id'] ?? null;
                if($cableId){
                    $inCableInfo = TransCableDetail::where('trans_id',$id)->where('cable_type','in_cable')->where('id',$inCableItem['id'])->first();
                    $inCableInfo->update([
                        'trans_id' => $id,
                        'module_type' => 'branch',
                        'cable_type' => 'in_cable',
                        'fiber_code' => $inCableItem['in_fiber_id'],  // Accessing data from loop item
                        'fiber_core' => $inCableItem['in_fiber_core'],
                        'start_fiber_meter' => $inCableItem['in_fiber_start_meter'],
                        'end_fiber_meter' => $inCableItem['in_fiber_end_meter'],
                        'fiber_length' => $inCableItem['in_fiber_length'],
                        'joining_core_color' => $inCableItem['in_fiber_joining_core_color'],
                        'db_signal' => $inCableItem['db_signal'],
                    ]);
                }else{
                    TransCableDetail::create([
                        'trans_id' => $id,
                        'module_type' => 'branch',
                        'cable_type' => 'in_cable',
                        'fiber_code' => $inCableItem['in_fiber_id'],  // Accessing data from loop item
                        'fiber_core' => $inCableItem['in_fiber_core'],
                        'start_fiber_meter' => $inCableItem['in_fiber_start_meter'],
                        'end_fiber_meter' => $inCableItem['in_fiber_end_meter'],
                        'fiber_length' => $inCableItem['in_fiber_length'],
                        'joining_core_color' => $inCableItem['in_fiber_joining_core_color'],
                        'db_signal' => $inCableItem['db_signal'],
                    ]);
                }
            }
        }
        

        $outCableData = $request->get('out_fiber_information');
        if($outCableData){
            foreach($outCableData as $outCableDataItem){
                $cableId = $outCableDataItem['id'] ?? null;
                if($cableId){
                    $outCableInfo = TransCableDetail::where('trans_id', $id)->where('cable_type','out_cable')->where('id',$outCableDataItem['id'])->first();
                    $outCableInfo->update([
                        'trans_id' => $id,
                        'module_type' => 'branch',
                        'cable_type' => 'out_cable',
                        'fiber_code' => $outCableDataItem['out_fiber_id'],
                        'fiber_core' => $outCableDataItem['out_fiber_core'],
                        'start_fiber_meter' => $outCableDataItem['out_fiber_start_meter'],
                        'end_fiber_meter' => $outCableDataItem['out_fiber_end_meter'],
                        'fiber_length' => $outCableDataItem['out_fiber_length'],
                        'connected_port_number' => $outCableDataItem['out_fiber_connected_port_number'],
                    ]);
                }else{
                    TransCableDetail::create([
                        'trans_id' => $id,
                        'module_type' => 'branch',
                        'cable_type' => 'out_cable',
                        'fiber_code' => $outCableDataItem['out_fiber_id'],
                        'fiber_core' => $outCableDataItem['out_fiber_core'],
                        'start_fiber_meter' => $outCableDataItem['out_fiber_start_meter'],
                        'end_fiber_meter' => $outCableDataItem['out_fiber_end_meter'],
                        'fiber_length' => $outCableDataItem['out_fiber_length'],
                        'connected_port_number' => $outCableDataItem['out_fiber_connected_port_number'],
                    ]);
                }
            }
        }
        

        $popDeviceInfo = TransPopDeviceInfo::where('trans_id', $id)->first();
        if($popDeviceInfo){
            $popDeviceInfo->update([
                'trans_id' => $id,
                'module_type' => 'branch',
                'sfp_brand_name' => $request->get('input_sfp_mc_model'),
                'sfp_type' => $request->get('input_sfp_type'),
                'sfp_capacity' => $request->get('input_sfp_capacity'),
                'input_device_port_type' => $request->get('input_device_port_type'),
                'incoming_fiber_connected_port_number' => $request->get('incoming_fiber_connected_port_number'),
                'mk_brand_name' => $request->get('mikrotik_model'),
                'mk_capacity' => $request->get('mikrotik_capacity'),
                'mk_port_number' => $request->get('mikrotik_port_number'),
                'mk_serial_no' => $request->get('mikrotik_serial_no'),
                'mk_device_id' => $request->get('mikrotik_device_id'),
                'mk_power_consumption' => $request->get('mikrotik_power_consumption'),
                'mk_mac_address' => $request->get('mikrotik_mac'),
                'rak_brand_name' => $request->get('rak_model'),
                'rak_capacity' => $request->get('rak_capacity'),
            ]);
        }else{
            // Pop Device Info
            $popDeviceInfo = new TransPopDeviceInfo();
            $popDeviceInfo->trans_id = $id;
            $popDeviceInfo->module_type = 'branch';
            $popDeviceInfo->sfp_brand_name = $request->get('input_sfp_mc_model');
            $popDeviceInfo->sfp_type = $request->get('input_sfp_type');
            $popDeviceInfo->sfp_capacity = $request->get('input_sfp_capacity');
            $popDeviceInfo->input_device_port_type = $request->get('input_device_port_type');
            // $popDeviceInfo->port_capacity = $request->get('');
            $popDeviceInfo->incoming_fiber_connected_port_number = $request->get('incoming_fiber_connected_port_number');
            $popDeviceInfo->mk_brand_name = $request->get('mikrotik_model');
            $popDeviceInfo->mk_capacity = $request->get('mikrotik_capacity');
            $popDeviceInfo->mk_port_number = $request->get('mikrotik_port_number');
            $popDeviceInfo->mk_serial_no = $request->get('mikrotik_serial_no');
            $popDeviceInfo->mk_device_id = $request->get('mikrotik_device_id');
            $popDeviceInfo->mk_power_consumption = $request->get('mikrotik_power_consumption');
            $popDeviceInfo->mk_mac_address = $request->get('mikrotik_mac');
            $popDeviceInfo->rak_brand_name = $request->get('rak_model');
            $popDeviceInfo->rak_capacity = $request->get('rak_capacity');
            $popDeviceInfo->save();
        }

        $popOutputDeviceInfos = $request->get('output_device_information');
        if($popOutputDeviceInfos){
            foreach ($popOutputDeviceInfos as $popOutputItem) {
                $deviceId = $popOutputItem['id'] ?? null;
                if($deviceId){
                    $popOutputDeviceInfo = TransPopOutputDeviceInfo::where('trans_id', $id)
                    ->where('id', $popOutputItem['id'])
                    ->first();
                    $popOutputDeviceInfo->update([
                        'trans_id' => $id,
                        'module_type' => 'branch',
                        'output_device_type' => $popOutputItem['output_device'],
                        'output_device_port_type' => $popOutputItem['output_device_port_type'],
                        'output_device_port_number' => $popOutputItem['output_device_port_number'],
                        'output_device_brand_name' => $popOutputItem['output_device_model'],
                        'output_device_connection_capacity' => $popOutputItem['connection_capacity'],
                        'output_device_serial_no' => $popOutputItem['serial_no'],
                        'output_device_id' => $popOutputItem['device_id'],
                        'output_device_power_consumption' => $popOutputItem['power_consumption'],
                    ]);
                }else{
                    TransPopOutputDeviceInfo::create([
                        'trans_id' => $id,
                        'module_type' => 'branch',
                        'output_device_type' => $popOutputItem['output_device'],
                        'output_device_port_type' => $popOutputItem['output_device_port_type'],
                        'output_device_port_number' => $popOutputItem['output_device_port_number'],
                        'output_device_brand_name' => $popOutputItem['output_device_model'],
                        'output_device_connection_capacity' => $popOutputItem['connection_capacity'],
                        'output_device_serial_no' => $popOutputItem['serial_no'],
                        'output_device_id' => $popOutputItem['device_id'],
                        'output_device_power_consumption' => $popOutputItem['power_consumption'],
                    ]);
                }
            }
        }
        

        $workerInfo = TransWorkerInfo::where('trans_id', $id)->first();
        if($workerInfo){
            $workerInfo->update([
                'trans_id' => $id,
                'module_type' => 'branch',
                'added_by_name' => $request->get('worker_name'),
                'mobile_number' => $request->get('worker_mobile'),
                'work_type' => $request->get('work_type'),
            ]);
        }else{
            $workerInfo = new TransWorkerInfo();
            $workerInfo->trans_id = $id;
            $workerInfo->module_type = 'branch';
            $workerInfo->added_by_name = $request->get('worker_name');
            $workerInfo->mobile_number = $request->get('worker_mobile');
            $workerInfo->work_type = $request->get('work_type');
            $workerInfo->save();
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Pop updated successfully!';
        return ResponseWrapper::End($returned_data);
    }

    // Delete Transmission Pop
    public function deleteTransBranchPop($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $latLongDeleted = TransLatLong::where('trans_id', $id)->where('module_type','branch')->delete();
        $outputDeviceDeleted = TransPopOutputDeviceInfo::where('trans_id', $id)->where('module_type','branch')->delete();
        $deviceDeleted = TransPopDeviceInfo::where('trans_id', $id)->where('module_type','branch')->delete();
        $cableDeleted = TransCableDetail::where('trans_id', $id)->where('module_type','branch')->delete();
        $workerDeleted = TransWorkerInfo::where('trans_id', $id)->where('module_type','branch')->delete();
        // Fetch all images related to the pop
        $images = TransImage::where('trans_id', $id)->where('module_type','branch')->get();

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
        $imageDeleted = TransImage::where('trans_id', $id)->where('module_type','branch')->delete();
        $popDeleted = TransPop::where('id', $id)->where('pop_type','branch')->delete();
        if ($latLongDeleted && $outputDeviceDeleted && $deviceDeleted && $cableDeleted && $workerDeleted && $popDeleted) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "Pop and associated images deleted successfully!";
        } else {
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Try again something went wrong!";
        }
        return ResponseWrapper::End($returned_data);
    }

    // Summary Transmission Pop
    public function summaryTransBranchPop() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // summary
        $summary = TransPop::where('pop_type','branch')->selectRaw(
            'COUNT(trans_pops.id) AS total,
             COUNT(CASE WHEN trans_pops.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_pops.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    public function getTransBranchPopLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransLatLong::query();
        $query->leftJoin('trans_pops','trans_pops.id', '=', 'trans_lat_longs.trans_id');
        $query->where('module_type','branch');

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
                'trans_pops.pop_code as trans_code',
                'trans_lat_longs.status',
                'trans_lat_longs.latitude',
                'trans_lat_longs.longitude',
            ]);

        $returned_data['results'] = $results;
        return ResponseWrapper::End($returned_data);
    }

    public function bulkUploadTransBranchPop(Request $request) : JsonResponse
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
            Excel::import(new TransBranchPopImport, $request->file('file'));
            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }
}
