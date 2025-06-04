<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\InternetPackage;
use App\Models\Transaction;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletPointPaymentsController extends Controller
{
    public function paymentWalletPointCreate(Request $request) : JsonResponse {

        $amount = $request->get("amount");
        $purpose = $request->get("purpose");
        $payByUid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('sender_auth_id'));
        $userWallet = UserProfile::where('uid', '=', $payByUid)->first();

        $extraData = $request->get('extra_data');
        if($extraData['service_type'] === 'internet_service'){
            $purpose = $extraData["purpose"];
        }

        if($userWallet['wallet_amount'] < $amount){
            $returned_data['error_type'] = "insufficient_wallet_points";
            return ResponseWrapper::End($returned_data);
        }

        $payfauid = (new \App\Classes\CustomHelpers)->getUidByAuthId($request->get('pay_for_auth_id'));

        $newTransaction = new Transaction();
        $newTransaction->sender_uid = $payByUid;
        $newTransaction->receiver_uid = $payfauid;
        $newTransaction->trx_id = time().'U'.$payByUid;
        $newTransaction->trx_type = 'wallet_point';
        $newTransaction->plus_minus = 'minus';
        $newTransaction->method = 'wallet_point';
        $newTransaction->amount = $amount;
        $newTransaction->purpose = $purpose;
        $newTransaction->save();


        if(!empty($newTransaction)){
            $userWallet['wallet_amount'] = ($userWallet['wallet_amount'] - $amount);
            $userWallet->save();
        }
        $returned_data['results'] = $newTransaction;

        return ResponseWrapper::End($returned_data);
    }
}
