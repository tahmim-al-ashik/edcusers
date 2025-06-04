<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Imports\TransCustomerImport;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\InternetUsers;
use App\Models\TransCustomer;
use App\Models\TransImage;
use App\Models\TransLatLong;
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

class TransCustomerController extends Controller
{
    // Customer list ---
    public function transCustomerList(Request $request, $auth_id) : JsonResponse
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

        $query = TransCustomer::query();
        if($base_role !== 'admin'){
            $query = $query->where('trans_customers.zone_id', $zone_id);
        }
        $query = $query->leftJoin('trans_lat_longs', 'trans_customers.id', '=', 'trans_lat_longs.trans_id')->where('module_type','customer')
        ->orderBy('trans_customers.id', 'desc')
        ->get([
            'trans_customers.id',
            'trans_customers.customer_name',
            'trans_customers.customer_mobile',
            'trans_customers.customer_email',
            'trans_customers.pop_id',
            DB::raw('(SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_customers.pop_id) as parent_pop_code'),
            'trans_customers.tj_box_id',
            DB::raw('(SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_customers.tj_box_id) as parent_tj_box_code'),
            'trans_customers.division_id',
            'trans_customers.district_id',
            'trans_customers.upazila_id',
            'trans_customers.union_id',
            'trans_customers.status',
            'trans_customers.created_at'
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

    // Customer Single Details ---
    public function transCustomerDetails($auth_id, $id) : JsonResponse
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

        // Fetch the transCustomer record along with related data
        $transCustomer = TransCustomer::query();
        if($base_role !== 'admin'){
            $transCustomer = $transCustomer->where('trans_customers.zone_id', $zone_id);
        }
        $transCustomer = $transCustomer->where('trans_customers.id', $id)
                            ->leftJoin('trans_pops', 'trans_customers.pop_id', '=', 'trans_pops.id')
                            ->leftJoin('trans_tj_boxes', 'trans_customers.tj_box_id', '=', 'trans_tj_boxes.id')
                            ->with([
                                'customerWorkerInfos',
                                'customerImages'
                            ])
                            ->select(
                                'trans_customers.*',
                                'trans_pops.pop_code',  // Included in the joined select
                                'trans_pops.pop_type',   // Included in the joined select
                                'trans_tj_boxes.tj_box_code',  // Included in the joined select
                                'trans_tj_boxes.tj_box_type',   // Included in the joined select
                            )
                            ->first();

        if ($transCustomer) {
            $details = [
                'id' => $transCustomer->id,
                'pop_id' => $transCustomer->pop_id,
                'pop_code' => TransPop::where('id',$transCustomer->pop_id)->value('pop_code'),
                'tj_box_id' => $transCustomer->tj_box_id,
                'tj_box_code' => TransTjBox::where('id',$transCustomer->tj_box_id)->value('tj_box_code'),
                'olt_port' => $transCustomer->olt_port,
                'customer_name' => $transCustomer->customer_name,
                'customer_mobile' => $transCustomer->customer_mobile,
                'customer_email' => $transCustomer->customer_email,
                'customer_organization' => $transCustomer->customer_organization,
                'contact_person_name' => $transCustomer->contact_person_name,
                'contact_person_number_pri' => $transCustomer->contact_person_number_pri,
                'contact_person_number_sec' => $transCustomer->contact_person_number_sec,
                'contact_person_designation' => $transCustomer->contact_person_designation,
                'contact_person_whatsapp' => $transCustomer->contact_person_whatsapp,
                'contact_person_email' => $transCustomer->contact_person_email,
                'division_id' => $transCustomer->division_id,
                'district_id' => $transCustomer->district_id,
                'upazila_id' => $transCustomer->upazila_id,
                'union_id' => $transCustomer->union_id,
                'division_name' => GeoDivision::where('id', $transCustomer->division_id)->value('en_name') ,
                'district_name' => GeoDistrict::where('id', $transCustomer->district_id)->value('en_name'),
                'upazila_name' => GeoUpazila::where('id', $transCustomer->upazila_id)->value('en_name'),
                'union_name' => GeoUnionPouroshova::where('id', $transCustomer->union_id)->value('en_name'),
                'village' => $transCustomer->village,
                'latitude' => $transCustomer->latitude,
                'longitude' => $transCustomer->longitude,
                'address_direction' => $transCustomer->address_direction,
                'added_by_uid' => $transCustomer->added_by_uid,
                'updated_by_uid' => $transCustomer->updated_by_uid,
                'comments' => $transCustomer->comments,
                'status' => $transCustomer->status,
                'created_at' => $transCustomer->created_at,
                'updated_at' => $transCustomer->updated_at,

                'images' => $transCustomer->customerImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'trans_id' => $image->trans_id,
                        'module_type' => $image->module_type,
                        'image' => $image->image
                    ];
                })->toArray(),

                'worker_info' => $transCustomer->customerWorkerInfos->map(function ($workerInfo) {
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

    // Create Customer ---
    public function createTransCustomer(Request $request, $auth_id) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();
            $validated = $request->validate([
                'customer_name' => 'required',
                'customer_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'customer_email' => 'required',
                'customer_organization' => 'required',

                'customer_pop_id' => 'required',
                'customer_tj_box_id' => 'required',
                'olt_port' => 'required',

                'contact_person_name' => 'required',
                'contact_person_primary_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                // 'contact_person_secondary_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'contact_person_designation' => 'required',
                // 'contact_person_whatsapp_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                // 'contact_person_email' => 'required',

                'division' => 'required',
                'district' => 'required',
                'upazila' => 'required',
                'union' => 'required',
                'village' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'address_direction' => 'required',

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

            $mobileNumber = TransCustomer::where('customer_mobile',$request->get('customer_mobile'))->exists();
            if($mobileNumber){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'This mobile number already in use! Try another number!';
                return ResponseWrapper::End($returned_data);
            }

            $parentPopCode = TransPop::where('pop_code',$request->get('customer_pop_id'))->exists();
            if(!$parentPopCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No Pop found according to your Customer Pop Code!';
                return ResponseWrapper::End($returned_data);
            }

            if($request->get('customer_tj_box_id')){
                $parentTjBoxCode = TransTjBox::where('tj_box_code',$request->get('customer_tj_box_id'))->exists();
                if(!$parentTjBoxCode){
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'No Tj Box found according to your Customer Tj Box Code!';
                    return ResponseWrapper::End($returned_data);
                }
            }

            $parentPopId = TransPop::where('pop_code', $request->get('customer_pop_id'))->value('id');
            $parentTjBoxId = TransTjBox::where('tj_box_code',$request->get('customer_tj_box_id'))->value('id');

            // create new profile
            $customer = new TransCustomer();
            $customer->zone_id = $zone_id;
            $customer->customer_name = $request->get('customer_name');
            $customer->customer_mobile = $request->get('customer_mobile');
            $customer->customer_email = $request->get('customer_email');
            $customer->customer_organization = $request->get('customer_organization');
            $customer->pop_id = $parentPopId;
            $customer->tj_box_id = $parentTjBoxId;
            $customer->olt_port = $request->get('olt_port');
            $customer->contact_person_name = $request->get('contact_person_name');
            $customer->contact_person_number_pri = $request->get('contact_person_primary_mobile');
            $customer->contact_person_number_sec = $request->get('contact_person_secondary_mobile');
            $customer->contact_person_designation = $request->get('contact_person_designation');
            $customer->contact_person_whatsapp = $request->get('contact_person_whatsapp_mobile');
            $customer->contact_person_email = $request->get('contact_person_email');
            $customer->division_id = $request->get('division');
            $customer->district_id = $request->get('district');
            $customer->upazila_id = $request->get('upazila');
            $customer->union_id = $request->get('union');
            $customer->village = $request->get('village');
            $customer->latitude = $request->get('latitude');
            $customer->longitude = $request->get('longitude');
            $customer->address_direction = $request->get('address_direction');
            $customer->added_by_uid = $request->get('added_by_uid');
            $customer->updated_by_uid = $request->get('updated_by_uid');
            $customer->comments = $request->get('comments');
            $customer->status =  'Active';
            $customer->save();

            // Catching Customer id
            $customerId = TransCustomer::where('customer_mobile', $request->get('customer_mobile'))->value('id');

            // Customer Lat Long Info
            $address = new TransLatLong();
            $address->zone_id = $zone_id;
            $address->trans_id = $customerId;
            $address->module_type = 'customer';
            $address->division_id = $request->get('division');
            $address->district_id = $request->get('district');
            $address->upazila_id = $request->get('upazila');
            $address->union_id = $request->get('union');
            $address->latitude = $request->get('latitude');
            $address->longitude = $request->get('longitude');
            $address->status = 'Active';
            $address->save();

            // Worker Info
            $workerInfo = new TransWorkerInfo();
            $workerInfo->zone_id = $zone_id;
            $workerInfo->trans_id = $customerId;
            $workerInfo->module_type = 'customer';
            $workerInfo->added_by_name = $request->get('worker_name');
            $workerInfo->mobile_number = $request->get('worker_mobile');
            $workerInfo->work_type = $request->get('work_type');
            $workerInfo->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Customer added successfully!';
            $returned_data['results'] = $customerId;
            return ResponseWrapper::End($returned_data);
    }

    // Edit Customer ---
    public function editTransCustomer(Request $request, $auth_id, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'customer_name' => 'required',
            'customer_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'customer_email' => 'required',
            'customer_organization' => 'required',

            'customer_pop_id' => 'required',
            'customer_tj_box_id' => 'required',
            'olt_port' => 'required',

            'contact_person_name' => 'required',
            'contact_person_primary_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            // 'contact_person_secondary_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'contact_person_designation' => 'required',
            // 'contact_person_whatsapp_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            // 'contact_person_email' => 'required',

            'division' => 'required',
            'district' => 'required',
            'upazila' => 'required',
            'union' => 'required',
            'village' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'address_direction' => 'required',

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

        $parentPopCode = TransPop::where('pop_code',$request->get('customer_pop_id'))->exists();
        if(!$parentPopCode){
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'No Pop found according to your Customer Pop Code!';
            return ResponseWrapper::End($returned_data);
        }

        if($request->get('customer_tj_box_id')){
            $checkTjBoxCode = TransTjBox::where('tj_box_code',$request->get('customer_tj_box_id'))->exists();
            if(!$checkTjBoxCode){
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No Tj Box found according to your Customer Tj Code!!';
                return ResponseWrapper::End($returned_data);
            }
        }

        $parentPopId = TransPop::where('pop_code', $request->get('customer_pop_id'))->value('id');
        $parentTjBoxId = TransTjBox::where('tj_box_code',$request->get('customer_tj_box_id'))->value('id');

        // Edit Customer
        $customer = TransCustomer::where('id', $id)->first();
        if($customer){
            $customer->update([
                // 'zone_id' => $zone_id,
                'customer_name' => $request->get('customer_name'),
                // 'customer_mobile' => $request->get('customer_mobile'),
                'customer_email' => $request->get('customer_email'),
                'customer_organization' => $request->get('customer_organization'),

                'pop_id' => $parentPopId,
                'tj_box_id' => $parentTjBoxId,
                'olt_port' => $request->get('olt_port'),

                'contact_person_name' => $request->get('contact_person_name'),
                'contact_person_number_pri' => $request->get('contact_person_primary_mobile'),
                'contact_person_number_sec' => $request->get('contact_person_secondary_mobile'),
                'contact_person_designation' => $request->get('contact_person_designation'),
                'contact_person_whatsapp' => $request->get('contact_person_whatsapp_mobile'),
                'contact_person_email' => $request->get('contact_person_email'),

                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'union_id' => $request->get('union'),
                'village' => $request->get('village'),

                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'address_direction' => $request->get('address_direction'),
                // 'added_by_uid' => $request->get('added_by_uid'),
                'updated_by_uid' => $request->get('updated_by_uid'),
                'comments' => $request->get('comments'),
                'status' => 'Active',
            ]);
        }

        $address = TransLatLong::where('trans_id', $id)->where('module_type','customer')->first();
        if($address){
            $address->update([
                'zone_id' => $zone_id,
                'trans_id' => $id,
                'module_type' => 'customer',
                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'union_id' => $request->get('union'),
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                'status' => 'Active'
            ]);
        }

        $workerInfo = TransWorkerInfo::where('trans_id', $id)->where('module_type','customer')->first();
        if($workerInfo){
            $workerInfo->update([
                'zone_id' => $zone_id,
                'trans_id' => $id,
                'module_type' => 'customer',
                'added_by_name' => $request->get('worker_name'),
                'mobile_number' => $request->get('worker_mobile'),
                'work_type' => $request->get('work_type'),
            ]);
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Customer data updated successfully!';
        return ResponseWrapper::End($returned_data);
    }

    // Delete Customer ---
    public function deleteTransCustomer($auth_id, $id) : JsonResponse
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
        $latLongDeleted = $latLongDeleted->where('trans_id', $id)->where('module_type','customer')->delete();

        $workerDeleted = TransWorkerInfo::query();
        if($base_role !== 'admin'){
            $workerDeleted = $workerDeleted->where('zone_id', $zone_id);
        }
        $workerDeleted = $workerDeleted->where('trans_id', $id)->where('module_type','customer')->delete();

        // Fetch all images related to the pop
        $images = TransImage::query();
        if($base_role !== 'admin'){
            $images = $images->where('zone_id', $zone_id);
        }
        $images = $images->where('trans_id', $id)->where('module_type','customer')->get();

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
        $imageDeleted = $imageDeleted->where('trans_id', $id)->where('module_type','customer')->delete();

        $customerDeleted = TransCustomer::query();
        if($base_role !== 'admin'){
            $customerDeleted = $customerDeleted->where('zone_id', $zone_id);
        }
        $customerDeleted = $customerDeleted->where('id', $id)->delete();
        if ($latLongDeleted && $workerDeleted && $customerDeleted) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "Customer and associated images deleted successfully!";
        } else {
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Try again something went wrong!";
        }
        return ResponseWrapper::End($returned_data);
    }

    // Summary Customer ---
    public function summaryTransCustomer($auth_id) : JsonResponse {
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
        $summary = TransCustomer::query();
        if($base_role !== 'admin'){
            $summary = $summary->where('zone_id', $zone_id);
        }
        $summary = $summary->selectRaw(
            'COUNT(trans_customers.id) AS total,
             COUNT(CASE WHEN trans_customers.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_customers.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }

    // Get Customer Lat-Long
    public function getTransCustomerLatLong(Request $request, $auth_id) : JsonResponse {
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
        $query->where('module_type','customer');
        $query->leftJoin('trans_customers','trans_customers.id', '=', 'trans_lat_longs.trans_id');

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
                $query->where('trans_customers.pop_id',$request->get('pop'));
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
                'trans_customers.customer_name as trans_code',
                'trans_lat_longs.status',
                'trans_lat_longs.latitude',
                'trans_lat_longs.longitude',
            ]);

        $returned_data['results'] = $results;
        return ResponseWrapper::End($returned_data);
    }

    // Bulk Upload ---
    public function bulkUploadTransCustomer(Request $request) : JsonResponse
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
            Excel::import(new TransCustomerImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }
}
