<?php

namespace App\Http\Controllers\school;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\SchoolManagerStoreRequest;
use App\Http\Requests\School\SchoolManagerUpdateRequest;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\PanelUser;
use App\Models\Permission;
use App\Models\School\NMSCategoryBasedAdmin;
use App\Models\School\NMSLotAdmin;
use App\Models\School\SchoolManager;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Xenon\LaravelBDSms\Request as LaravelBDSmsRequest;

class SchoolManagerController extends Controller
{
    // School Manager List
    public function index(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $status = $request->get('status');
        $lot_id = $request->get('lot_id');
        $per_page = (int) $request->get('per_page') ?? 10;
        $search = strtolower(trim($request->get('search')));
        $sorting_id = $request->get('sorting_id') ?? 'created_at';
        $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');
        $auth_id = $request->get('auth');

        $user = PanelUser::where('id', $auth_id)->first();

        // Base Query
        $query = SchoolManager::with([
        'user_profiles:uid,full_name,address_direction',
        'panel_users:user_id,auth_id,text_password,id',
        'panel_lot_admin:id,user_id',
        'panel_lot_admin.lot_admin:uid,id,name'
        ])->select([
            'school_managers.id',
            'school_managers.uid',
            'school_managers.lot_id',
            'school_managers.status',
            'school_managers.created_at'
        ]);

        if($user){
            if($user->base_role === 'lot_admin'){
                $query = $query->where('school_managers.lot_id', $user->id);
            } else if($user->base_role === 'category_admin'){
                $categoryAdmin = NMSCategoryBasedAdmin::where('uid', $user->user_id)->first();
                $query = $query->where('lot_id', $categoryAdmin->lot_id);
            }
        }

        // Apply filters
        if ($division_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
        }
        if ($district_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
        }
        if ($upazila_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
        }
        if ($union_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($lot_id) {
            $query->whereHas('panel_lot_admin', fn($subQuery) => $subQuery->where('id', $lot_id));
        }
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('status', 'LIKE', "%{$search}%")
                    ->orWhereHas('user_profiles', fn($subSubQuery) => $subSubQuery->where('address_direction', 'LIKE', "%{$search}%"))
                    ->orWhereHas('user_profiles', fn($subSubQuery) => $subSubQuery->where('full_name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('panel_users', fn($subSubQuery) => $subSubQuery->where('auth_id', 'LIKE', "%{$search}%"))
                    ->orWhereHas('panel_lot_admin.lot_admin', fn($subSubQuery) => $subSubQuery->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // Sorting logic
        if ($sorting_id === 'address') {
            $query->join('user_profiles', 'school_managers.uid', '=', 'user_profiles.uid')->orderBy('user_profiles.address_direction', $sorting_direction);
        } elseif ($sorting_id === 'full_name') {
            $query->join('user_profiles', 'school_managers.uid', '=', 'user_profiles.uid')->orderBy('user_profiles.full_name', $sorting_direction);
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

    // School Manager List
    // public function schoolManagersAccordingToLot($lot_id): JsonResponse
    // {
    //     $returned_data = ResponseWrapper::Start();

    //     // Base Query
    //     $query = SchoolManager::where('lot_id', $lot_id)->with(['user_profiles:uid,full_name'])->get([
    //         'id',
    //         'uid',
    //         'status',
    //         'created_at'
    //     ]);

    //     $returned_data['results'] = $query; // Paginated and filtered data
    //     $returned_data['status'] = 'success';

    //     return ResponseWrapper::End($returned_data);
    // }

    // School Manager Show
    public function show($uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $panelUser = PanelUser::where('user_id',$uid)->first();
        $query = SchoolManager::with(['user_profiles:uid,full_name,mobile_number,whatsapp_number,email,division_id,district_id,upazila_id,union_id,village_id,latitude,longitude,address_direction','user:id,panel_access'])->where('uid', $uid)->get();
        foreach ($query as $schoolManager) {
            $schoolManager->setAttribute('username', $panelUser->auth_id);
            $schoolManager->setAttribute('division', GeoDivision::where('id', $schoolManager->user_profiles->division_id)->value('en_name'));
            $schoolManager->setAttribute('district', GeoDistrict::where('id', $schoolManager->user_profiles->district_id)->value('en_name'));
            $schoolManager->setAttribute('upazila', GeoUpazila::where('id', $schoolManager->user_profiles->upazila_id)->value('en_name'));
            $schoolManager->setAttribute('union', GeoUnionPouroshova::where('id', $schoolManager->user_profiles->union_id)->value('en_name'));
            $schoolManager->setAttribute('village', GeoVillage::where('id', $schoolManager->user_profiles->village_id)->value('en_name'));
            $schoolManager->setAttribute('working_division', GeoDivision::where('id', $schoolManager->assigned_division_id)->value('en_name'));
            $schoolManager->setAttribute('working_district', GeoDistrict::where('id', $schoolManager->assigned_district_id)->value('en_name'));
            $schoolManager->setAttribute('working_upazila', GeoUpazila::where('id', $schoolManager->assigned_upazila_id)->value('en_name'));
            $schoolManager->setAttribute('working_union', GeoUnionPouroshova::where('id', $schoolManager->assigned_union_id)->value('en_name'));
            $schoolManager->setAttribute('working_village', GeoVillage::where('id', $schoolManager->assigned_village_id)->value('en_name'));
            $schoolManager->setAttribute('profile_image', $schoolManager->profile_image ? '/school/profile/'.$schoolManager->profile_image : null);
        }

        if (!$query) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'School manager not found';
            return ResponseWrapper::End($returned_data);
        }

        $returned_data['status'] = 'success';
        $returned_data['results'] = $query;
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    // School Manager Store
    public function store(SchoolManagerStoreRequest $request) : JsonResponse
    {
            $returned_data = ResponseWrapper::Start();

            $auth_id = User::where('auth_id',$request->get('mobile_number'))->exists();
            if($auth_id){
                $returned_data['status'] = 'error';
                $returned_data['error_type'] = 'general';
                $returned_data['message'] = 'This number is already in used, Try another!';
                return ResponseWrapper::End($returned_data);
            }

            $manager = PanelUser::where('id',$request->get('auth_id'))->where('base_role','lot_admin')->exists();
            if(!$manager){
                $returned_data['status'] = 'error';
                $returned_data['error_type'] = 'general';
                $returned_data['message'] = 'You are not permitted!';
                return ResponseWrapper::End($returned_data);
            }

            $password = (new \App\Classes\CustomHelpers)->generate_strong_password();

            $user = new User();
            $user->auth_id = $request->get('mobile_number');
            $user->status = 'active';
            $user->base_role = 'edc_manager';
            $user->panel_access = 0;
            $user->password = Hash::make($password);
            $user->text_password = $password;
            $user->save();

            $uid = User::where('auth_id', $request->get('mobile_number'))->first();

            $panelUser = new PanelUser();
            $panelUser->auth_id = $request->get('username');
            $panelUser->user_id = $uid->id;
            $panelUser->status = 'active';
            $panelUser->base_role = 'edc_manager';
            $panelUser->panel_access = 1;
            $panelUser->password = $uid->password;
            $panelUser->text_password = $uid->text_password;
            $panelUser->save();

            // create new profile
            $userProfile = new UserProfile();
            $userProfile->uid = $uid->id;
            $userProfile->full_name = $request->get('name');
            $userProfile->mobile_number = $request->get('mobile_number');
            $userProfile->whatsapp_number = $request->get('whatsapp');
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

            $schoolProfile = new SchoolManager();
            $schoolProfile->uid = $uid->id;
            $schoolProfile->lot_id = $request->get('auth_id');
            $schoolProfile->status = 'pending';
            $schoolProfile->created_by = $request->get('auth_id');
            $schoolProfile->updated_by = $request->get('auth_id');
            $schoolProfile->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'School Manager Added Successfully!';
            $returned_data['results'] = $uid->id;
            return ResponseWrapper::End($returned_data);
    }

    public function update(SchoolManagerUpdateRequest $request, $uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // $manager = User::where('id',$request->get('auth_id'))->where('base_role','manager')->exists();
        // if(!$manager){
        //     $returned_data['status'] = 'error';
        //     $returned_data['error_type'] = 'general';
        //     $returned_data['message'] = 'You are not permitted!';
        //     return ResponseWrapper::End($returned_data);
        // }

        // ----------------------------------------------------------------
        // update user profile table --------------------------------------
        // ----------------------------------------------------------------
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
            'whatsapp_number' => $request->get('whatsapp'),
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
        $returned_data['message'] = 'School manager updated successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    public function delete($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        SchoolManager::where('uid', $id)->delete();
        UserProfile::where('uid', $id)->delete();
        User::where('id', $id)->delete();

        $returned_data['status']  = 'success';
        $returned_data['message'] = "School Manager Deleted Successfully!";

        return ResponseWrapper::End($returned_data);
    }

    public function permissions(): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = Permission::where('group_name','school')->get();
        $returned_data['results'] = $query;
        $returned_data['status'] = 'success';
        return ResponseWrapper::End($returned_data);
    }

    public function access(Request $request, $uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $user = User::where('id', $uid)->update(['panel_access' => $request->get('panel_access')]);

        $schoolManager = SchoolManager::where('uid', $uid)->first();
        $schoolManager->update([
            'manager_type' => $request->get('manager_type'),
            'assigned_division_id' => $request->get('division'),
            'assigned_district_id' => $request->get('district'),
            'assigned_upazila_id' => $request->get('upazila'),
            'assigned_union_id' => $request->get('union'),
            'mikrotik_ip' => $request->get('mk_ip'),
            'mikrotik_username' => $request->get('mk_username'),
            'mikrotik_password' => $request->get('mk_password'),
            'status' => $request->get('panel_access') === 1 ? 'active' : ($request->get('panel_access') === 0 ? 'suspended':'pending'),
            'created_by' => $request->get('auth_id'),
            'updated_by' => $request->get('auth_id')
        ]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'School manager updated successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    public function profileUpdate(Request $request, $uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $user = PanelUser::where('id', $uid)->first();
        $userProfile = UserProfile::where('uid', $user->user_id)->first();
        $userProfile->update([
            'full_name' => $request->get('name'),
            'mobile_number' => $request->get('mobile_number'),
            'whatsapp_number' => $request->get('whatsapp'),
            'email' => $request->get('email'),
            'division_id' => $request->get('division'),
            'district_id' => $request->get('district'),
            'upazila_id' => $request->get('upazila'),
            'union_id' => $request->get('union'),
            'village_id' => $request->get('village'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'address_direction' => $request->get('address_direction')
        ]);

        $image = $request->file('profile_image');
        $schoolManager = SchoolManager::where('uid', $user->user_id)->first();
        $imageName = null;

        if ($image) {
            $managerImageExists = SchoolManager::where('uid', $user->user_id)->value('profile_image');
            if ($managerImageExists !== $image->getClientOriginalName()) {
                if ($managerImageExists) {
                    // Delete existing image
                    $existingImagePath = public_path('school/profile/' . $managerImageExists);
                    if (file_exists($existingImagePath)) {
                        unlink($existingImagePath);
                    }
                }

                // Upload new image
                $imageName = date('YmdHi') . '-' . $image->getClientOriginalName();
                $image->move(public_path('school/profile'), $imageName);
            }
        } else {
            $imageName = SchoolManager::where('uid', $user->user_id)->value('profile_image');
        }

        $schoolManager->update(['profile_image'=>$imageName]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'School manager updated successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    public function passwordUpdate(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $uid =  PanelUser::where('id', $request->get('uid'))->value('user_id');
        $password = $request->get('password');

        $user = User::find($uid);
        $user->password = Hash::make($password);
        $user->text_password = $password;
        $user->save();

        $panelUser = PanelUser::where('user_id', $uid)->first();
        $panelUser->password = $user->password;
        $panelUser->text_password = $user->text_password;
        $panelUser->save();

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Password changed successfully!';
        return ResponseWrapper::End($returned_data);
    }

    public function profileDetails($uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $panelUser = PanelUser::where('id',$uid)->first();
        $baseRole = User::where('id',$panelUser->user_id)->value('base_role');
        if($baseRole === 'edc_manager'){
            $query = SchoolManager::with(['user_profiles:uid,full_name,mobile_number,whatsapp_number,email,division_id,district_id,upazila_id,union_id,village_id,latitude,longitude,address_direction','user:id,panel_access'])->where('uid', $panelUser->user_id)->get();
            foreach ($query as $schoolManager) {
                $schoolManager->setAttribute('division', GeoDivision::where('id', $schoolManager->user_profiles->division_id)->value('en_name'));
                $schoolManager->setAttribute('district', GeoDistrict::where('id', $schoolManager->user_profiles->district_id)->value('en_name'));
                $schoolManager->setAttribute('upazila', GeoUpazila::where('id', $schoolManager->user_profiles->upazila_id)->value('en_name'));
                $schoolManager->setAttribute('union', GeoUnionPouroshova::where('id', $schoolManager->user_profiles->union_id)->value('en_name'));
                $schoolManager->setAttribute('village', GeoVillage::where('id', $schoolManager->user_profiles->village_id)->value('en_name'));
                $schoolManager->setAttribute('working_division', GeoDivision::where('id', $schoolManager->assigned_division_id)->value('en_name'));
                $schoolManager->setAttribute('working_district', GeoDistrict::where('id', $schoolManager->assigned_district_id)->value('en_name'));
                $schoolManager->setAttribute('working_upazila', GeoUpazila::where('id', $schoolManager->assigned_upazila_id)->value('en_name'));
                $schoolManager->setAttribute('working_union', GeoUnionPouroshova::where('id', $schoolManager->assigned_union_id)->value('en_name'));
                $schoolManager->setAttribute('working_village', GeoVillage::where('id', $schoolManager->assigned_village_id)->value('en_name'));
                $schoolManager->setAttribute('profile_image', $schoolManager->profile_image ? '/school/profile/'.$schoolManager->profile_image : null);
            }

            if (!$query) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'School manager not found';
                return ResponseWrapper::End($returned_data);
            }

            $returned_data['status'] = 'success';
            $returned_data['results'] = $query;
        } else{
            $query = User::where('id', $panelUser->user_id)->with('user_profiles:uid,full_name,mobile_number,whatsapp_number,email,division_id,district_id,upazila_id,union_id,village_id,latitude,longitude,address_direction')->get();
            foreach ($query as $user) {
                $user->setAttribute('division', GeoDivision::where('id', $user->user_profiles->division_id)->value('en_name'));
                $user->setAttribute('district', GeoDistrict::where('id', $user->user_profiles->district_id)->value('en_name'));
                $user->setAttribute('upazila', GeoUpazila::where('id', $user->user_profiles->upazila_id)->value('en_name'));
                $user->setAttribute('union', GeoUnionPouroshova::where('id', $user->user_profiles->union_id)->value('en_name'));
                $user->setAttribute('village', GeoVillage::where('id', $user->user_profiles->village_id)->value('en_name'));
            }

            $returned_data['status'] = 'success';
            $returned_data['results'] = $query;
        }
        return ResponseWrapper::End($returned_data);
    }


    public function schoolManagersAccordingToLot($auth_id): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $panelUser = PanelUser::find($auth_id);

        if(!$panelUser) {
            $returned_data['error_type'] = 'No user found!';
            $returned_data['status'] = 'error';

            return ResponseWrapper::End($returned_data);
        }

        $query = SchoolManager::with(['user_profiles:uid,full_name']);

        if($panelUser->base_role === 'edc_manager'){
            $query = $query->where('uid', $panelUser->user_id);
        }
        if($panelUser->base_role === 'lot_admin') {
            $query = $query->where('lot_id', $panelUser->id);
        }

        // Base Query
        $query = $query->get([
            'id',
            'uid',
            'status',
            'created_at'
        ]);

        $returned_data['results'] = $query; // Paginated and filtered data
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }
}
