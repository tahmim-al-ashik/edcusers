<?php

namespace App\Http\Controllers\panel\internet_user;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PanelPermissionController extends Controller
{
    public function getPermissionList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = Permission::orderBy('group_name','asc')->get();
        $total = Permission::count();

        $returned_data['results']['total'] = $total;
        $returned_data['results']['list'] = $query->map(function($permission) {
            return [
                'pid' => $permission->id,
                'name' => $permission->name,
                'group_name' => $permission->group_name,
                'key_name' => $permission->key_name,
                'module_names' => json_decode($permission->module_names)
            ];
        });

        return ResponseWrapper::End($returned_data);
    }

    public function createPermission(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        if ($request->get('key_name')) {
            $check = Permission::where('key_name', $request->get('key_name'))->exists();
            if($check){
                $returned_data['message'] = "This permission already exists.";
                return ResponseWrapper::End($returned_data);
            }
        }

        $request->validate([
            'group_name' => 'required',
            'key_name' => 'required',
            'module_names' => 'required',
            'name' => 'required'
        ],[
            'group_name' => 'Group name is required.',
            'key_name' => 'Key name is required.',
            'module_names' => 'Module is required.',
            'name' => 'Name is required.'
        ]);

        // proceed to create new permission
        $query = new Permission();
        $query->group_name = $request->get('group_name');
        $query->key_name = $request->get('key_name');
        $query->module_names = json_encode($request->get('module_names'));
        $query->name = $request->get('name');
        $query->save();

        // success message to returned data
        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Permission created successfully.';

        $returned_data['results'] = $query;

        return ResponseWrapper::End($returned_data);
    }

    public function updatePermission(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $permission = Permission::find($request->get('pid'));

        if (!$permission) {
            $returned_data['message'] = "Permission not found.";
            return ResponseWrapper::End($returned_data);
        }

        if ($request->get('key_name') !== $permission->key_name) {
            $check = Permission::where('key_name', $request->get('key_name'))->exists();
            if($check){
                $returned_data['message'] = "This permission key already exists.";
                return ResponseWrapper::End($returned_data);
            }
        }

        // Update permission
        $permission->group_name = $request->get('group_name');
        $permission->key_name = $request->get('key_name');
        $permission->module_names = json_encode($request->get('module_names'));
        $permission->name = $request->get('name');
        $permission->save();

        // success message to returned data
        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Permission updated successfully.';
        $returned_data['results'] = $permission;

        return ResponseWrapper::End($returned_data);
    }

    public function deletePermission($id, $key_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Delete permission
        $permissionDeleted = Permission::where('id', $id)->delete();

        // Delete associated user permissions
        $userPermissionsDeleted = UserPermission::where('name', $key_name)->delete();

        if ($permissionDeleted) {
            if ($userPermissionsDeleted || $userPermissionsDeleted === 0) {
                $returned_data['results'] = true;
            } else {
                $returned_data['results'] = false;
            }
        } else {
            $returned_data['results'] = false;
        }

        return ResponseWrapper::End($returned_data);
    }
}



