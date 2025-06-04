<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Models\InternetUsers;
use App\Models\PaymentToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BkashPaymentController extends Controller
{

    protected $credentials = [
        'app_key'=>'71cq1rvcpai56i4f845t4vucfh',
        'app_secret'=>'1h2ncj4cc07vivetne5066n2gjkj59b2leb6jogttbvu45fmj21a',
        'username'=>'PLEXUSCLOUD',
        'password'=>'P7@3L0dDcUw',
        'token_url' => 'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/token/grant',
        'create_url'=>'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/payment/create',
        'execute_url'=>'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/payment/execute/'
    ];

    public function get_payment_token(){

        $tokenUrl = $this->credentials['token_url'];

        $url=curl_init($tokenUrl);
        $credentials=json_encode(array('app_key'=>$this->credentials['app_key'], 'app_secret'=>$this->credentials['app_secret']));
        $header=array('Content-Type:application/json', 'username:'.$this->credentials['username'], 'password:'.$this->credentials['password']);
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_POSTFIELDS, $credentials);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
        $resultData = curl_exec($url);
        curl_close($url);
        return json_decode($resultData, true);
    }

    public function paymentCreate(Request $request){

        $invoiceNumber = $request->get('ref_no');
        $tokenData = self::get_payment_token();

        if(empty($tokenData['id_token'])){
            return response()->json(['errors'=> 'token_error', 'status'=>'token_error']);
        } else {
            $existToken = PaymentToken::where('invoice_number', $invoiceNumber, '=')->where('vendor_name', 'bkash')->first();
            if(empty($existToken)){
                $query = new PaymentToken();
                $query->vendor_name = 'bkash';
                $query->invoice_number = $invoiceNumber;
            } else {
                $query = PaymentToken::find($existToken['id']);
            }
            $query->token = $tokenData["id_token"];
            $query->save();
        }


        $header=array(
            'Content-Type:application/json',
            'authorization:'.$tokenData["id_token"],
            'x-app-key:'.$this->credentials['app_key']
        );
        $paymentData = array(
            'amount'=>$request->get('amount'),
            'currency'=>$request->get('currency'),
            'merchantInvoiceNumber'=>$invoiceNumber,
            'intent'=>$request->get('intent')
        );
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

    public function paymentExecute(Request $request){

        $tokenData = PaymentToken::where('invoice_number', $request->get('order_no'), '=')->where('vendor_name', 'bkash')->value('token');

        $header=array(
            'Content-Type:application/json',
            'authorization:'.$tokenData,
            'x-app-key:'.$this->credentials['app_key']
        );

        $paymentId = $request->get('paymentID');
        $url = curl_init($this->credentials["execute_url"].$paymentId);
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);

        $resultData = curl_exec($url);
        curl_close($url);
        $results = json_decode($resultData, true);
        return $results;
    }


}
