<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\CorporateClient;
use App\Models\InternetUsers;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function updateStatus(Request $request, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = Employee::where('uid', '=', $uid)->update(['status'=>$request->get('status')]);
        if (CorporateClient::where('uid', $uid)->exists()) {
            CorporateClient::where('uid', '=', $uid)->update(['status'=>$request->get('status')]);
        }
        return ResponseWrapper::End($returned_data);
    }

    public function updateDesignation(Request $request, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $result = Employee::where('uid', '=', $uid)->update(['designation'=>$request->get('designation')]);
        if($result){
            $returned_data['results'] = EmployeeDesignation::find($request->get('designation'));
        }
        return ResponseWrapper::End($returned_data);
    }

    public function updatePanelAccess(Request $request, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = User::where('id', '=', $uid)->update(['panel_access'=>$request->get('panel_access')]);
        return ResponseWrapper::End($returned_data);
    }

    public function getEmployeeList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        // get user list on partner area
        $query = Employee::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'employees.uid');
        $query->leftJoin('users', 'users.id', '=', 'employees.uid');
        $query->leftJoin('employee_designations', 'employee_designations.id', '=', 'employees.designation');
        if($request->get('status') !== 'all'){
            $query->where('status', '=', $request->get('status'));
        }
        $query->orderBy('employees.created_at', $sortBy);
        $query->skip($totalSkip)->take(50);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get(['employee_designations.name as designation','employees.id','employees.uid', 'employees.status','employees.created_at','user_profiles.full_name','user_profiles.email','user_profiles.mobile_number','user_profiles.wallet_amount','users.base_role']);

        return ResponseWrapper::End($returned_data);

    }


    public function createNewEmployee(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        return ResponseWrapper::End($returned_data);
    }


    public function getEmployeeDetails(Request $request, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $result = Employee::where('uid', '=', $uid)->first();
        if(!empty($result)){
            $result['designation'] = EmployeeDesignation::where('id', $result['designation'])->first();
        }
        $result->profile = UserProfile::where('uid', '=', $uid)->first();
        $result->zone_id = InternetUsers::where('uid', '=', $uid)->value('zone_id');
        $result->panel_access = User::where('id', '=', $uid)->value('panel_access');
        $returned_data['results'] = $result;

        return ResponseWrapper::End($returned_data);
    }

    public function checkEmployment(Request $request, $uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $result = Employee::where('uid', '=', $uid)->first();
        if(!empty($result)){
            $result['designation'] = EmployeeDesignation::where('id', $result['designation'])->first();
            $result['panel_access'] = User::where('id', '=', $uid)->value('panel_access');
        }
        $returned_data['results'] = $result;
        $returned_data['designations'] = EmployeeDesignation::all();

        return ResponseWrapper::End($returned_data);
    }
}
