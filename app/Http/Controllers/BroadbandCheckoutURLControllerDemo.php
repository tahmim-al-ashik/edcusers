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
use App\Models\BroadbandDbPaymentToken;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BroadbandCheckoutURLController extends Controller
{
    private $base_url;

    public function __construct()
    {
        // $this->base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
        $this->base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function authHeaders($settingsInfo){
        $bkash_app_key = $settingsInfo[0]['bkash_app_key'];
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
        $bkash_username = $settingsInfo[0]['bkash_username'];
        $bkash_password = $settingsInfo[0]['bkash_password'];
        $bkash_app_key = $settingsInfo[0]['bkash_app_key'];
        $bkash_app_secret_key = $settingsInfo[0]['bkash_app_secret_key'];

        if($status === 200){
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
        $user_id = $request->get('zone_id');
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        if($client){
            $settingsInfo = CorporateClientsSettings::where('client_uid', '=', $user_id)->get(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        }elseif($agent){
            $editor_id_from_agent = CorporateAgent::where('uid',$user_id)->value('client_id');
            $settingsInfo = CorporateClientsSettings::where('client_uid', '=', $editor_id_from_agent)->get(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        }elseif($sub_agent){
            $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$user_id)->value('client_id');
            $settingsInfo = CorporateClientsSettings::where('client_uid', '=', $editor_id_from_sub_agent)->get(['bkash_username','bkash_password','bkash_app_key','bkash_app_secret_key']);
        }

        $header = $this->authHeaders($settingsInfo);

        $website_url = 'https://backend.shadhinwifi.com';

        $currentYear = Carbon::now()->format('Y');
        $currentMonth = Carbon::now()->format('m');
        $checkPayment = Payment::where('uid', $request->get('uid'))->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->where('transaction_status','Completed')->exists();
        if ($checkPayment) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Paid for this month!';
            return ResponseWrapper::End($returned_data);
        }

        // Fetch branch information
        if($sub_agent){
            $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$user_id)->value('client_id');
            $branchInfo = CorporateClient::where('uid', '=', $editor_id_from_sub_agent)->get();
        }elseif($agent){
            $editor_id_from_agent = CorporateAgent::where('uid',$user_id)->value('client_id');
            $branchInfo = CorporateClient::where('uid', '=', $editor_id_from_agent)->get();
        }elseif($client){
            $branchInfo = CorporateClient::where('uid', '=', $user_id)->get();
        }

        // Check if branch information exists
        if (!$branchInfo) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Zone information not found!';
            return ResponseWrapper::End($returned_data);
        }

        // Api Variables
        $ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
        $mkUser = $branchInfo->implode('mikrotik_username', ', ');
        $mkPass = $branchInfo->implode('mikrotik_password', ', ');
        $API = new RouterOsApi();

        // Connect to MikroTik Router
        if (!$API->connect($ipAddr, $mkUser, $mkPass)) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Failed to connect to MikroTik Router!';
            return ResponseWrapper::End($returned_data);
        }

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');
        // $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

        // Extract the Authorization key from the header array
        $authorizationHeader = array_values(array_filter($header, function ($headerLine) {
            return strpos($headerLine, 'Authorization:') === 0;
        }))[0];

        // Get the token value from the Authorization header
        $authorizationToken = explode('Authorization:', $authorizationHeader)[1];

        $body_data = array(
            'mode' => '0011',
            'payerReference' => ' ',
            'callbackURL' => $website_url.'/api/v2/broadband/payment/bkash/callback?source='.$request->get('source'),
            'amount' => $request->get('payable_package_price'),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceID // you can pass here OrderID
        );
        $body_data_json=json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/create',$header,'POST',$body_data_json);
        $res_array = json_decode($response['body'],true);

        if ($response['status'] !== 200) {
            // Token might be expired, force refresh
            $this->grant($settingsInfo, $response['status']);

            // Retry with refreshed token
            $header = $this->authHeaders($settingsInfo);
            $response = $this->curlWithBody('/tokenized/checkout/create', $header, 'POST', $body_data_json);
            $res_array = json_decode($response['body'], true);
        }


        if(empty($authorizationToken)){
            return response()->json(['errors'=> 'token_error', 'status'=>'token_error']);
        } else if(empty($res_array)){
            return response()->json(['errors'=> 'response_error', 'status'=>'response_not_found']);
        }else {
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
            if($sub_agent){
                $client_id = CorporateSubAgent::where('uid',$user_id)->value('client_id');
                $payment->zone_id = $client_id;
            }elseif($agent){
                $client_id = CorporateAgent::where('uid',$user_id)->value('client_id');
                $payment->zone_id = $client_id;
            }elseif($client){
                $payment->zone_id = $user_id;
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
            $transactionForBackend->sender_uid = $user_id;
            $transactionForBackend->receiver_uid = $request->get('uid');
            $transactionForBackend->method = 'bkash';
            $transactionForBackend->amount = $request->get('payable_package_price');
            $transactionForBackend->purpose = 'new_user_internet_bill_payment';

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
	    $bkash_username = CorporateClientsSettings::where('client_uid', $zone_id)->value('bkash_username');
        $bkash_app_key = CorporateClientsSettings::where('client_uid', $zone_id)->value('bkash_app_key');
        $invoice_number = Payment::where('payment_id',$paymentID)->value('invoice_number');
        //$token = PaymentToken::where('invoice_number', $invoice_number)->where('vendor_name', 'bkash')->value('token');
	    $token = BroadbandDbPaymentToken::where('mr_username', $bkash_username)->value('access_token');
        $header = array(
            'Content-Type:application/json',
            'Authorization:' .$token,
            'X-APP-Key:' .$bkash_app_key,
        );
        $body_data = array('paymentID' => $paymentID);
        $body_data_json=json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/execute',$header,'POST',$body_data_json);
        $res_array = json_decode($response['body'],true);
        return $response;
    }

    public function query($paymentID)
    {
        $zone_id = Payment::where('payment_id', $paymentID)->value('zone_id');
        $settingsInfo = CorporateClientsSettings::where('client_uid', $zone_id)->get(['bkash_username', 'bkash_password', 'bkash_app_key', 'bkash_app_secret_key']);
        $header = $this->authHeaders($settingsInfo);
        $body_data = array(
            'paymentID' => $paymentID,
        );
        $body_data_json=json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/payment/status',$header,'POST',$body_data_json);
        $res_array = json_decode($response['body'],true);
        return $response;
    }

    public function callback(Request $request)
    {
        $allRequest = $request->all();
        if(isset($allRequest['status']) && $allRequest['status'] == 'success'){

            $response = $this->execute($allRequest['paymentID']);
            $arr = json_decode($response['body'],true);
            if(array_key_exists("message",$arr)){
                // if execute api failed to response
                sleep(1);
                $response = $this->query($allRequest['paymentID']);
                $arr = json_decode($response['body'],true);
            }

            if(array_key_exists("statusCode",$arr) && $arr['statusCode'] != '0000'){
                // return redirect('http://10.142.1.10:8002/broadband/user-list/user/error?data='.$arr['statusMessage']);
                return redirect('https://erp.myopenwifi.com/broadband/user-list/user/error?data='.$arr['statusMessage']);
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
                $payable_package_price = $paymentTable->amount;
                $trx_id = $paymentTable->trx_id;
                $user_id = $transactionTable->sender_uid;

                $client = CorporateClient::where('uid', $user_id)->exists();
                $agent = CorporateAgent::where('uid', $user_id)->exists();
                $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

                // Fetch branch information
                $branchInfo = CorporateClient::where('uid', '=', $zone_id)->get();

                // Check if branch information exists
                if (!$branchInfo) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Zone information not found!';
                    return ResponseWrapper::End($returned_data);
                }

                // Api Variables
                $ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
                $mkUser = $branchInfo->implode('mikrotik_username', ', ');
                $mkPass = $branchInfo->implode('mikrotik_password', ', ');
                $oldUserCheck = BroadbandDbSecret::where('username', $mobile_number)->first();
                $API = new RouterOsApi();
                // Connect to MikroTik Router
                if ($API->connect($ipAddr, $mkUser, $mkPass)) {
                    if(!$oldUserCheck){
                        $API->comm('/ppp/secret/add', array('name' => $mobile_number, 'password' => $password, 'service' => 'pppoe', 'profile' => $package));
                        $API->disconnect();
                    } else{
                        if($oldUserCheck->profile !== $package){
                            $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id","?name" => $mobile_number));
                            $API->comm("/ppp/secret/set", array(".id" => $arrID[0][".id"], "name"=> $mobile_number, "password" => $password, "service" => 'pppoe' , "profile" => $package));
                            $API->disconnect();
                        }else{
                            $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $mobile_number));
                            $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                            $API->disconnect();
                        }
                    }
                } else {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Failed to connect to MikroTik Router!';
                    return ResponseWrapper::End($returned_data);
                }

                // Save Affiliate History
                $added_by = InternetUsers::where('uid', $internet_user_id)->value('added_by');
                $added_by_user_table = User::where('id', $added_by)->first();
                if ($added_by_user_table->base_role === 'agent' || $added_by_user_table->base_role === 'sub_agent') {
                    $previous_wallet_amount = UserProfile::where('uid', $added_by)->value('wallet_amount');
                    if($oldUserCheck){
                        $commission_amount = CustomHelpers::getCommissionAmount($added_by_user_table, $payable_package_price);
                    }else{
                        $commission_amount = $payable_package_price * (20 / 100);
                    }
                    

                    // Save Affiliate History
                    $affiliate_history = new AffiliateHistory();
                    $affiliate_history->affiliator_uid = $added_by;
                    $affiliate_history->product_type = 'internet_package';
                    $affiliate_history->product_id = $internet_user_id;
                    $affiliate_history->status = 'approved';
                    $affiliate_history->commission_amount = $commission_amount;
                    if (!$affiliate_history->save()) {
                        $returned_data['status'] = 'error';
                        $returned_data['message'] = 'Something went wrong in Backend Affiliate History Storing!';
                        return ResponseWrapper::End($returned_data);
                    }

                    // Save Agent Commission Breakdown
                    $agentCommissionForBackend = new AgentCommissionBreakdown();
                    $agentCommissionForBackend->agent_uid = $added_by;
                    $agentCommissionForBackend->user_uid = $internet_user_id;
                    $agentCommissionForBackend->previous_wallet_amount = $previous_wallet_amount;
                    $agentCommissionForBackend->new_commission_amount = $commission_amount;
                    if (!$agentCommissionForBackend->save()) {
                        $returned_data['status'] = 'error';
                        $returned_data['message'] = 'Something went wrong in Backend Agent Commission Breakdown Storing!';
                        return ResponseWrapper::End($returned_data);
                    }

                    UserProfile::where('uid', $added_by)->update([
                        'wallet_amount' => $previous_wallet_amount + $commission_amount
                    ]);
                }

                // Save Mikrotik Payment
                $paymentForMikrotik = new BroadbandDbPayment();
                $paymentForMikrotik->username = $mobile_number;
                $paymentForMikrotik->amount = $payable_package_price;
                $paymentForMikrotik->type = 'bkash';
                $paymentForMikrotik->month = date('n');
                $paymentForMikrotik->submited_by = $user_id;
                $paymentForMikrotik->payment_date = Carbon::now();
                $paymentForMikrotik->last_update = Carbon::now();

                if (!$paymentForMikrotik->save()) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Mikrotik Payment Storing!';
                    return ResponseWrapper::End($returned_data);
                }

                // Save Mikrotik Secret
                $secretCheck = BroadbandDbSecret::where('username', $mobile_number)->exists();
                if($secretCheck){
                    $paymentForMikrotikSecret = BroadbandDbSecret::where('username', $mobile_number)->first();
                    $paymentForMikrotikSecret->profile = $package;
                    $paymentForMikrotikSecret->status = 0;
                    $paymentForMikrotikSecret->type = 'corporate_payment';
                    $updateData = [];
                    if ($agent || $sub_agent) {
                        if ($agent) {
                            $clientId = CorporateAgent::where('uid', $user_id)->value('client_id');
                            $agentId = $user_id;
                            $subAgentId = null;
                        } else {
                            $clientId = CorporateSubAgent::where('uid', $user_id)->value('client_id');
                            $agentId = CorporateSubAgent::where('uid', $user_id)->value('agent_id');
                            $subAgentId = $user_id;
                        }
                        $updateData = [
                            'client_id' => $clientId,
                            'agent_id' => $agentId,
                            'sub_agent_id' => $subAgentId,
                        ];
                    } else {
                        $updateData = [
                            'client_id' => $user_id,
                        ];
                    }
                    $paymentForMikrotikSecret->update($updateData);
                }else{
                    // Save Mikrotik Secret
                    $paymentForMikrotikSecret = new BroadbandDbSecret();
                    $paymentForMikrotikSecret->username = $mobile_number;
                    $paymentForMikrotikSecret->password = $password;
                    $paymentForMikrotikSecret->profile = $package;
                    $paymentForMikrotikSecret->service = 'pppoe';
                    if($sub_agent){
                        $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$user_id)->value('client_id');
                        $paymentForMikrotikSecret->zone = CorporateClient::where('uid', $editor_id_from_sub_agent)->value('zone_name');
                    }elseif($agent){
                        $editor_id_from_agent = CorporateAgent::where('uid',$user_id)->value('client_id');
                        $paymentForMikrotikSecret->zone = CorporateClient::where('uid', $editor_id_from_agent)->value('zone_name');
                    }elseif($client){
                        $paymentForMikrotikSecret->zone = CorporateClient::where('uid', $user_id)->value('zone_name');
                    }
                    $paymentForMikrotikSecret->status = 0;
                    $paymentForMikrotikSecret->type = 'corporate_payment';
                    $paymentForMikrotikSecret->dateOf_Inactive = Carbon::now()->endOfMonth();

                    if ($agent || $sub_agent) {
                        $paymentForMikrotikSecret->client_id = $agent ? CorporateAgent::where('uid', $user_id)->value('client_id') : CorporateSubAgent::where('uid', $user_id)->value('client_id');
                        $paymentForMikrotikSecret->agent_id = $sub_agent ? CorporateSubAgent::where('uid', $user_id)->value('agent_id') : $user_id;
                        $paymentForMikrotikSecret->sub_agent_id = $sub_agent ? $user_id : null;
                    } else {
                        $paymentForMikrotikSecret->client_id = $user_id;
                    }

                    if (!$paymentForMikrotikSecret->save()) {
                        $returned_data['status'] = 'error';
                        $returned_data['message'] = 'Something went wrong in Mikrotik Secret Storing!';
                        return ResponseWrapper::End($returned_data);
                    }
                }

                InternetUsers::where('uid', $internet_user_id)->update([
                    'connection_status' => 'active',
                    'package_id' => InternetPackageCorporate::where('en_title',$paymentTable->package)->value('id'),
                    'package_expire_date' => date('Y-m-t H:s:i')
                ]);

                Payment::where('payment_id', $allRequest['paymentID'])->update([
                    'trx_id' => $arr['trxID'],
                    'transaction_status' => 'Completed'
                ]);

                // Delete if any pending found ------
                Payment::where('uid', $internet_user_id)->where('transaction_status', 'pending')->where('payment_id', '!=', $allRequest['paymentID'])->delete();

                // Save Mikrotik User
                $broadbandDbUser = BroadbandDbUsers::where('username',$mobile_number)->exists();
                if(!$broadbandDbUser){
                    $mikrotikUser = new BroadbandDbUsers();
                    $mikrotikUser->username = $mobile_number;
                    $mikrotikUser->mobileno = $mobile_number;
                    $mikrotikUser->password = $mobile_number;
                    $mikrotikUser->save();
                    if (!$mikrotikUser->save()) {
                        $returned_data['status'] = 'error';
                        $returned_data['message'] = 'Something went wrong in Mikrotik Users Storing!';
                        return ResponseWrapper::End($returned_data);
                    }
                }

                $query = new MessageAndNotification();
                $query->uid = $internet_user_id;
                $query->title = 'ISP Broadband Internet User Create';
                $query->description = 'স্বাগতম! স্বাধীন ওয়াই-ফাই-এ আপনাকে স্বাগতম। নিরবিচ্ছিন্ন ইন্টারনেট সংযোগের লক্ষে আমরা কাজ করে চলেছি, সুন্দর আগামীর পথে আপনিও আমাদের সাথে থাকুন।';
                $query->sender_uid = $user_id;
                $query->is_read = 0;
                $query->save();

                $smsText = "আপনার স্বাধীন ওয়াইফাই অ্যাপ ইউজার আইডি: ".$mobile_number." এবং পাসওয়ার্ড: " . $password;
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile_number, $smsText);

                // Get the current date
                $currentDate = now();
                $daysInMonth = intval($currentDate->format('t'));
                $currentDay = intval($currentDate->format('j'));
                $remainingDays = ($daysInMonth - $currentDay) + 1;

                $response_data = [
                    'status' => 'success',
                    'message' => $arr['statusMessage'],
                    'transaction_id' => $trx_id,
                    'invoice_id' => $invoice_number,
                    'payment_id' => $allRequest['paymentID'],
                    'full_name' => $full_name,
                    'mobile_number' => $mobile_number,
                    'package' => $package,
                    'days_left' => $remainingDays,
                    'final_price' => $payable_package_price,
                    'payment_method' => 'bkash',
                    'payment_date' => Carbon::now()->toDateTimeString(),
                ];

                // Encode the response data as a query string
                $query_string = http_build_query($response_data);
                $frontendUrl = $_GET['source'];
                $redirect_url = '';
                if ($frontendUrl === 'openwifi') {
                        $redirectUrl = 'https://erp.myopenwifi.com/broadband/user-list/user/success?' . $query_string;
                        // $redirectUrl = 'http://10.142.1.10:8002/broadband/user-list/user/success?' . $query_string;
                } else {
                        $redirectUrl = 'https://erp.shadhinwifi.com/broadband/user-list/user/success?' . $query_string;
                        // $redirectUrl = 'http://10.142.1.10:8002/broadband/user-list/user/success?' . $query_string;
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

            $query_string = http_build_query($error_data);

            // Redirect to the error URL
            $frontendUrl = $_GET['source'];
            $redirect_url = '';

            if ($frontendUrl === 'openwifi') {
                    $redirectUrl = 'https://erp.myopenwifi.com/broadband/user-list/user/error?data=' . $query_string;
                    // $redirectUrl = 'http://10.142.1.10:8002/wifi/user-list/user/error?data=' . $query_string;
            } else {
                    $redirectUrl = 'https://erp.shadhinwifi.com/broadband/user-list/user/error?data=' . $query_string;
                    // $redirectUrl = 'http://10.142.1.10:8002/wifi/user-list/user/error?data=' . $query_string;
            }
            return redirect($redirectUrl);
        }

    }

    public function refund(Request $request)
    {
        $zone_id = Payment::where('payment_id', $request->paymentID)->value('zone_id');
        $settingsInfo = CorporateClientsSettings::where('client_uid', $zone_id)->get(['bkash_username', 'bkash_password', 'bkash_app_key', 'bkash_app_secret_key']);
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
