<?php

namespace App\Http\Controllers\school;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\NMSCategoryBasedAdminStoreRequest;
use App\Http\Requests\School\NMSCategoryBasedAdminUpdateRequest;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\PanelUser;
use App\Models\School\NMSCategoryBasedAdmin;
use App\Models\School\NMSLotAdmin;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Database\Factories\PanelUserFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class NMSCategoryBasedAdminController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $per_page = (int) $request->get('per_page') ?? 10;
        $search = strtolower(trim($request->get('search')));
        $sorting_id = $request->get('sorting_id') ?? 'created_at';
        $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $category_type = $request->get('category_type');
        $auth_id = $request->get('auth');

        $user = PanelUser::find($auth_id);

        // Base Query
        $query = NMSCategoryBasedAdmin::with([
            'user_profiles:uid,full_name,division_id,district_id,upazila_id,union_id',
            'panel_users:id,auth_id,user_id,text_password',
            'users:id,auth_id'
            ])->select([
                'id',
                'uid',
                'category_type',
                'status',
                'created_at'
            ]);

        if ($user && $user->base_role === 'lot_admin'){
            $query = $query->where('lot_id', $auth_id);
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
        if ($category_type) {
            $query->where('category_type', $category_type);
        }

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('category_type', 'LIKE', "%{$search}%")
                    ->orWhereHas('user_profiles', function($subSubQuery) use ($search) {
                        $subSubQuery->where('full_name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('panel_users', function($subSubQuery) use ($search) {
                        $subSubQuery->where('username', 'LIKE', "%{$search}%")
                            ->orWhere('text_password', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Sorting logic
        if ($sorting_id === 'full_name') {
            $query->orderBy('user_profiles.full_name', $sorting_direction);
        } elseif ($sorting_id === 'username') {
            $query->orderBy('panel_users.username', $sorting_direction);
        } elseif ($sorting_id === 'password') {
            $query->orderBy('panel_users.text_password', $sorting_direction);
        } else {
            $query->orderBy($sorting_id, $sorting_direction);
        }

        // Fetch paginated data
        $admins = $query->paginate($per_page);

        // Response structure
        $returned_data['pagination'] = [
            'currentPage' => $admins->currentPage(),
            'perPage' => $admins->perPage(),
            'totalPages' => $admins->lastPage(),
            'totalItems' => $admins->total(),
        ];
        $returned_data['results'] = $admins->items(); // Paginated and filtered data
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }

    public function create()
    {
        //
    }

    public function store(NMSCategoryBasedAdminStoreRequest $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = PanelUser::where('auth_id', $request->get('username'))->exists();
        if($auth_id){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'This username is already in used, Try another!';
            return ResponseWrapper::End($returned_data);
        }

        $admin = PanelUser::where('id',$request->get('auth_id'))->where('base_role','edc_admin')->exists();
        if(!$admin){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'You are not permitted!';
            return ResponseWrapper::End($returned_data);
        }

        $password = (new \App\Classes\CustomHelpers)->generate_strong_password();

        $user = new User();
        $user->auth_id = $request->get('mobile_number');
        $user->status = 'active';
        $user->base_role = 'category_admin';
        $user->panel_access = 1;
        $user->password = Hash::make($password);
        $user->text_password = $password;
        $user->save();

        $uid = User::where('auth_id', $request->get('mobile_number'))->first();

        $panelUser = new PanelUser();
        $panelUser->auth_id = $request->get('username');
        $panelUser->user_id = $uid->id;
        $panelUser->status = 'active';
        $panelUser->base_role = 'category_admin';
        $panelUser->panel_access = 1;
        $panelUser->password = $uid->password;
        $panelUser->text_password = $uid->text_password;
        $panelUser->save();

        $userProfile = new UserProfile();
        $userProfile->uid = $uid->id;
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

        $categoryBasedAdmin = new NMSCategoryBasedAdmin();
        $categoryBasedAdmin->uid = $uid->id;
        $categoryBasedAdmin->lot_id = $request->get('auth_id');
        $categoryBasedAdmin->category_type = $request->get('category_type');
        $categoryBasedAdmin->division_id = $request->get('division');
        $categoryBasedAdmin->district_id = $request->get('district');
        $categoryBasedAdmin->upazila_id = $request->get('upazila');
        $categoryBasedAdmin->union_id = $request->get('union');
        $categoryBasedAdmin->village_id = $request->get('village');
        $categoryBasedAdmin->address_direction = $request->get('address_direction');
        $categoryBasedAdmin->latitude = $request->get('latitude');
        $categoryBasedAdmin->longitude = $request->get('longitude');
        $categoryBasedAdmin->status = 'active';
        $categoryBasedAdmin->created_by = Carbon::now();
        $categoryBasedAdmin->updated_by = Carbon::now();
        $categoryBasedAdmin->save();

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Category Admin Added Successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    public function show($uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = NMSCategoryBasedAdmin::with(['panel_users:user_id,auth_id,status','user_profiles:uid,full_name,mobile_number,whatsapp_number,email'])->where('uid', $uid)->get();
        foreach ($query as $categoryBasedAdmin) {
            $categoryBasedAdmin->setAttribute('division', GeoDivision::where('id', $categoryBasedAdmin->division_id)->value('en_name'));
            $categoryBasedAdmin->setAttribute('district', GeoDistrict::where('id', $categoryBasedAdmin->district_id)->value('en_name'));
            $categoryBasedAdmin->setAttribute('upazila', GeoUpazila::where('id', $categoryBasedAdmin->upazila_id)->value('en_name'));
            $categoryBasedAdmin->setAttribute('union', GeoUnionPouroshova::where('id', $categoryBasedAdmin->union_id)->value('en_name'));
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

    public function edit($id)
    {
        //
    }

    public function update(NMSCategoryBasedAdminUpdateRequest $request, $uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $panelUser = PanelUser::where('user_id', $uid)->first();
        if (!$panelUser) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'User not found!';
            return ResponseWrapper::End($returned_data);
        }
        $panelUser->update([
            'auth_id' => $request->get('username')
        ]);

        $categoryBasedAdmin = NMSCategoryBasedAdmin::where('uid', $uid)->first();
        if (!$categoryBasedAdmin) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'User not found!';
            return ResponseWrapper::End($returned_data);
        }
        $categoryBasedAdmin->update([
            'lot_id' => $request->get('auth_id'),
            'category_type' => $request->get('category_type'),
            'division_id' => $request->get('division'),
            'district_id' => $request->get('district'),
            'upazila_id' => $request->get('upazila'),
            'union_id' => $request->get('union'),
            'village_id' => $request->get('village'),
            'address_direction' => $request->get('address_direction'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude')
        ]);
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
        $returned_data['message'] = 'School manager updated successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    public function destroy($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        NMSCategoryBasedAdmin::where('uid', $id)->delete();
        UserProfile::where('uid', $id)->delete();
        User::where('id', $id)->delete();
        PanelUser::where('user_id', $id)->delete();

        $returned_data['status']  = 'success';
        $returned_data['message'] = "School Manager Deleted Successfully!";

        return ResponseWrapper::End($returned_data);
    }
}
