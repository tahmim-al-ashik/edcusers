<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\InternetUsers;
use App\Models\TransJson;
use App\Models\TransPop;
use App\Models\User;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransGeoJsonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($auth_id) : JsonResponse
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

        $query = TransJson::query();
        if($base_role !== 'admin'){
            $query = $query->where('zone_id', $zone_id);
        }
        $query = $query->get();

        foreach ($query as $dataSet) {
            $dataSet->setAttribute('pop_code', TransPop::where('id', $dataSet->trans_id)->value('pop_code'));
            $dataSet->setAttribute('file_path', $dataSet->file ? '/trans/jsons/'.$dataSet->file : null);
        }

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($auth_id, $pop_code) : JsonResponse
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

        $transId = TransPop::query();
        if($base_role !== 'admin'){
            $transId = $transId->where('zone_id', $zone_id);
        }
        $transId = $transId->where('pop_code', $pop_code)->value('id');

        $query = TransJson::query();
        if($base_role !== 'admin'){
            $query = $query->where('zone_id', $zone_id);
        }
        $query = $query->where('trans_id', $transId)->get();
        foreach ($query as $dataSet) {
            $dataSet->setAttribute('pop_code', TransPop::where('id', $dataSet->trans_id)->value('pop_code'));
            $dataSet->setAttribute('file_path', $dataSet->file ? '/trans/jsons/'.$dataSet->file : null);
        }
        $returned_data['status'] = 'success';
        $returned_data['results']['details'] = $query;
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

        Log::info($request);

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

        $auth_id = $request->get('auth_id');
        $transId = TransPop::where('pop_code', $request->get('pop_code'))->where('zone_id', $zone_id)->value('id');

        $file = $request->file('file');
        $fileName = null;
        if ($file) {
            $fileExists = TransJson::where('trans_id', $transId)->where('zone_id', $zone_id)->value('file');
            if ($fileExists) {
                // Delete existing image
                $filePath = public_path('trans/jsons/' . $fileExists);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Upload new image
            $fileName = date('YmdHi') . '-' . $file->getClientOriginalName();
            $file->move(public_path('trans/jsons'), $fileName);
        }

        $transJson = TransJson::updateOrCreate(
            ['trans_id' => $transId],
            ['zone_id' => $zone_id],
            [
                'module_type' => 'branch',
                'created_by' => $auth_id,
                'updated_by' => $auth_id,
            ]
        );

        $transJson->update(['file'=>$fileName]);

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

        $fileExists = TransJson::query();
        if($base_role !== 'admin'){
            $fileExists = $fileExists->where('zone_id', $zone_id);
        }
        $fileExists = $fileExists->where('id', $id)->value('file');
        if ($fileExists) {
            // Delete file
            $filePath = public_path('trans/jsons/' . $fileExists);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // delete the data
        $query = TransJson::query();
        if($base_role !== 'admin'){
            $query = $query->where('zone_id', $zone_id);
        }
        $query = $query->where('id', $id)->delete();

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'File deleted successfully!';
        return ResponseWrapper::End($returned_data);
    }
}
