<?php

namespace App\Http\Controllers\panel\shadhin_client;

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
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\MessageAndNotification;
use App\Models\NetworkSupportCenter;
use App\Models\Payment;
use App\Models\PaymentToken;
use App\Models\Transaction;
use App\Models\BroadbandDbPaymentToken;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;
use URL;
use Illuminate\Support\Str;
use Sabberworm\CSS\Value\URL as ValueURL;

class ShadhinBroadbandCheckoutURLController extends Controller
{
    private $base_url;

    public function __construct()
    {        
        $this->base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function authHeaders(){
        $status = 200;
        return array(
            'Content-Type:application/json',
            'Authorization:' .$this->grant($status),
            'X-APP-Key:PBD0wucYMjDlgbw7lQNI6omctc'
        );
    }

    public function curlWithBody($url, $header, $method, $body_data_json){
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

    public function grant($status)
    {
        $bkash_username = '01971399998';
        $bkash_password = '?H7QP}eQa<A';
        $bkash_app_key = 'PBD0wucYMjDlgbw7lQNI6omctc';
        $bkash_app_secret_key = 'RQefyX8FVwSTUPLNvAaweFg8CM84MLlhCVu5Q1be19EuiyJgfgcT';

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
        $zone_id = $request->get('zone_id');
        $internet_user_id = $request->get('uid');
        $partner_user_id = NetworkSupportCenter::where('zone_id', '=', $zone_id)->value('uid');
        $mobile_number = $request->get('mobile_number');
        $amount = $request->get('payable_package_price');
        $password = User::where('auth_id', $mobile_number)->value('text_password');
        $purpose = $request->get('purpose');
        $package = $request->get('package');

        $header = $this->authHeaders();
        $website_url = 'https://backend.shadhinwifi.com';

        $currentYear = Carbon::now()->format('Y');
        $currentMonth = Carbon::now()->format('m');
        $checkPayment = Payment::where('uid', $internet_user_id)->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->where('transaction_status','Completed')->exists();
        if ($checkPayment) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Paid for this month!';
            return ResponseWrapper::End($returned_data);
        }
        
        // Fetch branch information & branch information exists & check mikrotik connection
        $branchInfo = NetworkSupportCenter::where('zone_id', '=', $zone_id)->first(['zone_ip','zone_username','zone_password']);

        if (!$branchInfo) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Zone information not found!';
            return ResponseWrapper::End($returned_data);
        }

        $ipAddr = $branchInfo->zone_ip;
        $mkUser = $branchInfo->zone_username;
        $mkPass = $branchInfo->zone_password;

        $API = new RouterOsApi();
        if (!$API->connect($ipAddr, $mkUser, $mkPass)) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Failed to connect to MikroTik Router!';
            return ResponseWrapper::End($returned_data);
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
            'callbackURL' => $website_url.'/api/v2/shadhin/broadband/payment/bkash/callback?source='.$request->get('source'),
            'amount' => $amount,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceID // you can pass here OrderID
        );

        $body_data_json = json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/create',$header,'POST',$body_data_json);
        $res_array = json_decode($response['body'],true);

        if (empty($res_array['paymentID']) && $response['status'] !== 200) {
            // Token might be expired, force refresh
            $this->grant($response['status']);

            // Retry with refreshed token
            $header = $this->authHeaders();
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

            // payment table
            $payment = new Payment();
            $payment->uid = $internet_user_id;
            $payment->zone_id = $zone_id;
            $payment->vendor_name = 'bkash';
            $payment->trx_id = $trxID;
            $payment->invoice_number = $invoiceID;
            $payment->amount = $amount;
            $payment->payment_id = $res_array['paymentID'];
            $payment->process_status = '1';
            $payment->purpose = $purpose;
            $payment->package = $package;
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
            $transactionForBackend->sender_uid = $partner_user_id;
            $transactionForBackend->receiver_uid = $internet_user_id;
            $transactionForBackend->method = 'bkash';
            $transactionForBackend->amount = $amount;
            $transactionForBackend->purpose = 'payment_from_broadband_billing_portal';

            if (!$transactionForBackend->save()) {
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
                return ResponseWrapper::End($returned_data);
            }
        }
        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['result'] = $res_array['bkashURL'];
        return ResponseWrapper::End($returned_data);
    }

    public function execute($paymentID)
    {
	    $bkash_username = '01971399998';
	    $token =  BroadbandDbPaymentToken::where('mr_username', $bkash_username)->value('access_token');
        $header = array(
            'Content-Type:application/json',
            'Authorization:' .$token,
            'X-APP-Key:PBD0wucYMjDlgbw7lQNI6omctc'
        );

        $body_data = array(
            'paymentID' => $paymentID
        );

        $body_data_json = json_encode($body_data);
        $response = $this->curlWithBody('/tokenized/checkout/execute',$header,'POST',$body_data_json);
        return $response;
    }

    public function query($paymentID)
    {
        $header = $this->authHeaders();
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
        $frontendUrl = $_GET['source'];
        if(isset($allRequest['status']) && $allRequest['status'] == 'success'){
            $response = $this->execute($allRequest['paymentID']);
            $arr = json_decode($response['body'],true);
            if((!isset($arr['trxID']) || empty($arr['trxID'])) && array_key_exists("message",$arr)){
                $response = $this->query($allRequest['paymentID']);
                $arr = json_decode($response['body'],true);
            }

            if((!isset($arr['trxID']) || empty($arr['trxID'])) && array_key_exists("statusCode", $arr) && $arr['statusCode'] != '0000'){
                if ($frontendUrl === 'billing') {
                    return redirect('https://billing.shadhinwifi.com/payment/failure?data='.$arr['statusMessage']);
                } else {
                    return redirect('https://user.shadhinwifi.com/payment/failure?data='.$arr['statusMessage']);
                }
            }else{
                // response save to your db -----------------------
                $paymentTable = Payment::where('payment_id',$allRequest['paymentID'])->first();
                $userTable = User::where('id',$paymentTable->uid)->first();
                $transactionTable = Transaction::where('trx_id',$paymentTable->trx_id)->first();
                $packageId = InternetPackage::where('mikrotik_radius_group_name',$paymentTable->package)->value('id');
                $full_name = UserProfile::where('uid',$paymentTable->uid)->value('full_name');

                $invoice_number = $paymentTable->invoice_number;
                $uid = $paymentTable->uid;
                $mobile_number = $userTable->auth_id;
                $password = $userTable->text_password;
                $zone_id = $paymentTable->zone_id;
                $package = $paymentTable->package;
                $payable_package_price = $paymentTable->amount;
                $trx_id = $paymentTable->trx_id;
                $user_id = $transactionTable->sender_uid;
                $oldUserCheck = BroadbandDbSecret::where('username', $mobile_number)->first();

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

                InternetUsers::where('uid', $uid)->update([
                    'connection_status' => 'active',
                    'package_id' => $packageId,
                    'activation_date' => Carbon::now(),
                ]);

                Payment::where('payment_id', $allRequest['paymentID'])->update([
                    'trx_id' => $arr['trxID'],
                    'transaction_status' => 'Completed'
                ]);

                // Delete if any pending found ------
                Payment::where('uid', $uid)->where('transaction_status', 'pending')->where('payment_id', '!=', $allRequest['paymentID'])->delete();

                // Save Mikrotik User
                $checkUserDb = BroadbandDbUsers::where('username', $mobile_number)->exists();
                if(!$checkUserDb){
                    $mikrotikUser = new BroadbandDbUsers();
                    $mikrotikUser->username = $mobile_number;
                    $mikrotikUser->mobileno = $mobile_number;
                    $mikrotikUser->password = $mobile_number;
                    $mikrotikUser->save();
                }

                // Fetch branch information
                $branchInfo = NetworkSupportCenter::where('zone_id', '=', $zone_id)->first(['zone_ip','zone_username','zone_password','zone_name']);

                // Check if branch information exists
                if (!$branchInfo) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Zone information not found!';
                    return ResponseWrapper::End($returned_data);
                }

                // Api Variables
                $ipAddr = $branchInfo->zone_ip;
                $mkUser = $branchInfo->zone_username;
                $mkPass = $branchInfo->zone_password;
                $zoneName = $branchInfo->zone_name;

                $API = new RouterOsApi();
                // Connect to MikroTik Router
                if ($API->connect($ipAddr, $mkUser, $mkPass)) {

                    if(!$oldUserCheck){
                        $ARRAY = $API->comm('/ppp/secret/add',
                                    array('name' => $mobile_number,
                                            'password' => $password,
                                            'service' => 'pppoe',
                                            'profile' => $package));
                        $API->disconnect();
                        $paymentForMikrotikSecret = new BroadbandDbSecret();
                        $paymentForMikrotikSecret->username = $mobile_number;
                        $paymentForMikrotikSecret->password = $password;
                        $paymentForMikrotikSecret->profile = $package;
                        $paymentForMikrotikSecret->service = 'pppoe';
                        $paymentForMikrotikSecret->zone = $zoneName;
                        $paymentForMikrotikSecret->status = '0';
                        $paymentForMikrotikSecret->type = 'paid';
                        $paymentForMikrotikSecret->dateOf_Inactive = Carbon::now()->endOfMonth();
                        $paymentForMikrotikSecret->client_id = $user_id;
    
                        if (!$paymentForMikrotikSecret->save()) {
                            Log::info($mobile_number.'- Something went wrong in Mikrotik Secret Storing!');
                        }
                    }else{
                        if($oldUserCheck->profile ==! $package){
                            $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id","?name" => $mobile_number));
                            $API->comm("/ppp/secret/set", array(".id" => $arrID[0][".id"], "name"=> $mobile_number, "password" => $password, "service" => 'pppoe' , "profile" => $package));
                            $API->disconnect();
                        }else{
                            $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $mobile_number));
                            $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                            $API->disconnect();
                        }

                        $paymentForMikrotikSecret = BroadbandDbSecret::where('username', $mobile_number)->first();
                        $paymentForMikrotikSecret->profile = $package;
                        $paymentForMikrotikSecret->zone = $zoneName;
                        $paymentForMikrotikSecret->status = '0';
                        $paymentForMikrotikSecret->type = 'paid';
                        $paymentForMikrotikSecret->dateOf_Inactive = Carbon::now()->endOfMonth();
                        $paymentForMikrotikSecret->client_id = $user_id;
                        $paymentForMikrotikSecret->save();
                    }
                } else {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Failed to connect to MikroTik Router!';
                    return ResponseWrapper::End($returned_data);
                }

                $smsText = "অভিনন্দন! আপনার ইউজার আইডি: ".$mobile_number." এবং পাসওয়ার্ড: " . $password;
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

                // Append the query string to the redirect URL
                if ($frontendUrl === 'billing') {
                    $redirect_url = 'https://billing.shadhinwifi.com/payment/success?' . $query_string;
                } else {
                    $redirect_url = 'https://user.shadhinwifi.com/payment/success?' . $query_string;
                }
                return redirect($redirect_url);

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
            if ($frontendUrl === 'billing') {
                $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . $query_string;
            } else {
                $redirect_url = 'https://user.shadhinwifi.com/payment/failure?' . $query_string;
            }

            // Redirect to the error URL
            return redirect($redirect_url);
        }
    }

    public function refund(Request $request)
    {
        $header = $this->authHeaders();
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
