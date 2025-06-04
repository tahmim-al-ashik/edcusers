<?php

namespace App\Http\Controllers\apps;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\NetworkSupportCenter;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CorporateAgent;
use Log;

class AppsSalesAgentController extends Controller
{
    public function registerSalesAgent(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('auth_id'));

        if(SalesPoint::where('uid', '=', $userId)->exists() || NetworkSupportCenter::where('uid', '=', $userId)->exists()){
            $returned_data['already_exist'] = "already_exist";
            return ResponseWrapper::End($returned_data);
        }

        if(!SalesAgent::where('uid', '=', $userId)->exists()){
            $salesAgent = new SalesAgent();
            $salesAgent->uid = $userId;
            $salesAgent->nid = $request->get('nid');
            $salesAgent->birth_date = $request->get('birth_date');
            $salesAgent->photo_source = $request->get('photo_source');
            $salesAgent->data_object = json_encode($request->get('data_object'));
            $salesAgent->save();

            if($salesAgent->id){
                $returned_data['results'] = $salesAgent;
            }
        } else {
            $returned_data['results'] = "already_exist";
        }
        return ResponseWrapper::End($returned_data);
    }


    public function salesAgentDashboard(Request $request, $auth_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($auth_id);
        $salesAgent = SalesAgent::where('uid', '=', $userId)->exists();
        $corporateAgent = CorporateAgent::where('uid', '=', $userId)->exists();
        // $returned_data['results'] = SalesAgent::where('uid', '=', $userId)->first(['id', 'status', 'created_at']);

        //return ResponseWrapper::End($returned_data);

        if(!empty($corporateAgent)){
            $data = CorporateAgent::where('uid', '=', $userId)->first(['id', 'status', 'created_at']);
            $id = $data['id'];
            $status = $data['status'];
            if($status = 0){
                $status = 'inactive';
            }else if ($status = 1){
                $status =  'active'; 
            }
            $actDate = $data['created_at'];
            //$agentInfo = ['id' => $id, 'status' => $status, 'created_at' => $actDate];
            //Log::info();
            $returned_data['results'] = ['id' => $id, 'status' => $status, 'created_at' => $actDate];;
            //Log::info($returned_data);
            return ResponseWrapper::End($returned_data);
        }else {
            $returned_data['results'] = SalesAgent::where('uid', '=', $userId)->first(['id', 'status', 'created_at']);
            return ResponseWrapper::End($returned_data);
        }


    }
}
