<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Models\InternetUsers;
use App\Models\PaymentToken;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpayPaymentController extends Controller
{

    protected $credentials = [
        'merchant_id'=>'1140101050009124',
        'merchant_key'=>'0qTcSv7oD31lQNaZeJG48ZSD6gRdXb4e',
        'merchant_code'=>'4816',
        'merchant_name'=>'Plexus Cloud',
        'token_url' => 'https://pg.upaysystem.com/payment/merchant-auth/',
        'create_url'=>'https://pg.upaysystem.com/payment/merchant-payment-init/',
    ];


    public function get_payment_token(){

        $tokenUrl = $this->credentials['token_url'];

        $url=curl_init($tokenUrl);
        $credentials=json_encode(array('merchant_id'=>$this->credentials['merchant_id'], 'merchant_key'=>$this->credentials['merchant_key']));
        $header=array('Content-Type:application/json');
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_POSTFIELDS, $credentials);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
        $resultData = curl_exec($url);
        curl_close($url);

        return json_decode($resultData, true);
    }

//    public function paymentGetToken(Request $request) : JsonResponse {
//
//        $returned_data = ResponseWrapper::Start();
//        $tokenData = self::get_payment_token();
//
//        if($tokenData['code'] === 'MAS2001' && $tokenData['data'] !== null){
//            $returned_data['results'] = $tokenData['data']['token'];
//        } else {
//            return response()->json(['errors'=> 'token_error', 'status'=>'token_error']);
//        }
//
//        return ResponseWrapper::End($returned_data);
//    }

    public function paymentCreate(Request $request){

        $tokenData = self::get_payment_token();
        if($tokenData['code'] !== 'MAS2001'){
            return response()->json(['errors'=> 'token_error', 'status'=>'token_error']);
        }
        $tokenString = $tokenData['data']['token'];

        $paymentData = [
            'date'=> Carbon::now()->format('Y-m-d'),
            'txn_id'=> $request->get('txn_id'),
            'invoice_id'=> $request->get('invoice_id'),
            'amount'=> $request->get('amount'),
            'merchant_id'=> $this->credentials['merchant_id'],
            'merchant_name'=> $this->credentials['merchant_name'],
            'merchant_code'=> $this->credentials['merchant_code'],
            'merchant_country_code'=> 'BD',
            'merchant_city'=> 'Dhaka',
            'merchant_category_code'=> 'Merchant',
            'merchant_mobile'=> '01756348921',
            'transaction_currency_code'=> 'BDT',
            'redirect_url'=> 'https://'.$_SERVER['HTTP_HOST'].'/api/v2/payments/upay/redirect/internet_bill_pay',
        ];

//        $query = new PaymentToken();
//        $query->vendor_name = 'upay';
//        $query->invoice_number = $paymentData['invoice_id'];
//        $query->token = $request->get('user_auth_id');
//        $query->save();


        $header = array('Content-Type:application/json', 'authorization: UPAY '. $tokenString);
        $paymentDataEncoded = json_encode($paymentData);
        $url = curl_init($this->credentials["create_url"]);
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_POSTFIELDS, $paymentDataEncoded);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);

        $resultData = curl_exec($url);
        curl_close($url);
        return json_decode($resultData, true);

    }

    public function paymentRedirect(Request $request) {

        $redirStatus = $request->get('status');
        $redirInvoiceId = $request->get('invoice_id');
        //dd($redirStatus);
        $data = [];
        if($redirStatus === 'cancel'){
            $data['status'] = 'cancel';
        } else if($redirStatus !== 'success'){
            $data['status'] = 'failed';
        } else {
            $data['status'] = 'success';
            $data['invoice_id'] = $redirInvoiceId;
        }
        //dd($data);
        return view('upay_payment_redirect', ['data'=> $data]);
    }

}
