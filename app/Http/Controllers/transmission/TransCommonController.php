<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\InternetUsers;
use App\Models\TransCustomer;
use App\Models\TransImage;
use App\Models\TransJson;
use App\Models\TransLatLong;
use App\Models\TransLoop;
use App\Models\TransPop;
use App\Models\TransTjBox;
use App\Models\User;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransCommonController extends Controller
{
    // Get Transmission Pop By radiation ---
    public function getTransPopsByRadiation($auth_id, $id, $type, $latitude, $longitude, $radiation) : JsonResponse {
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
        $radiusInMeter = $radiation / 1000;
        $result = TransLatLong::query();
        if($base_role !== 'admin'){
            $result = $result->where('trans_lat_longs.zone_id', $zone_id);
        }
        $result = $result->select(DB::raw("
                trans_lat_longs.trans_id,
                (SELECT company_id FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as company_id,
                (SELECT company_name FROM trans_companies AS tc WHERE tc.id = company_id) as company_name,
                (SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as pop_code,
                (SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_lat_longs.trans_id) as tj_code,
                (SELECT loop_code FROM trans_loops AS lo WHERE lo.id = trans_lat_longs.trans_id) as loop_code,
                (SELECT customer_name FROM trans_customers AS tcu WHERE tcu.id = trans_lat_longs.trans_id) as customer_name,
                trans_lat_longs.module_type,
                trans_lat_longs.latitude,
                trans_lat_longs.longitude,
                ROUND((6371 * acos(cos(radians('$latitude')) * cos(radians(latitude)) * cos(radians(longitude) - radians('$longitude')) + sin(radians('$latitude')) * sin(radians(latitude)))), 2) AS distance
            "))
            ->where('trans_id', '!=', $id, 'AND', 'module_type', '!=', $type)
            ->havingRaw('distance < ?', [$radiusInMeter])
            ->orderBy('distance')
            ->get();
        $returned_data['results'] = $result;
        return ResponseWrapper::End($returned_data);
    }

    // Get Transmission Customer By radiation ---
    public function getTransCustomerByRadiation($latitude, $longitude, $radiation) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $radiusInMeter = $radiation / 1000;

        $nearestPop = TransLatLong::select(DB::raw("
            trans_lat_longs.trans_id,
            (SELECT company_id FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as company_id,
            (SELECT company_name FROM trans_companies AS tc WHERE tc.id = company_id) as company_name,
            (SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as pop_code,
            (SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_lat_longs.trans_id) as tj_code,
            (SELECT loop_code FROM trans_loops AS lo WHERE lo.id = trans_lat_longs.trans_id) as loop_code,
            (SELECT customer_name FROM trans_customers AS tcu WHERE tcu.id = trans_lat_longs.trans_id) as customer_name,
            trans_lat_longs.module_type,
            trans_lat_longs.latitude,
            trans_lat_longs.longitude,
            ROUND((6371 * acos(cos(radians('$latitude')) * cos(radians(latitude)) * cos(radians(longitude) - radians('$longitude')) + sin(radians('$latitude')) * sin(radians(latitude)))), 2) AS distance
        "))
        ->whereIn('module_type', ['branch','distribution_loop','distribution_tj'])
        ->havingRaw('distance < ?', [$radiusInMeter])
        ->orderBy('distance')
        ->first();

        $nearestBranch = TransLatLong::select(DB::raw("
            trans_lat_longs.trans_id,
            (SELECT company_id FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as company_id,
            (SELECT company_name FROM trans_companies AS tc WHERE tc.id = company_id) as company_name,
            (SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as pop_code,
            (SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_lat_longs.trans_id) as tj_code,
            (SELECT loop_code FROM trans_loops AS lo WHERE lo.id = trans_lat_longs.trans_id) as loop_code,
            (SELECT customer_name FROM trans_customers AS tcu WHERE tcu.id = trans_lat_longs.trans_id) as customer_name,
            trans_lat_longs.module_type,
            trans_lat_longs.latitude,
            trans_lat_longs.longitude,
            ROUND((6371 * acos(cos(radians('$latitude')) * cos(radians(latitude)) * cos(radians(longitude) - radians('$longitude')) + sin(radians('$latitude')) * sin(radians(latitude)))), 2) AS distance
        "))
        ->where('module_type', 'branch')
        ->havingRaw('distance < ?', [$radiusInMeter])
        ->orderBy('distance')
        ->first();

        $data = [
            'nearest_pop' => $nearestPop,
            'nearest_branch' => $nearestBranch,
        ];

        // success response
        $returned_data['status'] = 'success';
        $returned_data['results'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    // Upload Transmission Image ---
    public function imageUploadTransmission(Request $request, $auth_id, $trans_id, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'images' => 'required',
            'images.*' => 'mimes:jpeg,png,jpg'
        ],[
            'images.required' => 'Images are required.',
            'images.*.mimes' => 'Allowed extensions are jpeg, png, jpg.',
            // 'images.*.max' => 'Maximum upload size is 1MB.',
        ]);

        if (!$validated) {
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

        // Variables
        $images = $request->file('images');

        $uploaded_images = [];

        foreach ($images as $image) {
            $imageName = date('YmdHi') . '-' . $image->getClientOriginalName();
            $image->move(public_path('trans/image'), $imageName);

            // create new profile
            $newImage = new TransImage();
            $newImage->zone_id = $zone_id;
            $newImage->trans_id = $trans_id;
            $newImage->module_type = $type;
            $newImage->image = 'trans/image/' . $imageName;
            $newImage->save();

            $uploaded_images[] = 'trans/image/' . $imageName;
        }

        // success response
        $returned_data['message'] = 'All images uploaded successfully';
        $returned_data['status'] = 'success';
        $returned_data['results'] = $uploaded_images;
        return ResponseWrapper::End($returned_data);
    }

    public function imageRemoveTransmission($id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $image = TransImage::where('id', $id)->first();

        if (!$image) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Image not found!";
            return ResponseWrapper::End($returned_data);
        }

        $imagePath = public_path($image->image);

        if (file_exists($imagePath)) {
            if (!unlink($imagePath)) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Failed to delete image!";
                return ResponseWrapper::End($returned_data);
            }
        }

        $imageDeleted = TransImage::where('id', $id)->delete();

        if (!$imageDeleted) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Failed to delete image from database!";
            return ResponseWrapper::End($returned_data);
        }

        // success response
        $returned_data['message'] = 'Image deleted successfully';
        $returned_data['status'] = 'success';
        return ResponseWrapper::End($returned_data);
    }
    
    public function getConnectionPathFromCustomer($customerId)  : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Initialize the final array structure
        $data = [];

        // Fetch customer information including the initial customer TJ-box ID
        $customer = TransCustomer::find($customerId);
        $customerPop = TransPop::find($customer->pop_id);
        $customerTj = TransTjBox::find($customer->tj_box_id);
        $data[] = $this->formatDataCustomer($customer, $customerTj->latitude, $customerTj->longitude);

        $currentTj = $customerTj;
        while ($currentTj) {
            $nextTj = TransTjBox::find($currentTj->parent_tj_box_id);
            if ($nextTj) {
                $data[] = $this->formatDataTj($currentTj, $nextTj->latitude, $nextTj->longitude);
            } else {
                // If nextTj is null, set static latitude and longitude
                $data[] = $this->formatDataTj($currentTj, $customerPop->latitude, $customerPop->longitude);
            }
            $currentTj = $nextTj;
        }

        $data[] = $this->formatDataPop($customerPop);

        // success response
        $returned_data['message'] = '';
        $returned_data['status'] = 'success';
        $returned_data['results'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    public function getConnectionPathFromLoop($loopId)  : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Initialize the final array structure
        $data = [];

        // Fetch customer information including the initial customer TJ-box ID
        $loop = TransLoop::find($loopId);
        $loopPop = TransPop::find($loop->pop_id);
        $loopTj = TransTjBox::find($loop->tj_box_id);
        $data[] = $this->formatDataLoop($loop, $loopTj->latitude, $loopTj->longitude);

        $currentTj = $loopTj;
        while ($currentTj) {
            $nextTj = TransTjBox::find($currentTj->parent_tj_box_id);
            if ($nextTj) {
                $data[] = $this->formatDataTj($currentTj, $nextTj->latitude, $nextTj->longitude);
            } else {
                // If nextTj is null, set static latitude and longitude
                $data[] = $this->formatDataTj($currentTj, $loopPop->latitude, $loopPop->longitude);
            }
            $currentTj = $nextTj;
        }

        $data[] = $this->formatDataPop($loopPop);

        // success response
        $returned_data['message'] = '';
        $returned_data['status'] = 'success';
        $returned_data['results'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    public function getConnectionPathFromTj($TjId)  : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Initialize the final array structure
        $data = [];

        // Fetch customer information including the initial customer TJ-box ID
        $tj = TransTjBox::find($TjId);
        $tjPop = TransPop::find($tj->pop_id);

        $currentTj = $tj;
        while ($currentTj) {
            $nextTj = TransTjBox::find($currentTj->parent_tj_box_id);
            if ($nextTj) {
                $data[] = $this->formatDataTj($currentTj, $nextTj->latitude, $nextTj->longitude);
            } else {
                // If nextTj is null, set static latitude and longitude
                $data[] = $this->formatDataTj($currentTj, $tjPop->latitude, $tjPop->longitude);
            }
            $currentTj = $nextTj;
        }

        $data[] = $this->formatDataPop($tjPop);

        // success response
        $returned_data['message'] = '';
        $returned_data['status'] = 'success';
        $returned_data['results'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    public function formatDataLoop($entity, $latitude, $longitude) {
        return [
            'trans_id' => $entity->id,
            'trans_code' => $entity->loop_code,
            'curr_pos' => [$entity->latitude, $entity->longitude],
            'upstream_pos' => [$latitude, $longitude],
            'module_type' => $entity->loop_type,
            'status' => $entity->status,
            'company_id' => $entity->company_id,
            'latitude' => $entity->latitude,
            'longitude' => $entity->longitude,
        ];
    }

    public function formatDataCustomer($entity, $latitude, $longitude) {
        return [
            'trans_id' => $entity->id,
            'trans_code' => $entity->customer_name,
            'curr_pos' => [$entity->latitude, $entity->longitude],
            'upstream_pos' => [$latitude, $longitude],
            'module_type' => 'customer',
            'status' => $entity->status,
            'company_id' => $entity->company_id,
            'latitude' => $entity->latitude,
            'longitude' => $entity->longitude,
        ];
    }

    public function formatDataTj($entity, $latitude, $longitude) {
        return [
            'trans_id' => $entity->id,
            'trans_code' => $entity->tj_box_code,
            // 'parent_tj_box' => $entity->parent_tj_box_id,
            'curr_pos' => [$entity->latitude, $entity->longitude],
            'upstream_pos' => [$latitude, $longitude],
            'module_type' => $entity->tj_box_type,
            'status' => $entity->status,
            'company_id' => $entity->company_id,
            'latitude' => $entity->latitude,
            'longitude' => $entity->longitude,
        ];
    }

    public function formatDataPop($entity) {
        return [
            'trans_id' => $entity->id,
            'trans_code' => $entity->pop_code,
            'curr_pos' => [$entity->latitude, $entity->longitude],
            'module_type' => $entity->pop_type,
            'status' => $entity->status,
            'company_id' => $entity->company_id,
            'latitude' => $entity->latitude,
            'longitude' => $entity->longitude,
        ];
    }

    private function getModuleConfigurations(): array {
        return [
            'nttn' => [
                'joinTable' => 'trans_pops',
                'joinConditionColumn' => 'trans_pops.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_pops.pop_code as trans_code',
                    'trans_pops.company_id',
                    DB::raw('(SELECT company_name FROM trans_companies AS tc WHERE tc.id = trans_pops.company_id) as company_name'),
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'branch' => [
                'joinTable' => 'trans_pops',
                'joinConditionColumn' => 'trans_pops.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_pops.pop_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'sub_branch' => [
                'joinTable' => 'trans_pops',
                'joinConditionColumn' => 'trans_pops.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_pops.pop_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'backbone_tj' => [
                'joinTable' => 'trans_tj_boxes',
                'joinConditionColumn' => 'trans_tj_boxes.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_tj_boxes.tj_box_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'backbone_join_tj' => [
                'joinTable' => 'trans_tj_boxes',
                'joinConditionColumn' => 'trans_tj_boxes.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_tj_boxes.tj_box_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'joining_tj' => [
                'joinTable' => 'trans_tj_boxes',
                'joinConditionColumn' => 'trans_tj_boxes.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_tj_boxes.tj_box_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'distribution_tj' => [
                'joinTable' => 'trans_tj_boxes',
                'joinConditionColumn' => 'trans_tj_boxes.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_tj_boxes.tj_box_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'customer_tj' => [
                'joinTable' => 'trans_tj_boxes',
                'joinConditionColumn' => 'trans_tj_boxes.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_tj_boxes.tj_box_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'reserved_loop' => [
                'joinTable' => 'trans_loops',
                'joinConditionColumn' => 'trans_loops.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_loops.loop_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'distribution_loop' => [
                'joinTable' => 'trans_loops',
                'joinConditionColumn' => 'trans_loops.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_loops.loop_code as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
            'customer' => [
                'joinTable' => 'trans_customers',
                'joinConditionColumn' => 'trans_customers.id',
                'joinKeyColumn' => 'trans_lat_longs.trans_id',
                'selectFields' => [
                    'trans_lat_longs.id',
                    'trans_lat_longs.trans_id',
                    'trans_lat_longs.module_type',
                    'trans_customers.customer_name as trans_code',
                    'trans_lat_longs.status',
                    'trans_lat_longs.latitude',
                    'trans_lat_longs.longitude',
                ],
            ],
        ];
    }

    public function getAllLatLong(Request $request, $auth_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 100;
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

        // Define module types and their specific joins and data mappings
        $modules = $this->getModuleConfigurations();

        $results = [];

        foreach ($modules as $moduleType => $moduleConfig) {
            $query = TransLatLong::query();
            // Apply the join
            $query->leftJoin(
                $moduleConfig['joinTable'],
                $moduleConfig['joinConditionColumn'],
                '=',
                $moduleConfig['joinKeyColumn']
            );

            // Apply module-specific filter
            if($base_role !== 'admin'){
                $query = $query->where('trans_lat_longs.zone_id', $zone_id);
            }
            $query->where('trans_lat_longs.module_type', $moduleType);

            // Apply search parameters
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
            if (!empty($request->get('pop')) && in_array($moduleType, ['joining_tj', 'distribution_tj', 'customer_tj', 'reserved_loop', 'distribution_loop', 'customer'])) {
                if ($request->get('pop') !== 'all') {
                    $query->where($moduleConfig['joinTable'] . '.pop_id', $request->get('pop'));
                }
            }
            if (!empty($request->get('status'))) {
                if ($request->get('status') !== 'all') {
                    $query->where('trans_lat_longs.status', $request->get('status'));
                }
            }

            // Fetch results with pagination
            $moduleResults = $query->skip($totalSkip)->take($totalLimit)
                ->get($moduleConfig['selectFields']);

            // Assign results to corresponding module key
            $results[$moduleType] = $moduleResults;
        }

        $returned_data['results'] = $results;

        return ResponseWrapper::End($returned_data);
    }

    public function getAllTranSummary($auth_id) : JsonResponse {
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

        // Mapping for all types
        $typeConfig = [
            'nttn' => [
                'model' => TransPop::class,
                'filterColumn' => 'pop_type',
                'filterValue' => 'nttn',
            ],
            'branch' => [
                'model' => TransPop::class,
                'filterColumn' => 'pop_type',
                'filterValue' => 'branch',
            ],
            'sub_branch' => [
                'model' => TransPop::class,
                'filterColumn' => 'pop_type',
                'filterValue' => 'sub_branch',
            ],
            'backbone_tj' => [
                'model' => TransTjBox::class,
                'filterColumn' => 'tj_box_type',
                'filterValue' => 'backbone_tj',
            ],
            'backbone_join_tj' => [
                'model' => TransTjBox::class,
                'filterColumn' => 'tj_box_type',
                'filterValue' => 'backbone_join_tj',
            ],
            'joining_tj' => [
                'model' => TransTjBox::class,
                'filterColumn' => 'tj_box_type',
                'filterValue' => 'joining_tj',
            ],
            'distribution_tj' => [
                'model' => TransTjBox::class,
                'filterColumn' => 'tj_box_type',
                'filterValue' => 'distribution_tj',
            ],
            'customer_tj' => [
                'model' => TransTjBox::class,
                'filterColumn' => 'tj_box_type',
                'filterValue' => 'customer_tj',
            ],
            'reserved_loop' => [
                'model' => TransLoop::class,
                'filterColumn' => 'loop_type',
                'filterValue' => 'reserved_loop',
            ],
            'distribution_loop' => [
                'model' => TransLoop::class,
                'filterColumn' => 'loop_type',
                'filterValue' => 'distribution_loop',
            ],
            'customer' => [
                'model' => TransCustomer::class,
            ],
        ];

        // Initialize the results
        $results = [];

        // Loop through each type configuration
        foreach ($typeConfig as $key => $config) {
            $query = $config['model']::query();
            if($base_role !== 'admin'){
                $query = $query->where('zone_id', $zone_id);
            }

            if (isset($config['filterColumn'], $config['filterValue'])) {
                $query->where($config['filterColumn'], $config['filterValue']);
            }

            // Perform the summary query
            $summary = $query->selectRaw(
                'COUNT(id) AS total,
                 COUNT(CASE WHEN status = \'active\' THEN 1 END) AS total_active,
                 COUNT(CASE WHEN status = \'pending\' THEN 1 END) AS total_pending'
            )->get();

            // Add to results
            $results[$key] = $summary;
        }

        $returned_data['results'] = $results;
        $returned_data['status'] = 'success';
        return ResponseWrapper::End($returned_data);
    }
}
