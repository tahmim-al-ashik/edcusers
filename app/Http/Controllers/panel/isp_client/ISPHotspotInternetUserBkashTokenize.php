<?php

namespace App\Http\Controllers\panel\isp_client;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InternetUsers;
use App\Models\CorporateClient;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use App\Models\InternetPackageCorporate;
use App\Models\Payment;
use App\Models\PaymentToken;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ISPHotspotInternetUserBkashTokenize extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function bkashTokenizePaymentCreate(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $internet_user_id = User::where('auth_id', $request->get('mobile_number'))->value('id');
        $editor_id = InternetUsers::where('uid',$internet_user_id)->value('added_by') ?? 0000;

        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $package_estimated_price = $request->get('amount');
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = $request->get('invoice_number');
        $paymentID = $request->get('payment_id');
        $package_id = InternetPackageCorporate::where('price', $package_estimated_price)->value('id');
        $package = InternetPackageCorporate::where('price', $package_estimated_price)->value('package_name');

        $existToken = PaymentToken::where('invoice_number', $invoiceID, '=')->where('vendor_name', 'bkash')->first();
        if(empty($existToken)) {
            $paymentToken = new PaymentToken();
            $paymentToken->vendor_name = 'bkash';
            $paymentToken->invoice_number = $invoiceID;
        } else {
            $paymentToken = PaymentToken::find($existToken['id']);
        }
        $paymentToken->token = $request->get('token');
        $paymentToken->save();

        //Log::info($paymentToken);

        // ---------------------- db save data ----------------------------
        $payment = new Payment();
        $payment->uid = $internet_user_id;
        if($client){
            $payment->zone_id = $editor_id;
        }elseif($agent){
            $client_id = CorporateAgent::where('uid',$editor_id)->value('client_id');
            $payment->zone_id = $client_id;
        }elseif($sub_agent){
            $client_id = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
            $payment->zone_id = $client_id;
        }
        $payment->is_test_mode = 0;
        $payment->vendor_name = 'bkash';
        $payment->trx_id = $trxID;
        $payment->invoice_number = $invoiceID;
        $payment->amount = $package_estimated_price;
        $payment->payment_id = $paymentID;
        $payment->process_status = '1';
        $payment->purpose = $request->get('purpose') ?? 'hotspot_internet_bill_payment_client_bkash';
        $payment->package = $package;
        $payment->transaction_status = 'pending';
        $payment->save();

        // Log::info($payment);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
