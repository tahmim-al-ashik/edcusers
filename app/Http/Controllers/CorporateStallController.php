<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Classes\ResponseWrapper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\CorporateAgents;
use App\Models\CorporateClient;
use App\Models\CorporateStall;
use App\Models\User;
use App\Models\Classes\GlobalClasses;
use App\Models\CorporateSubAgents;

class CorporateStallController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }
        $query = CorporateStall::query();
        if(!empty($request->get('is_active'))){
            $query->where('is_active', '=', $request->get('is_active'));
        }
        $query->orderBy('name');
        $returned_data['results'] = $query->get();
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createUpdate(Request $request): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $requestedData = $request->input();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }
        unset($requestedData['access_token']);
        if(CorporateStall::where('username', '=', $requestedData['username'])->exists()){
            $returned_data['results'] = CorporateStall::where('username', '=', $request->get('username'))->update($requestedData);
        }else {
            $returned_data['results'] = CorporateStall::create($requestedData);
        }
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function getStallDetails(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        $returned_data['results'] = CorporateStall::where('id', '=', $id)->first();
        return ResponseWrapper::End($returned_data);
    }

    public function show(Request $request, $username)
    {
        $returned_data = ResponseWrapper::Start();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }
        $returned_data['results'] = CorporateStall::where('username', '=', $username)->first();
        return ResponseWrapper::End($returned_data);
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }
        $client = CorporateStall::where('id', '=', $id)->first();
        if($client !== null){
            CorporateStall::where('id', '=', $id)->delete();
        } else {
            $returned_data['results'] = false;
        }
        return ResponseWrapper::End($returned_data);
    }
}
