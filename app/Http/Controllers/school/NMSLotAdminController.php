<?php

namespace App\Http\Controllers\school;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\NMSLotAdminStoreRequest;
use App\Http\Requests\School\NMSLotAdminUpdateRequest;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\School\NMSLotAdmin;
use App\Models\PanelUser;
use App\Models\School\NMSCategoryBasedAdmin;
use App\Models\School\SchoolManager;
use App\Models\User;
use App\Models\UserProfile;
use BeyondCode\LaravelWebSockets\Server\Logger\Logger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class NMSLotAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $status = $request->get('status');
        $per_page = (int) $request->get('per_page') ?? 10;
        $search = strtolower(trim($request->get('search')));
        $sorting_id = $request->get('sorting_id') ?? 'created_at';
        $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');

        // Base Query
        $query = NMSLotAdmin::with([
            'package:id,price',
            'panel_users:user_id,auth_id,text_password,id'
        ])->select([
            'id',
            'uid',
            'name',
            'mobile_number',
            'whatsapp_number',
            'package_id',
            'lot_username',
            'installation_cost',
            'status',
            'address_direction',
            'created_at'
        ]);

        // Apply filters
        if ($division_id) {
            $query->where('division_id', $division_id);
        }
        if ($district_id) {
            $query->where('district_id', $district_id);
        }
        if ($union_id) {
            $query->where('union_id', $union_id);
        }
        if ($upazila_id) {
            $query->where('union_id', $upazila_id);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('lot_username', 'LIKE', "%{$search}%")
                ->orWhere('address_direction', 'LIKE', "%{$search}%")
                ->orWhere('installation_cost', 'LIKE', "%{$search}%")
                ->orWhere('status', 'LIKE', "%{$search}%")
                ->orWhereHas('internet_package_corporates', function ($subSubQuery) use ($search) {
                    $subSubQuery->where('price', 'LIKE', "%{$search}%");
                });
            });
        }

        // Sorting logic
        if ($sorting_id === 'price') {
            $query->join('internet_package_corporates', 'n_m_s_lot_admins.package_id', '=', 'internet_package_corporates.id')
                  ->orderBy('internet_package_corporates.id', $sorting_direction);
        } else {
            $query->orderBy($sorting_id, $sorting_direction);
        }

        // Fetch paginated data
        $schools = $query->paginate($per_page);

        // Response structure
        $returned_data['pagination'] = [
            'currentPage' => $schools->currentPage(),
            'perPage' => $schools->perPage(),
            'totalPages' => $schools->lastPage(),
            'totalItems' => $schools->total(),
        ];
        $returned_data['results'] = $schools->items(); // Paginated and filtered data
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }

    public function lessDataList(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $user = PanelUser::where('id', $auth_id)->first();

        // Base Query
        $query = NMSLotAdmin::with('panel_users:user_id,id');

        if($user){
            if($user->base_role === 'lot_admin'){
                $query = $query->where('uid', $user->user_id)->select(['id', 'uid', 'name'])->get();
            } else if ($user->base_role === 'edc_manager'){
                $edcManager = SchoolManager::where('uid', $user->user_id)->first();
                $lotAdminData = PanelUser::where('id', $edcManager->lot_id)->first();
                $query = $query->where('uid', $lotAdminData->user_id)->select(['id', 'uid', 'name'])->get();
            } else {
                $query = $query->select(['id', 'uid', 'name'])->get();
            }
        }

        $returned_data['results'] = $query;
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
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
     * @return \Illuminate\Http\Response
     */
    public function store(NMSLotAdminStoreRequest $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = User::where('auth_id',$request->get('mobile_number'))->exists();
        if($auth_id){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'This mobile number is already in used, Try another!';
            return ResponseWrapper::End($returned_data);
        }

        $panel_auth_id = PanelUser::where('auth_id',$request->get('lot_username'))->exists();
        if($panel_auth_id){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'This username is already in used, Try another!';
            return ResponseWrapper::End($returned_data);
        }

        $admin = PanelUser::where('id',$request->get('auth_id'))->where('base_role','edc_admin')->exists();
        if(!$admin) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'You are not permitted!';
            return ResponseWrapper::End($returned_data);
        }

        $password = (new \App\Classes\CustomHelpers)->generate_strong_password();

        $user = new User();
        $user->auth_id = $request->get('mobile_number');
        $user->status = 'active';
        $user->base_role = 'lot_admin';
        $user->panel_access = 1;
        $user->password = Hash::make($password);
        $user->text_password = $password;
        $user->save();

        $user_id = User::where('auth_id', $request->get('mobile_number'))->value('id');

        $panelUser = new PanelUser();
        $panelUser->auth_id = $request->get('lot_username');
        $panelUser->user_id = $user_id;
        $panelUser->status = 'active';
        $panelUser->base_role = 'lot_admin';
        $panelUser->panel_access = 1;
        $panelUser->password = Hash::make($password);
        $panelUser->text_password = $password;
        $panelUser->save();

        $uid = User::where('auth_id', $request->get('mobile_number'))->value('id');

        $userProfile = new UserProfile();
        $userProfile->uid = $uid;
        $userProfile->full_name = $request->get('name');
        $userProfile->mobile_number = $request->get('mobile_number');
        $userProfile->whatsapp_number = $request->get('whatsapp_number');
        $userProfile->email = $request->get('email');
        // $userProfile->profession = $request->get('profession');
        // $userProfile->nid = $request->get('nid');
        // $userProfile->gender = $request->get('gender');
        $userProfile->division_id = $request->get('division');
        $userProfile->district_id = $request->get('district');
        $userProfile->upazila_id = $request->get('upazila');
        $userProfile->union_id = $request->get('union');
        $userProfile->village_id = $request->get('village');
        // $userProfile->address = $request->get('address');
        $userProfile->address_direction = $request->get('address_direction');
        $userProfile->latitude = $request->get('latitude');
        $userProfile->longitude = $request->get('longitude');
        $userProfile->device_info = json_encode(["brand"=>"erp"]);
        $userProfile->save();

        $lotAdmin = new NMSLotAdmin();
        $lotAdmin->uid = $uid;
        $lotAdmin->name = $request->get('name');
        $lotAdmin->mobile_number = $request->get('mobile_number');
        $lotAdmin->whatsapp_number = $request->get('whatsapp_number');
        $lotAdmin->email = $request->get('email');
        $lotAdmin->lot_username = $request->get('lot_username');
        $lotAdmin->lot_isp_name = $request->get('lot_isp_name');
        $lotAdmin->proprietor_name = $request->get('proprietor_name');
        $lotAdmin->proprietor_mobile = $request->get('proprietor_mobile');
        $lotAdmin->proprietor_email = $request->get('proprietor_email');
        $lotAdmin->bank_name = $request->get('bank_name');
        $lotAdmin->bank_account_name = $request->get('bank_account_name');
        $lotAdmin->bank_account_number = $request->get('bank_account_number');
        $lotAdmin->bank_branch_address = $request->get('bank_branch_address');
        $lotAdmin->installation_cost = $request->get('installation_cost');
        $lotAdmin->package_id = $request->get('package_id');
        $lotAdmin->division_id = $request->get('division');
        $lotAdmin->district_id = $request->get('district');
        $lotAdmin->upazila_id = $request->get('upazila');
        $lotAdmin->union_id = $request->get('union');
        $lotAdmin->village_id = $request->get('village');
        $lotAdmin->address_direction = $request->get('address_direction');
        $lotAdmin->latitude = $request->get('latitude');
        $lotAdmin->longitude = $request->get('longitude');
        $lotAdmin->status = 'active';
        $lotAdmin->created_by = $request->get('auth_id');
        $lotAdmin->updated_by = $request->get('auth_id');
        $lotAdmin->save();

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Lot Admin Added Successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\NMSLotAdmin  $nMSLotAdmin
     * @return \Illuminate\Http\Response
     */
    public function show($uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = NMSLotAdmin::with(['panel_users:id,panel_access','package'])->where('uid', $uid)->get();
        foreach ($query as $lotAdmin) {
            $lotAdmin->setAttribute('division', GeoDivision::where('id', $lotAdmin->division_id)->value('en_name'));
            $lotAdmin->setAttribute('district', GeoDistrict::where('id', $lotAdmin->district_id)->value('en_name'));
            $lotAdmin->setAttribute('upazila', GeoUpazila::where('id', $lotAdmin->upazila_id)->value('en_name'));
            $lotAdmin->setAttribute('union', GeoUnionPouroshova::where('id', $lotAdmin->union_id)->value('en_name'));
        }

        if (!$query) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Lot admin not found';
            return ResponseWrapper::End($returned_data);
        }

        $returned_data['status'] = 'success';
        $returned_data['results'] = $query;
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\NMSLotAdmin  $nMSLotAdmin
     * @return \Illuminate\Http\Response
     */
    public function edit(NMSLotAdmin $nMSLotAdmin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\NMSLotAdmin  $nMSLotAdmin
     * @return \Illuminate\Http\Response
     */
    public function update(NMSLotAdminUpdateRequest $request, $uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // ----------------------------------------------------------------
        // lot admin table --------------------------------------
        // ----------------------------------------------------------------
        $lotAdmin = NMSLotAdmin::where('uid', $uid)->first();
        $lotAdmin->update([
            'name' => $request->get('name'),
            'mobile_number' => $request->get('mobile_number'),
            'whatsapp_number' => $request->get('whatsapp_number'),
            'email' => $request->get('email'),
            // 'lot_username' => $request->get('lot_username'),
            'lot_isp_name' => $request->get('lot_isp_name'),
            'proprietor_name' => $request->get('proprietor_name'),
            'proprietor_mobile' => $request->get('proprietor_mobile'),
            'proprietor_email' => $request->get('proprietor_email'),
            'bank_name' => $request->get('bank_name'),
            'bank_account_name' => $request->get('bank_account_name'),
            'bank_account_number' => $request->get('bank_account_number'),
            'bank_branch_address' => $request->get('bank_branch_address'),
            'installation_cost' => $request->get('installation_cost'),
            'package_id' => $request->get('package_id'),
            'division_id' => $request->get('division'),
            'district_id' => $request->get('district'),
            'upazila_id' => $request->get('upazila'),
            'union_id' => $request->get('union'),
            'village_id' => $request->get('village'),
            'address_direction' => $request->get('address_direction'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'updated_by' => $request->get('auth_id'),
        ]);

        $userProfile = UserProfile::where('uid', $uid)->first();
        if (!$userProfile) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'User profile not found!';
            return ResponseWrapper::End($returned_data);
        }
        $userProfile->update([
            'full_name' => $request->get('name'),
            'mobile_number' => $request->get('mobile_number'),
            'whatsapp_number' => $request->get('whatsapp_number'),
            'email' => $request->get('email'),
            'division_id' => $request->get('division'),
            'district_id' => $request->get('district'),
            'upazila_id' => $request->get('upazila'),
            'union_id' => $request->get('union'),
            'village_id' => $request->get('village'),
            'address_direction' => $request->get('address_direction'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'device_info' => json_encode(["brand"=>"erp"]),
        ]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Lot Admin updated successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\NMSLotAdmin  $nMSLotAdmin
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        NMSLotAdmin::where('uid', $id)->delete();
        UserProfile::where('uid', $id)->delete();
        PanelUser::where('id', $id)->delete();

        $returned_data['status']  = 'success';
        $returned_data['message'] = "Lot Admin Deleted Successfully!";

        return ResponseWrapper::End($returned_data);
    }
}