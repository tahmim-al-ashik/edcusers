<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\GeoDistrict;
use App\Imports\TransNTTNPopImport;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\TransCableDetail;
use App\Models\TransCompany;
use App\Models\TransImage;
use App\Models\TransLatLong;
use App\Models\TransPop;
use App\Models\TransCoreJoinInfo;
use App\Models\TransPopDeviceInfo;
use App\Models\TransPopOutputDeviceInfo;
use App\Models\TransWorkerInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransNTTNPopController extends Controller
{
    // Transmission Nttn pop list ---
    public function transNTTNPopList(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransPop::query();
        $query = $query->where('pop_type','nttn')->leftJoin('trans_companies', 'trans_pops.company_id', '=', 'trans_companies.id')
        ->orderBy('trans_pops.id', 'desc')
        ->get([
            'trans_pops.id',
            'trans_pops.company_id',
            'trans_companies.company_name',
            'trans_pops.pop_code',
            'trans_pops.nttn_pop_code',
            'trans_pops.pop_type',
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
    public function transNTTNPops (Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $query = TransPop::query();
        $query = $query->where('pop_type', 'nttn')->orderBy('trans_pops.id', 'desc')
            ->get([
                'trans_pops.id',
                'trans_pops.pop_code'
            ]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    // Transmission Single Pop Details ---
    public function transNTTNPopDetails($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Fetch the TransPop record along with related data
        $transPop = TransPop::leftJoin('trans_companies', 'trans_pops.company_id', '=', 'trans_companies.id')
                            ->with([
                                'nttnDeviceInfos',
                                'nttnOutputDeviceInfos',
                                'nttnCableDetails',
                                'nttnCoreJoinInfo',
                                'nttnWorkerInfos'
                            ])
                            ->select(
                                'trans_pops.*',
                                'trans_companies.company_name',  // Included in the joined select
                                'trans_companies.company_type',   // Included in the joined select
                            )
                            ->where('trans_pops.id', $id)
                            ->first();

        if ($transPop) {
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
                'backup_nttn_pop_id' => $transPop->backup_nttn_pop_id,
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
                'out_cable_details' => $transPop->nttnCableDetails->map(function ($cableDetail) {
                    if($cableDetail->cable_type === 'our_cable'){
                        return [
                            'id' => $cableDetail->id,
                            'cable_type' => $cableDetail->cable_type,
                            'cable_name' => $cableDetail->cable_name,
                            'fiber_code' => $cableDetail->fiber_code,
                            'fiber_core' => $cableDetail->fiber_core,
                            'core_capacity' => $cableDetail->core_capacity,
                            'joining_core_color' => $cableDetail->joining_core_color,
                            'start_fiber_meter' => $cableDetail->start_fiber_meter,
                            'end_fiber_meter' => $cableDetail->end_fiber_meter,
                            'fiber_length' => $cableDetail->fiber_length,
                            'connected_port_number' => $cableDetail->connected_port_number,
                        ];
                    }
                })->filter()->values()->toArray(),
                'in_cable_details' => $transPop->nttnCableDetails->map(function ($cableDetail) {
                    if($cableDetail->cable_type === 'provider_cable'){
                        return [
                            'id' => $cableDetail->id,
                            'cable_type' => $cableDetail->cable_type,
                            'cable_name' => $cableDetail->cable_name,
                            'fiber_code' => $cableDetail->fiber_code,
                            'fiber_core' => $cableDetail->fiber_core,
                            'core_capacity' => $cableDetail->core_capacity,
                            'joining_core_color' => $cableDetail->joining_core_color,
                            'start_fiber_meter' => $cableDetail->start_fiber_meter,
                            'end_fiber_meter' => $cableDetail->end_fiber_meter,
                            'fiber_length' => $cableDetail->fiber_length,
                            'connected_port_number' => $cableDetail->connected_port_number,
                        ];
                    }
                })->filter()->values()->toArray(),
                'pop_core_join_info' => $transPop->nttnCoreJoinInfo->map(function ($coreJoin) {
                    return [
                        'id' => $coreJoin->id,
                        'in_fiber_id' => $coreJoin->in_fiber_id,
                        'out_fiber_id' => $coreJoin->out_fiber_id,
                        'out_fiber_code' => TransCableDetail::where('id', $coreJoin->out_fiber_id)->value('fiber_code'),
                        'in_fiber_code' => TransCableDetail::where('id', $coreJoin->in_fiber_id)->value('fiber_code'),
                        'joining_core_color' => $coreJoin->joining_core_color,
                        'db_signal' => $coreJoin->db_signal,
                    ];
                })->toArray(),
                'pop_device_info' => $transPop->nttnDeviceInfos->map(function ($deviceInfo) {
                    return [
                        'id' => $deviceInfo->id,
                        'sfp_brand_name' => $deviceInfo->sfp_brand_name,
                        'sfp_type' => $deviceInfo->sfp_type,
                        'sfp_capacity' => $deviceInfo->sfp_capacity,
                        'input_device_port_type' => $deviceInfo->input_device_port_type,
                        'port_capacity' => $deviceInfo->port_capacity,
                        'incoming_fiber_connected_port_number' => $deviceInfo->incoming_fiber_connected_port_number,
                        'mk_brand_name' => $deviceInfo->mk_brand_name,
                        'mk_capacity' => $deviceInfo->mk_capacity,
                        'mk_port_number' => $deviceInfo->mk_port_number,
                        'rak_brand_name' => $deviceInfo->rak_brand_name,
                        'rak_capacity' => $deviceInfo->rak_capacity,
                    ];
                })->toArray(),
                'images' => $transPop->nttnImages->map(function ($nttnImage) {
                    return [
                        'id' => $nttnImage->id,
                        'trans_id' => $nttnImage->trans_id,
                        'module_type' => $nttnImage->module_type,
                        'image' => $nttnImage->image
                    ];
                })->toArray(),
                'worker_info' => $transPop->nttnWorkerInfos->map(function ($workerInfo) {
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
    public function createTransNTTNPop(Request $request) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();

            $validated = $request->validate([
                'pop_id' => 'required',
                'pop_type' => 'required',
                'provider_id' => 'required',

                'division' => 'required',
                'district' => 'required',
                'union' => 'required',
                'upazila' => 'required',
                'village' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'address_direction' => 'required',

                // 'join_core_color' => 'required',
                // 'join_core_db_signal' => 'required',
                // 'join_fiber_id_our' => 'required',
                // 'join_fiber_id_provider' => 'required',

                // 'our_fiber_id' => 'required',
                // 'our_fiber_core' => 'required',
                // 'our_cable_start_meter' => 'required',
                // 'our_cable_end_meter' => 'required',
                // 'our_cable_length' => 'required',

                // 'provider_fiber_id' => 'required',
                // 'provider_fiber_core' => 'required',
                // 'provider_core_capacity' => 'required',

                // 'sfp_type' => 'required',
                // 'sfp_model' => 'required',
                // 'sfp_capacity' => 'required',

                'worker_name' => 'required',
                'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'work_type' => 'required',
            ],[
                'pop_id.required' => 'Pop Id is required.',
                'pop_type.required' => 'Pop type is required.',
                'provider_id.required' => 'Provider id is required.',

                'division.required' => 'Division is required.',
                'district.required' => 'District is required.',
                'union.required' => 'Union is required.',
                'upazila.required' => 'Upazila is required.',
                'village.required' => 'Village is required.',
                'latitude.required' => 'Latitude is required.',
                'longitude.required' => 'Longitude is required.',
                'address_direction.required' => 'Address direction is required.',

                // 'join_core_color.required' => 'Join core color is required.',
                // 'join_core_db_signal.required' => 'Join core db signal is required.',
                // 'join_fiber_id_our.required' => 'Our join fiber id is required.',
                // 'join_fiber_id_provider.required' => 'Provider join fiber id is required.',

                // 'our_fiber_id.required' => 'Our fiber id is required.',
                // 'our_fiber_core.required' => 'Our fiber core is required.',
                // 'our_cable_start_meter.required' => 'Our cable start meter is required.',
                // 'our_cable_end_meter.required' => 'Our cable end meter is required.',
                // 'our_cable_length.required' => 'Our cable length is required.',

                // 'provider_fiber_id.required' => 'Provider fiber id is required.',
                // 'provider_fiber_core.required' => 'Provider fiber core is required.',
                // 'provider_core_capacity.required' => 'Provider core capacity is required.',

                // 'sfp_type.required' => 'Sfp type is required.',
                // 'sfp_model.required' => 'Sfp model is required.',
                // 'sfp_capacity.required' => 'Sfp capacity is required.',

                'worker_name.required' => 'Worker name is required.',
                'worker_mobile.required' => 'Worker mobile is required.',
                'worker_mobile.regex' => 'Worker mobile should be from Bangladesh.',
                'work_type.required' => 'Worker type is required.'
            ]);

            if(!$validated){
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Validation Failed!";
                return ResponseWrapper::End($returned_data);
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
            $pop->pop_type = 'nttn';
            $pop->pop_main_type = $request->get('pop_type');
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

            $address = new TransLatLong();
            $address->trans_id = $popId;
            $address->module_type = 'nttn';
            $address->division_id = $request->get('division');
            $address->district_id = $request->get('district');
            $address->upazila_id = $request->get('upazila');
            $address->union_id = $request->get('union');
            $address->latitude = $request->get('latitude');
            $address->longitude = $request->get('longitude');
            $address->status = 'Active';
            $address->save();

            $ourCable = new TransCableDetail();
            $ourCable->trans_id = $popId;
            $ourCable->module_type = 'nttn';
            $ourCable->cable_type = 'our_cable';
            $ourCable->fiber_code = $request->get('our_fiber_id');
            $ourCable->fiber_core = $request->get('our_fiber_core');
            $ourCable->start_fiber_meter = $request->get('our_cable_start_meter');
            $ourCable->end_fiber_meter = $request->get('our_cable_end_meter');
            $ourCable->fiber_length = $request->get('our_cable_length');
            $ourCable->save();

            $providerCable = new TransCableDetail();
            $providerCable->trans_id = $popId;
            $providerCable->module_type = 'nttn';
            $providerCable->cable_type = 'provider_cable';
            $providerCable->core_capacity = $request->get('provider_core_capacity');
            $providerCable->fiber_code = $request->get('provider_fiber_id');
            $providerCable->fiber_core = $request->get('provider_fiber_core');
            $providerCable->save();

            $joinInfo = new TransCoreJoinInfo();
            $joinInfo->trans_id = $popId;
            $joinInfo->module_type = 'nttn';
            $joinInfo->in_fiber_id = TransCableDetail::where('fiber_code', $request->get('provider_fiber_id'))->where('module_type','nttn')->value('id');
            $joinInfo->out_fiber_id = TransCableDetail::where('fiber_code', $request->get('our_fiber_id'))->where('module_type','nttn')->value('id');
            $joinInfo->joining_core_color = $request->get('join_core_color');
            $joinInfo->db_signal = $request->get('join_core_db_signal');
            $joinInfo->save();

            $popDeviceInfo = new TransPopDeviceInfo();
            $popDeviceInfo->trans_id = $popId;
            $popDeviceInfo->module_type = 'nttn';
            $popDeviceInfo->sfp_brand_name = $request->get('sfp_model');
            $popDeviceInfo->sfp_type = $request->get('sfp_type');
            $popDeviceInfo->sfp_capacity = $request->get('sfp_capacity');
            $popDeviceInfo->save();

            $workerInfo = new TransWorkerInfo();
            $workerInfo->trans_id = $popId;
            $workerInfo->module_type = 'nttn';
            $workerInfo->added_by_name = $request->get('worker_name');
            $workerInfo->mobile_number = $request->get('worker_mobile');
            $workerInfo->work_type = $request->get('work_type');
            $workerInfo->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'NTTN pop added successfully!';
            $returned_data['results'] = $popId;
            return ResponseWrapper::End($returned_data);
    }

    // Edit Transmission NTTN Pop ---
    public function editTransNTTNPop(Request $request, $id) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();

            $validated = $request->validate([
                'pop_id' => 'required',
                'pop_type' => 'required',
                'provider_id' => 'required',

                'division' => 'required',
                'district' => 'required',
                'union' => 'required',
                'upazila' => 'required',
                'village' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'address_direction' => 'required',

                // 'join_core_color' => 'required',
                // 'join_core_db_signal' => 'required',
                // 'join_fiber_id_our' => 'required',
                // 'join_fiber_id_provider' => 'required',

                // 'our_fiber_id' => 'required',
                // 'our_fiber_core' => 'required',
                // 'our_cable_start_meter' => 'required',
                // 'our_cable_end_meter' => 'required',
                // 'our_cable_length' => 'required',

                // 'provider_fiber_id' => 'required',
                // 'provider_fiber_core' => 'required',
                // 'provider_core_capacity' => 'required',

                // 'sfp_type' => 'required',
                // 'sfp_model' => 'required',
                // 'sfp_capacity' => 'required',

                'worker_name' => 'required',
                'worker_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'work_type' => 'required',
            ],[
                'pop_id.required' => 'Pop Id is required.',
                'pop_type.required' => 'Pop type is required.',
                'provider_id.required' => 'Provider id is required.',

                'division.required' => 'Division is required.',
                'district.required' => 'District is required.',
                'union.required' => 'Union is required.',
                'upazila.required' => 'Upazila is required.',
                'village.required' => 'Village is required.',
                'latitude.required' => 'Latitude is required.',
                'longitude.required' => 'Longitude is required.',
                'address_direction.required' => 'Address direction is required.',

                // 'join_core_color.required' => 'Join core color is required.',
                // 'join_core_db_signal.required' => 'Join core db signal is required.',
                // 'join_fiber_id_our.required' => 'Our join fiber id is required.',
                // 'join_fiber_id_provider.required' => 'Provider join fiber id is required.',

                // 'our_fiber_id.required' => 'Our fiber id is required.',
                // 'our_fiber_core.required' => 'Our fiber core is required.',
                // 'our_cable_start_meter.required' => 'Our cable start meter is required.',
                // 'our_cable_end_meter.required' => 'Our cable end meter is required.',
                // 'our_cable_length.required' => 'Our cable length is required.',

                // 'provider_fiber_id.required' => 'Provider fiber id is required.',
                // 'provider_fiber_core.required' => 'Provider fiber core is required.',
                // 'provider_core_capacity.required' => 'Provider core capacity is required.',

                // 'sfp_type.required' => 'Sfp type is required.',
                // 'sfp_model.required' => 'Sfp model is required.',
                // 'sfp_capacity.required' => 'Sfp capacity is required.',

                'worker_name.required' => 'Worker name is required.',
                'worker_mobile.required' => 'Worker mobile is required.',
                'worker_mobile.regex' => 'Worker mobile should be from Bangladesh.',
                'work_type.required' => 'Worker type is required.'
            ]);

            if(!$validated){
                $returned_data['status'] = 'success';
                $returned_data['message'] = "Validation Failed!";
                return ResponseWrapper::End($returned_data);
            }

            // Edit Pop
            $pop = TransPop::where('id', $id)->first();
            if($pop){
                $pop->update([
                    'company_id' => $request->get('provider_id'),
                    'pop_code' => $request->get('pop_id'),
                    'pop_type' => 'nttn',
                    'pop_main_type' => $request->get('pop_type'),
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
                    'module_type' => 'nttn',
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
                $address->module_type = 'nttn';
                $address->division_id = $request->get('division');
                $address->district_id = $request->get('district');
                $address->upazila_id = $request->get('upazila');
                $address->union_id = $request->get('union');
                $address->latitude = $request->get('latitude');
                $address->longitude = $request->get('longitude');
                $address->status = 'Active';
                $address->save();
            }

            $ourCable = TransCableDetail::where('trans_id', $id)->where('cable_type','our_cable')->first();
            if($ourCable){
                $ourCable->update([
                    'trans_id' => $id,
                    'module_type' => 'nttn',
                    'cable_type' => 'our_cable',
                    'fiber_code' => $request->get('our_fiber_id'),
                    'fiber_core' => $request->get('our_fiber_core'),
                    'start_fiber_meter' => $request->get('our_cable_start_meter'),
                    'end_fiber_meter' => $request->get('our_cable_end_meter'),
                    'fiber_length' => $request->get('our_cable_length'),
                ]);
            }else{
                $ourCable = new TransCableDetail();
                $ourCable->trans_id = $id;
                $ourCable->module_type = 'nttn';
                $ourCable->cable_type = 'our_cable';
                $ourCable->fiber_code = $request->get('our_fiber_id');
                $ourCable->fiber_core = $request->get('our_fiber_core');
                $ourCable->start_fiber_meter = $request->get('our_cable_start_meter');
                $ourCable->end_fiber_meter = $request->get('our_cable_end_meter');
                $ourCable->fiber_length = $request->get('our_cable_length');
                $ourCable->save();
            }

            $providerCable = TransCableDetail::where('trans_id', $id)->where('cable_type','provider_cable')->first();
            if($providerCable){
                $providerCable->update([
                    'trans_id' => $id,
                    'module_type' => 'nttn',
                    'cable_type' => 'provider_cable',
                    'core_capacity' => $request->get('provider_core_capacity'),
                    'fiber_code' => $request->get('provider_fiber_id'),
                    'fiber_core' => $request->get('provider_fiber_core'),
                ]);
            }else{
                $providerCable = new TransCableDetail();
                $providerCable->trans_id = $id;
                $providerCable->module_type = 'nttn';
                $providerCable->cable_type = 'provider_cable';
                $providerCable->core_capacity = $request->get('provider_core_capacity');
                $providerCable->fiber_code = $request->get('provider_fiber_id');
                $providerCable->fiber_core = $request->get('provider_fiber_core');
                $providerCable->save();
            }

            $joinInfo = TransCoreJoinInfo::where('trans_id', $id)->first();
            if($joinInfo){
                $joinInfo->update([
                    'trans_id' => $id,
                    'module_type' => 'nttn',
                    'in_fiber_id' => TransCableDetail::where('fiber_code', $request->get('provider_fiber_id'))->where('module_type','nttn')->value('id'),
                    'out_fiber_id' => TransCableDetail::where('fiber_code', $request->get('our_fiber_id'))->where('module_type','nttn')->value('id'),
                    'joining_core_color' => $request->get('join_core_color'),
                    'db_signal' => $request->get('join_core_db_signal'),
                ]);
            }else{
                $joinInfo = new TransCoreJoinInfo();
                $joinInfo->trans_id = $id;
                $joinInfo->module_type = 'nttn';
                $joinInfo->in_fiber_id = TransCableDetail::where('fiber_code', $request->get('provider_fiber_id'))->where('module_type','nttn')->value('id');
                $joinInfo->out_fiber_id = TransCableDetail::where('fiber_code', $request->get('our_fiber_id'))->where('module_type','nttn')->value('id');
                $joinInfo->joining_core_color = $request->get('join_core_color');
                $joinInfo->db_signal = $request->get('join_core_db_signal');
                $joinInfo->save();
            }

            $popDeviceInfo = TransPopDeviceInfo::where('trans_id', $id)->first();
            if($popDeviceInfo){
                $popDeviceInfo->update([
                    'trans_id' => $id,
                    'module_type' => 'nttn',
                    'sfp_brand_name' => $request->get('sfp_model'),
                    'sfp_type' => $request->get('sfp_type'),
                    'sfp_capacity' => $request->get('sfp_capacity'),
                ]);
            }else{
                $popDeviceInfo = new TransPopDeviceInfo();
                $popDeviceInfo->trans_id = $id;
                $popDeviceInfo->module_type = 'nttn';
                $popDeviceInfo->sfp_brand_name = $request->get('sfp_model');
                $popDeviceInfo->sfp_type = $request->get('sfp_type');
                $popDeviceInfo->sfp_capacity = $request->get('sfp_capacity');
                $popDeviceInfo->save();
            }

            $workerInfo = TransWorkerInfo::where('trans_id', $id)->first();
            if($workerInfo){
                $workerInfo->update([
                    'trans_id' => $id,
                    'module_type' => 'nttn',
                    'added_by_name' => $request->get('worker_name'),
                    'mobile_number' => $request->get('worker_mobile'),
                    'work_type' => $request->get('work_type'),
                ]);
            }else{
                $workerInfo = new TransWorkerInfo();
                $workerInfo->trans_id = $id;
                $workerInfo->module_type = 'nttn';
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
    public function deleteTransNTTNPop($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Delete related records from other tables
        $latLongDeleted = TransLatLong::where('trans_id', $id)->where('module_type','nttn')->delete();
        $deviceDeleted = TransPopDeviceInfo::where('trans_id', $id)->where('module_type','nttn')->delete();
        $coreJoinDeleted = TransCoreJoinInfo::where('trans_id', $id)->where('module_type','nttn')->delete();
        $cableDeleted = TransCableDetail::where('trans_id', $id)->where('module_type','nttn')->delete();
        $workerDeleted = TransWorkerInfo::where('trans_id', $id)->where('module_type','nttn')->delete();

        // Fetch all images related to the pop
        $images = TransImage::where('trans_id', $id)->where('module_type','nttn')->get();

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
        $imageDeleted = TransImage::where('trans_id', $id)->where('module_type','nttn')->delete();

        // Delete the TransPop record
        $popDeleted = TransPop::where('id', $id)->where('pop_type','nttn')->delete();

        // Check if all deletions were successful
        if ($latLongDeleted && $deviceDeleted && $coreJoinDeleted && $cableDeleted && $workerDeleted && $popDeleted) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "Pop and associated images deleted successfully!";
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Try again, something went wrong!";
        }

        return ResponseWrapper::End($returned_data);
    }

    // Summary Transmission Pop
    public function summaryTransNTTNPop() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // summary
        $summary = TransPop::where('pop_type','nttn')->selectRaw(
            'COUNT(trans_pops.id) AS total,
             COUNT(CASE WHEN trans_pops.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_pops.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    public function getTransNTTNPopLatLong(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransLatLong::query();
        $query->leftJoin('trans_pops','trans_pops.id', '=', 'trans_lat_longs.trans_id');
        $query->where('module_type','nttn');

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
        if(!empty($request->get('company'))){
            if($request->get('company') !== 'all'){
                $query->where('trans_pops.company_id',$request->get('company'));
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
                'trans_pops.pop_code as trans_code',
                'trans_pops.company_id',
                DB::raw('(SELECT company_name FROM trans_companies AS tc WHERE tc.id = trans_pops.company_id) as company_name'),
                'trans_lat_longs.status',
                'trans_lat_longs.latitude',
                'trans_lat_longs.longitude',
            ]);

        $returned_data['results'] = $results;
        return ResponseWrapper::End($returned_data);
    }

    public function bulkUploadTransNTTNPop(Request $request) : JsonResponse
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
            Excel::import(new TransNTTNPopImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }
}
