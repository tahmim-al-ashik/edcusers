<?php

namespace App\Http\Controllers\panel;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\AffiliateHistory;
use App\Models\AgentCommissionBreakdown;
use App\Models\Payment;
use App\Models\SalesAgent;
use App\Models\SalesPoint;
use App\Models\UserPermission;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PanelController extends Controller {

    public function checkPageAccess(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $user = Auth::user();
        if(!empty($user) && $user['panel_access'] === 1){

            if($user['id'] === 1 || $user['base_role'] === 'admin'){
                $returned_data['results'] = true;
                return ResponseWrapper::End($returned_data);
            }

            $permission_name = $request->get('permission');
            if($permission_name === 'panel_access'){
                $returned_data['results'] = true;
                return ResponseWrapper::End($returned_data);
            }

            $returned_data['results'] = UserPermission::where('name', '=', $permission_name)->where('uid', '=', $user['id'])->exists();
        }
        return ResponseWrapper::End($returned_data);
    }


    public function updateMissingCommissionBreakdowns(Request $request, $month_year, $agent_type, $agent_row_id, $commission_rate) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $filteredMonth = Carbon::createFromFormat('d-m-Y h:i:s', $month_year.' 00:00:01');
        $executionDate = Carbon::createFromFormat('d-m-Y h:i:s', '01-09-2023 00:00:00');

        if($filteredMonth > $executionDate){
            $agent_uid = '';
            if($agent_type === 'sales_agent'){
                $agent_uid = SalesAgent::where('id', '=', $agent_row_id)->value('uid');
            } else if($agent_type === 'sales_point'){
                $agent_uid = SalesPoint::where('id', '=', $agent_row_id)->value('uid');
            }

            $usersIds = AffiliateHistory::where('affiliator_uid', '=', $agent_uid)->where('product_type', '=', 'internet_package')->pluck('product_id')->toArray();
            $septPayments = Payment::whereIn('uid', $usersIds)->whereMonth('created_at', '=', $filteredMonth->month)->whereYear('created_at', '=', $filteredMonth->year)->get();

            $returned_data['results'] = [];
            foreach ($septPayments as $sPayment){
                $eachCommissionAmount = ($sPayment['amount'] * $commission_rate) / 100;
                if(!AgentCommissionBreakdown::where('agent_uid', '=', $agent_uid)->where('user_uid', '=', $sPayment['uid'])->where('trx_id', '=', $sPayment['trx_id'])->exists()){
                    $newBreakdown = new AgentCommissionBreakdown();
                    $newBreakdown->agent_uid = $agent_uid;
                    $newBreakdown->user_uid = $sPayment['uid'];
                    $newBreakdown->trx_id = $sPayment['trx_id'];
                    $newBreakdown->payment_amount = $sPayment['amount'];
                    $newBreakdown->commission_rate = $commission_rate;
                    $newBreakdown->commission_amount = $eachCommissionAmount;
                    $newBreakdown->created_at = Carbon::parse($sPayment['created_at'])->format('Y-m-d h:i:s');
                    $newBreakdown->updated_at = Carbon::parse($sPayment['created_at'])->format('Y-m-d h:i:s');
                    $newBreakdown->save();
                    if($newBreakdown->save()){
                        $returned_data['results'][] = $newBreakdown->id;
                    }
                }
            }
        } else {
            $returned_data['error_type'] = 'before_sept_2023';
        }

        return ResponseWrapper::End($returned_data);
    }

}
