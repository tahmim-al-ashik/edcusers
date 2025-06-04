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
use App\Models\User;
use App\Models\Classes\GlobalClasses;
use App\Models\CorporateSubAgent;
use Illuminate\Support\Facades\DB;

class CorporateSubAgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $uid)
    {
         $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        $query = CorporateSubAgent::query();
        $query->leftJoin("user_profiles", 'user_profiles.uid', '=', 'corporate_sub_agents.uid');
        $query->leftJoin("users", 'users.id', '=', 'corporate_sub_agents.uid');
        $query->select([
            'corporate_sub_agents.id',
            'corporate_sub_agents.uid',
            'user_profiles.full_name',
            'user_profiles.mobile_number',
            'user_profiles.email',
            'users.text_password',
            'corporate_sub_agents.status',
            DB::raw("(SELECT full_name FROM user_profiles WHERE user_profiles.uid = corporate_sub_agents.client_id) as agent_name"),
            'corporate_sub_agents.activated_at'
        ]);
        $query->where('corporate_sub_agents.client_id',$uid);
        $returned_data['results'] = $query->get();

       // dd($returned_data['results']);
        return ResponseWrapper::End($returned_data);
    }

    public function getByAgentIndex(Request $request, $agentId)
    {
         $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }

        $query = CorporateSubAgent::where('agent_id', '=', $agentId);
        if(!empty($request->get('is_active'))){
            $query->where('is_active', '=', $request->get('is_active'));
        }
        $query->orderBy('name');
        $returned_data['results'] = $query->get();

       // dd($returned_data['results']);
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

//        if(empty($requestedData['email'])){
//            $returned_data['error_type'] = 'invalid_data';
//            return ResponseWrapper::End($returned_data);
//        }


        //dd($requestedData);
        unset($requestedData['access_token']);

        if(CorporateSubAgent::where('username', '=', $requestedData['username'])->exists()){
            $returned_data['results'] = CorporateSubAgent::where('username', '=', $request->get('username'))->update($requestedData);
        }else {
            $returned_data['results'] = CorporateSubAgent::create($requestedData);
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


     public function getSubAgentDetails(Request $request, $id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }


        $returned_data['results'] = CorporateSubAgent::where('id', '=', $id)->first();

        return ResponseWrapper::End($returned_data);
    }


    public function show(Request $request, $username)
    {
        $returned_data = ResponseWrapper::Start();

        if(!(new \App\Classes\CustomHelpers)->external_hash_verification($request->get('access_token'))){
            $returned_data['error_type'] = 'token_error';
            return ResponseWrapper::End($returned_data);
        }


        $returned_data['results'] = CorporateSubAgent::where('username', '=', $username)->first();

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

        $client = CorporateSubAgent::where('id', '=', $id)->first();
        if($client !== null){
            CorporateSubAgent::where('id', '=', $id)->delete();
        } else {
            $returned_data['results'] = false;
        }
        return ResponseWrapper::End($returned_data);
    //
    }
}
