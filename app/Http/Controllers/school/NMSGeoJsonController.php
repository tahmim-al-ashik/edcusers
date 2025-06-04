<?php

namespace App\Http\Controllers\school;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\PanelUser;
use App\Models\School\NMSJson;
use App\Models\School\NMSLotAdmin;
use App\Models\School\SchoolManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NMSGeoJsonController extends Controller
{
    public function index(Request $request, $institution_type) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $auth_id = $request->get('auth');

        $user = PanelUser::find($auth_id);

        if(!$user) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'No user found!';
            $returned_data['results'] = [];
            return ResponseWrapper::End($returned_data);
        }

        $query = NMSJson::query();
        if($user->base_role === 'lot_admin'){
            $query = $query->where('lot_uid', $user->user_id);
        }
        if($user->base_role === 'edc_manager'){
            $lot_id = SchoolManager::where('uid', $user->user_id)->value('lot_id');
            $query = $query->where('lot_id', $lot_id);
        }
        if($institution_type !== 'all'){
            $query = $query->where('institution_type', $institution_type);
        }
        $query = $query->get();

        foreach ($query as $dataSet) {
            $dataSet->setAttribute('file_title', (NMSLotAdmin::where('uid', $dataSet->lot_uid)->value('name')) .' - ' .$dataSet->institution_type);
            $dataSet->setAttribute('file_path', $dataSet->file ? '/school/json/'.$dataSet->file : null);
        }

        $returned_data['status'] = 'success';
        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    public function listByIdAndTypes(Request $request, $lot_uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $institution_types = explode(',', $request->get('institution_types'));
        $user = PanelUser::where('user_id', $lot_uid)->first();
        
        if(!$user || empty($institution_types)) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'No Data found!';
            $returned_data['results'] = [];
            return ResponseWrapper::End($returned_data);
        }
        
        $query = NMSJson::where('lot_id', $user->id)->whereIn('institution_type', $institution_types)->get();

        foreach ($query as $dataSet) {
            $dataSet->setAttribute('file_title', (NMSLotAdmin::where('uid', $dataSet->lot_uid)->value('name')) .' - ' .$dataSet->institution_type);
            $dataSet->setAttribute('file_path', $dataSet->file ? '/school/json/'.$dataSet->file : null);
        }

        $returned_data['status'] = 'success';
        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($lot_uid, $institution_type) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        if(!$lot_uid || !$institution_type){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'Please Select Required Fields!';
            $returned_data['results'] = null;
            return ResponseWrapper::End($returned_data);
        }

        $query = NMSJson::where('lot_uid', $lot_uid)->where('institution_type', $institution_type)->first();

        if(!$query){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'No Data Found!';
            $returned_data['results'] = null;
            return ResponseWrapper::End($returned_data);
        }

        $query->setAttribute('file_path', $query->file ? '/school/json/'.$query->file : null);

        $returned_data['status'] = 'success';
        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    // json file store
    public function store(Request $request, $auth_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'file' => 'required',
            'file.*' => 'mimes:geojson'
        ]);

        $institution_type = $request->get('institution_type');

        if (!$validated) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        if (!$auth_id) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Lot not found!";
            return ResponseWrapper::End($returned_data);
        }

        $panelUser = PanelUser::find($auth_id);
        if($panelUser->base_role === 'lot_admin'){
            $lot_id = $panelUser->id;
        } else if($panelUser->base_role === 'edc_manager') {
            $lot_id = SchoolManager::where('uid', $panelUser->user_id)->value('lot_id');
        } else {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = "You are not permitted!";
            return ResponseWrapper::End($returned_data);
        }

        $file = $request->file('file');
        $fileName = null;
        if ($file) {
            $fileExists = NMSJson::where('lot_id', $auth_id)->where('institution_type', $institution_type)->value('file');
            if (!is_null($fileExists) && trim($fileExists) !== '') {
                // Delete existing image
                $filePath = public_path('school/json/' . $fileExists);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Upload new image
            $fileName = date('YmdHi') . '-' . $file->getClientOriginalName();
            $file->move(public_path('school/json'), $fileName);
        }

        $nmsJson = NMSJson::updateOrCreate(
            ['lot_id' => $lot_id, 'institution_type' => $institution_type],
            [
                'lot_uid' => $request->get('lot_uid'),
                'created_by' => $auth_id,
                'updated_by' => $auth_id,
            ]
        );

        $nmsJson->update(['file'=>$fileName]);

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'File uploaded successfully!';
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($auth_id, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $user = PanelUser::find($auth_id);

        if($user->base_role !== 'lot_admin') {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'Permission Denied!';
            $returned_data['results'] = [];
            return ResponseWrapper::End($returned_data);
        }

        $query = NMSJson::find($id);

        if(!$query) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'No Data Found!';
            $returned_data['results'] = [];
            return ResponseWrapper::End($returned_data);
        }

        if ($query->file) {
            // Delete file
            $filePath = public_path('/school/json/' .$query->file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $query->delete();

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'File deleted successfully!';
        return ResponseWrapper::End($returned_data);
    }
}
