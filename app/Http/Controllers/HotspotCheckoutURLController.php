<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Models\AffiliateHistory;
use App\Models\AgentCommissionBreakdown;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbUsers;
use App\Models\BroadbandDbZone;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\CorporateClientsSettings;
use App\Models\CorporateSubAgent;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\MessageAndNotification;
use App\Models\Payment;
use App\Models\PaymentToken;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WifiDbBkashInfoClient;
use App\Models\WifiDbPaymentClient;
use App\Models\WifiDbRadReplyClient;
use App\Models\WifiDbRadUserGroupClient;
use App\Models\BroadbandDbPaymentToken;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;
use URL;
use Illuminate\Support\Str;
use Sabberworm\CSS\Value\URL as ValueURL;

class HotspotCheckoutURLController extends Controller
{
    private $base_url;
    public function __construct()
    {
        $this->base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function authHeaders($settingsInfo){
        $bkash_app_key = $settingsInfo->bkash_app_key;
        $status = 200;
        return array(
            'Content-Type:application/json',
            'Authorization:' .$this->grant($settingsInfo, $status),
            'X-APP-Key:'.$bkash_app_key,
        );
    }
    
    public function curlWithBody($url,$header,$method,$body_data_json){
        $curl = curl_init($this->base_url.$url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $body_data_json);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [
            'body' => $response,
            'status' => $httpStatus,
        ];
    }

    public function grant($settingsInfo, $status)
    {
        $bkash_username = $settingsInfo->bkash_username;
        $bkash_password = $settingsInfo->bkash_password;
        $bkash_app_key = $settingsInfo->bkash_app_key;
        $bkash_app_secret_key = $settingsInfo->bkash_app_secret_key;

        if($status === 200){
            // Check if a valid token exists
            $existingToken = BroadbandDbPaymentToken::where('mr_username', $bkash_username)->value('access_token');
            if ($existingToken) {
                return $existingToken;
            }
        }

        $header = [
            'Content-Type:application/json',
            'username:' . $bkash_username,
            'password:' . $bkash_password,
        ];

        $body_data = [
            'app_key' => $bkash_app_key,
            'app_secret' => $bkash_app_secret_key,
        ];
        $body_data_json = json_encode($body_data);

        $response = $this->curlWithBody('/tokenized/checkout/token/grant', $header, 'POST', $body_data_json);
        $response_data = json_decode($response['body'], true);

        if (!isset($response_data['id_token'])) {
            throw new \Exception('Token fetch failed: ' . $response);
        }

        $new_token = $response_data['id_token'];

        // Save or update in DB
        BroadbandDbPaymentToken::updateOrCreate(
            ['mr_username' => $bkash_username],
            [
                'access_token' => $new_token,
                'token_type' => 'bearer'
            ]
        );

        return $new_token;
    }
    
    public function create(Request $request)
    {
        $editor_id = $request->get('zone_id');
        $internet_user_id = $request->get('uid');

        // Checking User
        $client = CorporateClient::where('uid', $editor_id)->exists();
        $agent = CorporateAgent::where('uid', $editor_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();
        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        if($client){
            $settingsInfo = CorporateClientsSettings::where('client_uid', '=', $editor_id)->first(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        }elseif($agent){
            $editor_id_from_agent = CorporateAgent::where('uid',$editor_id)->value('client_id');
            $settingsInfo = CorporateClientsSettings::where('client_uid', '=', $editor_id_from_agent)->first(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        }elseif($sub_agent){
            $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
            $settingsInfo = CorporateClientsSettings::where('client_uid', '=', $editor_id_from_sub_agent)->first(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        }
        $header = $this->authHeaders($settingsInfo);
        $website_url = 'https://backend.shadhinwifi.com';

        $internet_user = User::where('id', $internet_user_id)->exists();
        if (!$internet_user) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        $paymentExists = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->exists();
        if ($paymentExists) {
            // Get the latest payment date
            $latestPaymentDate = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('created_at');
            $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
            $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
            if($expiration < 1440){
                $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
                $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
            }else{
                $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
            }
            // Check if today is before the expiry date
            if (Carbon::now()->lessThanOrEqualTo($expiryDate)) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "This user's package is not expired!";
                return ResponseWrapper::End($returned_data);
            }
        }

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');

        // Extract the Authorization key from the header array
        $authorizationHeader = array_values(array_filter($header, function ($headerLine) {
            return strpos($headerLine, 'Authorization:') === 0;
        }))[0];

        // Get the token value from the Authorization header
        $authorizationToken = explode('Authorization:', $authorizationHeader)[1];

        $body_data = array(
            'mode' => '0011',
            'payerReference' => ' ',
            'callbackURL' => $website_url.'/api/v2/hotspot/payment/bkash/callback?source='.$request->get('source'),
            'amount' => $request->get('payable_package_price'),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceID // you can pass here OrderID
        );
        $body_data_json=json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/create',$header,'POST',$body_data_json);
        $res_array = json_decode($response['body'],true);

        if (empty($res_array['paymentID']) && $response['status'] !== 200) {
            // Token might be expired, force refresh
            $this->grant($settingsInfo, $response['status']);

            // Retry with refreshed token
            $header = $this->authHeaders($settingsInfo);
            $response = $this->curlWithBody('/tokenized/checkout/create', $header, 'POST', $body_data_json);
            $res_array = json_decode($response['body'], true);
        }

        if(empty($authorizationToken)){
            return response()->json(['errors'=> 'token_error', 'status'=>'token_error']);
        } else {
            $existToken = PaymentToken::where('invoice_number', $invoiceID, '=')->where('vendor_name', 'bkash')->first();
            if(empty($existToken)){
                $paymentToken = new PaymentToken();
                $paymentToken->vendor_name = 'bkash';
                $paymentToken->invoice_number = $invoiceID;
            } else {
                $paymentToken = PaymentToken::find($existToken['id']);
            }
            $paymentToken->token = $authorizationToken;
            $paymentToken->save();

            // ---------------------- db save data ----------------------------
            $payment = new Payment();
            $payment->uid = $request->get('uid');
            if($client){
                $payment->zone_id = $editor_id;
            }elseif($agent){
                $client_id = CorporateAgent::where('uid',$editor_id)->value('client_id');
                $payment->zone_id = $client_id;
            }elseif($sub_agent){
                $client_id = CorporateSubAgent::where('uid',$editor_id)->value('client_id');
                $payment->zone_id = $client_id;
            }
            $payment->vendor_name = 'bkash';
            $payment->trx_id = $trxID;
            $payment->invoice_number = $invoiceID;
            $payment->amount = $request->get('payable_package_price');
            $payment->payment_id = $res_array['paymentID'];
            $payment->process_status = '1';
            $payment->purpose = $request->get('purpose');
            $payment->package = $request->get('package');
            $payment->transaction_status = 'pending';
            $payment->save();
            if (!$payment->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Payment Storing!';
                return ResponseWrapper::End($returned_data);
            }

            // Save Transaction
            $transactionForBackend = new Transaction();
            $transactionForBackend->trx_id = $trxID;
            $transactionForBackend->trx_type = 'payment';
            $transactionForBackend->plus_minus = 'minus';
            $transactionForBackend->sender_uid = $editor_id;
            $transactionForBackend->receiver_uid = $internet_user_id;
            $transactionForBackend->method = 'bkash';
            $transactionForBackend->amount = $request->get('payable_package_price');
            $transactionForBackend->purpose = 'hotspot_internet_bill_payment';
            if (!$transactionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
                return ResponseWrapper::End($returned_data);
            }
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['result'] = $res_array;
        return ResponseWrapper::End($returned_data);
    }

    public function execute($paymentID)
    {
        $zone_id = Payment::where('payment_id',$paymentID)->value('zone_id');
        $bkash_app_key = CorporateClientsSettings::where('client_uid', $zone_id)->value('bkash_app_key');
        $invoice_number = Payment::where('payment_id',$paymentID)->value('invoice_number');
        $token = PaymentToken::where('invoice_number', $invoice_number)->where('vendor_name', 'bkash')->value('token');
        $header = array(
            'Content-Type:application/json',
            'Authorization:' .$token,
            'X-APP-Key:' .$bkash_app_key,
        );
        $body_data = array('paymentID' => $paymentID);
        $body_data_json=json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/execute',$header,'POST',$body_data_json);
        return $response;
    }

    public function query($paymentID)
    {
        $zone_id = Payment::where('payment_id',$paymentID)->value('zone_id');
        $settingsInfo = CorporateClientsSettings::where('client_uid', '=', $zone_id)->first(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        $header = $this->authHeaders($settingsInfo);
        $body_data = array(
            'paymentID' => $paymentID,
        );
        $body_data_json=json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/payment/status',$header,'POST',$body_data_json);
        return $response;
    }

    public function callback(Request $request)
    {
        $allRequest = $request->all();
        if(isset($allRequest['status']) && $allRequest['status'] == 'success'){
            $response = $this->execute($allRequest['paymentID']);
            $arr = json_decode($response['body'],true);
            if((!isset($arr['trxID']) || empty($arr['trxID'])) && array_key_exists("message",$arr)){
                $response = $this->query($allRequest['paymentID']);
                $arr = json_decode($response['body'],true);
            }

            if((!isset($arr['trxID']) || empty($arr['trxID'])) && array_key_exists("statusCode",$arr) && $arr['statusCode'] != '0000'){
                $frontendUrl = $_GET['source'];
                if ($frontendUrl === 'openwifi') {
                    $redirectUrl = 'https://erp.myopenwifi.com/wifi/user-list/user/error?data='.$arr['statusMessage'];
                    // $redirectUrl = 'http://10.142.1.190:8000/wifi/user-list/user/error?data='.$arr['statusMessage'];
                } else {
                    $redirectUrl = 'https://erp.shadhinwifi.com/wifi/user-list/user/error?data='.$arr['statusMessage'];
                    // $redirectUrl = 'http://10.142.1.190:8000/wifi/user-list/user/error?data='.$arr['statusMessage'];
                }
                return redirect($redirectUrl);
            }else{
                // response save to your db -----------------------
                $paymentTable = Payment::where('payment_id',$allRequest['paymentID'])->first();
                $userTable = User::where('id',$paymentTable->uid)->first();
                $transactionTable = Transaction::where('trx_id',$paymentTable->trx_id)->first();
                $full_name = UserProfile::where('uid',$paymentTable->uid)->value('full_name');
                $invoice_number = $paymentTable->invoice_number;
                $internet_user_id = $paymentTable->uid;
                $mobile_number = $userTable->auth_id;
                $password = $userTable->text_password;
                $zone_id = $paymentTable->zone_id;
                $package = $paymentTable->package;
                $package_id = InternetPackageCorporate::where('package_name',$package)->value('id');
                $payable_package_price = $paymentTable->amount;
                $trx_id = $paymentTable->trx_id;
                $editor_id = $transactionTable->sender_uid;

                $client = CorporateClient::where('uid', $editor_id)->exists();
                $agent = CorporateAgent::where('uid', $editor_id)->exists();
                $sub_agent = CorporateSubAgent::where('uid', $editor_id)->exists();

                $internet_user = InternetUsers::where('uid',$internet_user_id)->first();
                if($internet_user){
                    $internet_user->update([
                        'package_id' => $package_id
                    ]);
                }

                Payment::where('payment_id', $allRequest['paymentID'])->update([
                    'trx_id' => $arr['trxID'],
                    'transaction_status' => 'Completed'
                ]);

                // Delete if any pending found ------
                Payment::where('uid', $internet_user_id)->where('transaction_status', 'pending')->where('payment_id', '!=', $allRequest['paymentID'])->delete();

                // Save Affiliate History
                $affiliate_history = new AffiliateHistory();
                $affiliate_history->affiliator_uid = $editor_id;
                $affiliate_history->product_type = 'internet_package';
                $affiliate_history->product_id = User::where('auth_id', $mobile_number)->value('id');
                $affiliate_history->status = 'pending';

                if ($client) {
                    $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
                    $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();

                    if ($checkIfSubAgent) {
                        $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                        $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                        $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                        // agent id
                        $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                        $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                        $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                        // commission & commission amount for agent
                        $agent_commission = $agent_main_commission - $sub_agent_commission;
                        $agent_commission_amount = ($agent_commission / 100) * $payable_package_price;

                        // commission & commission amount for sub-agent
                        $sub_agent_commission_amount = ($sub_agent_commission / 100) * $payable_package_price;
                        $affiliate_history->commission_amount = $sub_agent_commission_amount;

                        UserProfile::where('uid', $sub_agent_id)->update([
                            'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                        ]);

                        UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                            'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                        ]);
                    } elseif ($checkIfAgent) {
                        $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                        $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
                        $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                        $new_commission_amount = ($commission / 100) * $payable_package_price;
                        $affiliate_history->commission_amount = $new_commission_amount;

                        UserProfile::where('uid', $agent_id)->update([
                            'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                        ]);
                    }
                } elseif ($agent) {
                    $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();

                    if ($checkIfSubAgent) {
                        $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                        $sub_agent_commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                        $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');

                        // agent id
                        $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $sub_agent_id)->value('agent_id');
                        $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                        $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                        // commission & commission amount for agent
                        $agent_commission = $agent_main_commission - $sub_agent_commission;
                        $agent_commission_amount = ($agent_commission / 100) * $payable_package_price;

                        // commission & commission amount for sub-agent
                        $sub_agent_commission_amount = ($sub_agent_commission / 100) * $payable_package_price;
                        $affiliate_history->commission_amount = $sub_agent_commission_amount;

                        UserProfile::where('uid', $sub_agent_id)->update([
                            'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                        ]);

                        UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                            'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                        ]);
                    } else {
                        $commission = CorporateAgent::where('uid', $editor_id)->value('commission');
                        $previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');
                        $new_commission_amount = ($commission / 100) * $payable_package_price;
                        $affiliate_history->commission_amount = $new_commission_amount;

                        UserProfile::where('uid', $editor_id)->update([
                            'wallet_amount' => $previous_wallet_amount + $new_commission_amount
                        ]);
                    }
                } elseif ($sub_agent) {
                    $sub_agent_commission = CorporateSubAgent::where('uid', $editor_id)->value('commission');
                    $sub_agent_previous_wallet_amount = UserProfile::where('uid', $editor_id)->value('wallet_amount');

                    // agent id
                    $agent_id_for_this_sub_agent = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
                    $agent_main_commission = CorporateAgent::where('uid', $agent_id_for_this_sub_agent)->value('commission');
                    $agent_previous_wallet_amount = UserProfile::where('uid', $agent_id_for_this_sub_agent)->value('wallet_amount');

                    // commission & commission amount for agent
                    $agent_commission = $agent_main_commission - $sub_agent_commission;
                    $agent_commission_amount = ($agent_commission / 100) * $payable_package_price;

                    // commission & commission amount for sub-agent
                    $sub_agent_commission_amount = ($sub_agent_commission / 100) * $payable_package_price;
                    $affiliate_history->commission_amount = $sub_agent_commission_amount;

                    UserProfile::where('uid', $editor_id)->update([
                        'wallet_amount' => $sub_agent_previous_wallet_amount + $sub_agent_commission_amount
                    ]);

                    UserProfile::where('uid', $agent_id_for_this_sub_agent)->update([
                        'wallet_amount' => $agent_previous_wallet_amount + $agent_commission_amount
                    ]);
                }

                if (!$affiliate_history->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
                    return ResponseWrapper::End($returned_data);
                }

                // Save Agent Commission Breakdown
                if ($client) {
                    $checkIfAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('agent_id')->exists();
                    $checkIfSubAgent = InternetUsers::where('uid', $internet_user_id)->whereNotNull('sub_agent_id')->exists();
                    if ($checkIfSubAgent) {
                        $sub_agent_id = InternetUsers::where('uid', $internet_user_id)->value('sub_agent_id');
                        $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
                        $agentCommissionForBackend = new AgentCommissionBreakdown();
                        $agentCommissionForBackend->agent_uid = $sub_agent_id;
                        $agentCommissionForBackend->user_uid = User::where('auth_id', $mobile_number)->value('id');
                        $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
                        $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

                        if (!$agentCommissionForBackend->save()) {
                            $returned_data['status'] = 'error';
                            $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                            return ResponseWrapper::End($returned_data);
                        }
                    } elseif ($checkIfAgent) {
                        $agent_id = InternetUsers::where('uid', $internet_user_id)->value('agent_id');
                        $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                        $agentCommissionForBackend = new AgentCommissionBreakdown();
                        $agentCommissionForBackend->agent_uid = $agent_id;
                        $agentCommissionForBackend->user_uid = User::where('auth_id', $mobile_number)->value('id');
                        $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
                        $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

                        if (!$agentCommissionForBackend->save()) {
                            $returned_data['status'] = 'error';
                            $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                            return ResponseWrapper::End($returned_data);
                        }
                    }
                } elseif ($agent) {
                    $agent_id = InternetUsers::where('uid',$internet_user_id)->value('agent_id');
                    $previous_wallet_amount = UserProfile::where('uid', $agent_id)->value('wallet_amount');
                    $commission = CorporateAgent::where('uid', $agent_id)->value('commission');
                    $new_commission_amount = ($commission / 100) * $payable_package_price;
                    $agentCommissionForBackend = new AgentCommissionBreakdown();
                    $agentCommissionForBackend->agent_uid = $editor_id;
                    $agentCommissionForBackend->user_uid = User::where('auth_id', $mobile_number)->value('id');
                    $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
                    $agentCommissionForBackend->new_commission_amount = $new_commission_amount;

                    if (!$agentCommissionForBackend->save()) {
                        $returned_data['status'] = 'error';
                        $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                        return ResponseWrapper::End($returned_data);
                    }
                } elseif ($sub_agent) {
                    $sub_agent_id = InternetUsers::where('uid',$internet_user_id)->value('sub_agent_id');
                    $sub_agent_previous_wallet_amount = UserProfile::where('uid', $sub_agent_id)->value('wallet_amount');
                    $commission = CorporateSubAgent::where('uid', $sub_agent_id)->value('commission');
                    $sub_agent_commission_amount = ($commission / 100) * $payable_package_price;
                    $agentCommissionForBackend = new AgentCommissionBreakdown();
                    $agentCommissionForBackend->agent_uid = CorporateSubAgent::where('uid', $editor_id)->value('agent_id');
                    $agentCommissionForBackend->user_uid = User::where('auth_id', $mobile_number)->value('id');
                    $agentCommissionForBackend->previous_wallet_amount = $sub_agent_previous_wallet_amount;
                    $agentCommissionForBackend->new_commission_amount = $sub_agent_commission_amount;

                    if (!$agentCommissionForBackend->save()) {
                        $returned_data['status'] = 'error';
                        $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                        return ResponseWrapper::End($returned_data);
                    }
                }

                // Save Radius Payment
                $paymentForRadius = new WifiDbPaymentClient();
                $paymentForRadius->username = $mobile_number;
                $paymentForRadius->amount = $payable_package_price;
                $paymentForRadius->created_at = Carbon::now();

                if (!$paymentForRadius->save()){
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
                    return ResponseWrapper::End($returned_data);
                }

                // bkash info -----
                $paymentForRadius = new WifiDbBkashInfoClient();
                $paymentForRadius->username = $mobile_number;
                $paymentForRadius->amount = $payable_package_price;
                $paymentForRadius->payment_id = $allRequest['paymentID'];
                $paymentForRadius->transaction_id = $arr['trxID'];
                $paymentForRadius->status = 'Success';
                $paymentForRadius->created_at = Carbon::now();

                if (!$paymentForRadius->save()){
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
                    return ResponseWrapper::End($returned_data);
                }

                $is_date_expire = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->value('created_at');
                $package = Payment::where('uid', $internet_user_id)->where('transaction_status','Completed')->latest()->value('package');
                $expiration = InternetPackageCorporate::where('package_name',$package)->value('expiration');
                if($expiration < 1440){
                    $expirationMinutes = InternetPackageCorporate::where('package_name', $package)->value('expiration');
                    $expiryDate = Carbon::parse($latestPaymentDate)->addMinutes($expirationMinutes)->format('Y-m-d\TH:i:s');
                }else{
                    $expiryDate = Carbon::parse($latestPaymentDate)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
                }

                $expDateExist = WifiDbRadReplyClient::where('username',$mobile_number)->exists();
                if($expDateExist){
                    WifiDbRadReplyClient::where('username',$mobile_number)->update(['value' => $expiryDate]);
                }else{
                    $radUserGroup = new WifiDbRadReplyClient();
                    $radUserGroup->username = $mobile_number;
                    $radUserGroup->attribute = "WISPr-Session-Terminate-Time";
                    $radUserGroup->op = ":=";
                    $radUserGroup->value = $expiryDate;
                    $radUserGroup->save();
                }
                WifiDbRadUserGroupClient::where('username',$request->get('mobile_number'))->update(['groupname' => $package]);
                $response_data = [
                    'status' => 'success',
                    'message' => $arr['statusMessage'],
                    'transaction_id' => $trx_id,
                    'invoice_id' => $invoice_number,
                    'payment_id' => $allRequest['paymentID'],
                    'full_name' => UserProfile::where('uid',$internet_user_id)->value('full_name'),
                    'mobile_number' => $mobile_number,
                    'package' => $package,
                    'final_price' => $payable_package_price,
                    'payment_method' => 'Bkash',
                    'payment_date' => Carbon::now()
                ];

                // Encode the response data as a query string
                $query_string = http_build_query($response_data);

                // Append the query string to the redirect URL
                $frontendUrl = $_GET['source'];
                $redirect_url = '';
                if ($frontendUrl === 'openwifi') {
                    $redirectUrl = 'https://erp.myopenwifi.com/wifi/user-list/user/success?' . $query_string;
                    // $redirectUrl = 'http://10.142.1.190:8000/wifi/user-list/user/success?' . $query_string;
                } else {
                    $redirectUrl = 'https://erp.shadhinwifi.com/wifi/user-list/user/success?' . $query_string;
                    // $redirectUrl = 'http://10.142.1.190:8000/wifi/user-list/user/success?' . $query_string;
                }
                return redirect($redirectUrl);
            }
        }else{
            $paymentTable = Payment::where('payment_id', $allRequest['paymentID'])->first();
            $transactionTable = Transaction::where('trx_id', $paymentTable->trx_id)->delete();
            $transactionTable = PaymentToken::where('invoice_number', $paymentTable->invoice_number)->delete();
            $paymentTableDelete = Payment::where('payment_id', $allRequest['paymentID'])->delete();

            // Construct the error redirect URL with data
            $error_data = [
                'status' => 'error',
                'message' => 'Failed to Pay.'
            ];

            $response = $this->execute($allRequest['paymentID']);
            $arr = json_decode($response['body'],true);
            $query_string = http_build_query($arr);

            $frontendUrl = $_GET['source'];
            $redirect_url = '';
            if ($frontendUrl === 'openwifi') {
                $redirectUrl = 'https://erp.myopenwifi.com/wifi/user-list/user/error?data='.$query_string;
                // $redirectUrl = 'http://10.142.1.190:8000/wifi/user-list/user/error?data='.$query_string;
            } else {
                $redirectUrl = 'https://erp.shadhinwifi.com/wifi/user-list/user/error?data='.$query_string;
                // $redirectUrl = 'http://10.142.1.190:8000/wifi/user-list/user/error?data='.$query_string;
            }
            return redirect($redirectUrl);
        }
    }

    public function refund(Request $request)
    {
        $settingsInfo = CorporateClientsSettings::where('payment_id', '=', $request->paymentID)->first(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        $header = $this->authHeaders($settingsInfo);
        $body_data = array(
            'paymentID' => $request->paymentID,
            'amount' => $request->amount,
            'trxID' => $request->trxID,
            'sku' => 'sku',
            'reason' => 'Quality issue'
        );

        $body_data_json=json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/payment/refund',$header,'POST',$body_data_json);

        // your database operation
        // save $response

        return $response;
    }
}
