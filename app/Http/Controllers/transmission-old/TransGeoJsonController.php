<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\TransJson;
use App\Models\TransPop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransGeoJsonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = TransJson::all();
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
    public function show($pop_code) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $transId = TransPop::where('pop_code', $pop_code)->value('id');
        $query = TransJson::where('trans_id', $transId)->get();
        foreach ($query as $dataSet) {
            $dataSet->setAttribute('pop_code', TransPop::where('id', $dataSet->trans_id)->value('pop_code'));
            $dataSet->setAttribute('file_path', $dataSet->file ? '/trans/jsons/'.$dataSet->file : null);
        }
        $returned_data['status'] = 'success';
        $returned_data['results']['details'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    // json file store
    public function store(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'file' => 'required',
            'file.*' => 'mimes:geojson'
        ]);

        if (!$validated) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        $auth_id = $request->get('auth_id');
        $transId = TransPop::where('pop_code', $request->get('pop_code'))->value('id');

        $file = $request->file('file');
        $fileName = null;
        if ($file) {
            $fileExists = TransJson::where('trans_id', $transId)->value('file');
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
    public function delete($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $fileExists = TransJson::where('id', $id)->value('file');
        if ($fileExists) {
            // Delete file
            $filePath = public_path('trans/jsons/' . $fileExists);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // delete the data
        TransJson::where('id', $id)->delete();

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'File deleted successfully!';
        return ResponseWrapper::End($returned_data);
    }
}
