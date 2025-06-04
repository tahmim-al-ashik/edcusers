<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Library\SslCommerz\SslCommerzHotspotNotification;
use DB;
use Illuminate\Http\Request;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbUsers;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\Payment;
use App\Models\PaymentToken;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WifiDbBkashInfo;
use App\Models\WifiDbPayment;
use App\Models\WifiDbRadReply;
use App\Models\WifiDbRadUserGroup;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SslCommerzHotspotPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $editor_id = $request->get('zone_id');
        $internet_user_id = $request->get('uid');

        $internet_user = User::where('id', $internet_user_id)->exists();
        if (!$internet_user) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Internet User not found!';
            return ResponseWrapper::End($returned_data);
        }

        $currentYear = Carbon::now()->format('Y');
        $currentMonth = Carbon::now()->format('m');
        $checkPayment = Payment::where('uid', $internet_user_id)->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->where('transaction_status','Completed')->exists();
        if ($checkPayment) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Paid for this month!';
            return ResponseWrapper::End($returned_data);
        }

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');
        $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

        $post_data = array();
        $post_data['total_amount'] = $request->get('payable_package_price');
        $post_data['currency'] = "BDT";
        // $post_data['tran_id'] = uniqid();
        $post_data['tran_id'] = $trxID; // tran_id must be unique

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = 'Customer Name';
        $post_data['cus_email'] = 'customer@mail.com';
        $post_data['cus_add1'] = 'Customer Address';
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = '8801XXXXXXXXX';
        $post_data['cus_fax'] = "";

        # SHIPMENT INFORMATION
        $post_data['ship_name'] = "Store Test";
        $post_data['ship_add1'] = "Dhaka";
        $post_data['ship_add2'] = "Dhaka";
        $post_data['ship_city'] = "Dhaka";
        $post_data['ship_state'] = "Dhaka";
        $post_data['ship_postcode'] = "1000";
        $post_data['ship_phone'] = "";
        $post_data['ship_country'] = "Bangladesh";

        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = "Internet";
        $post_data['product_category'] = "Service";
        $post_data['product_profile'] = "Internet-Service";

        # OPTIONAL PARAMETERS
        $post_data['value_a'] = $request->get('url') ?? 'registration';
        $post_data['value_b'] = "ref002";
        $post_data['value_c'] = "ref003";
        $post_data['value_d'] = "ref004";

        #Before  going to initiate the payment order status need to insert or update as Pending.
        $payment = new Payment();
        $payment->uid = $request->get('uid');
        $payment->zone_id = $editor_id;
        $payment->vendor_name = 'SSLCommerz';
        $payment->trx_id = $trxID;
        $payment->invoice_number = $invoiceID;
        $payment->amount = $request->get('payable_package_price');
        $payment->payment_id = $paymentID;
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
        $transactionForBackend->method = 'SSLCommerz';
        $transactionForBackend->amount = $request->get('payable_package_price');
        $transactionForBackend->purpose = 'hotspot_internet_bill_payment';

        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }

        $sslc = new SslCommerzHotspotNotification();
        $payment_options = $sslc->makePayment($post_data, 'hosted');

        // Token Table -------
        $existToken = PaymentToken::where('invoice_number', $invoiceID)->first();
        if(empty($existToken)){
            $paymentToken = new PaymentToken();
            $paymentToken->vendor_name = 'SSLCommerz';
            $paymentToken->invoice_number = $invoiceID;
        } else {
            $paymentToken = PaymentToken::find($existToken['id']);
        }
        $paymentToken->token = $payment_options['sessionkey'] ?? null;
        $paymentToken->save();

        $return_data['sslCommerzURL'] = $payment_options['GatewayPageURL'];
        $return_data['message'] = 'success';
        $return_data['status'] = 'success';

        return ResponseWrapper::End($return_data);
    }


    public function success(Request $request)
    {

        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        $frontendUrl = $request->input('value_a');

        $bank_tran_id = $request->input('bank_tran_id');
        $val_id = $request->input('val_id');
        $currency_type = $request->input('currency_type');
        $card_type = $request->input('card_type');
        $sslc = new SslCommerzHotspotNotification();

        #Check order status in order tabel against the transaction id or order id.
        $paymentTable = Payment::where('trx_id', $tran_id)->first();
        $userTable = User::where('id', $paymentTable->uid)->first();
        $transactionTable = Transaction::where('trx_id', $paymentTable->trx_id)->first();
        $full_name = UserProfile::where('uid', $paymentTable->uid)->value('full_name');

        $invoice_number = $paymentTable->invoice_number;
        $internet_user_id = $paymentTable->uid;
        $mobile_number = $userTable->auth_id;
        $password = $userTable->text_password;
        $zone_id = $paymentTable->zone_id;
        $package = $paymentTable->package;
        $package_id = InternetPackage::where('mikrotik_radius_group_name', $package)->value('id');
        $expiration = InternetPackage::where('mikrotik_radius_group_name', $package)->value('expiration');

        $payable_package_price = $paymentTable->amount;
        $trx_id = $paymentTable->trx_id;
        $editor_id = $transactionTable->sender_uid;

        if ($paymentTable->transaction_status == 'pending') {
            $validation = $sslc->orderValidate($request->all(), $tran_id, $amount, $currency);

            if ($validation) {

                $internet_user = InternetUsers::where('uid',$internet_user_id)->first();
                if($internet_user){
                    $internet_user->update([
                        'package_id' => $package_id
                    ]);
                }

                // transaction status update
                Payment::where('payment_id', $paymentTable->payment_id)->update([
                    'transaction_status' => 'Completed',
                    'vendor_name' => $card_type,
                ]);

                Transaction::where('trx_id', $trx_id)->update([
                    'method' => $card_type
                ]);

                // Delete if any pending found ------
                Payment::where('uid', $internet_user_id)->where('transaction_status', 'pending')->where('payment_id', '!=', $paymentTable->payment_id)->delete();

                // Save Radius Payment
                $paymentForRadius = new WifiDbPayment();
                $paymentForRadius->username = $mobile_number;
                $paymentForRadius->amount = $payable_package_price;
                $paymentForRadius->created_at = Carbon::now();

                if (!$paymentForRadius->save()){
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
                    return ResponseWrapper::End($returned_data);
                }

                // bkash info -----
                $paymentForRadius = new WifiDbBkashInfo();
                $paymentForRadius->username = $mobile_number;
                $paymentForRadius->amount = $payable_package_price;
                $paymentForRadius->payment_id = $paymentTable->payment_id;
                $paymentForRadius->transaction_id = $trx_id;
                $paymentForRadius->status = 'Success';
                $paymentForRadius->created_at = Carbon::now();

                if (!$paymentForRadius->save()){
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = 'Something went wrong in Radius Payment Storing!';
                    return ResponseWrapper::End($returned_data);
                }

                $is_date_expire = Payment::where('uid', $internet_user_id)->value('created_at');
                $expiry_date = Carbon::parse($is_date_expire)->addDays(round($expiration / 1440))->format('Y-m-d\TH:i:s');
                WifiDbRadReply::where('username',$mobile_number)->update(['value' => $expiry_date]);
                WifiDbRadUserGroup::where('username',$mobile_number)->update(['groupname' => $package]);

                if ($frontendUrl === 'registration') {
                    $smsText = "আপনার স্বাধীন ওয়াইফাই ইউজার আইডি: ".$mobile_number." এবং পাসওয়ার্ড: " . $password;
                    $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile_number, $smsText);
                }

                $response_data = [
                    'status' => 'success',
                    // 'message' => $arr['statusMessage'],
                    'transaction_id' => $trx_id,
                    'invoice_id' => $invoice_number,
                    'payment_id' => $paymentTable->payment_id,
                    'full_name' => $full_name,
                    'mobile_number' => $mobile_number,
                    'package' => $package,
                    'final_price' => $payable_package_price,
                    'payment_method' => $card_type,
                    'payment_date' => Carbon::now()
                ];

                // Encode the response data as a query string
                $query_string = http_build_query($response_data);

                // Append the query string to the redirect URL
                $redirect_url = 'https://payment.shadhinwifi.com/payment/success?' . $query_string;
                return redirect($redirect_url);
            }
        } else if ($paymentTable->status == 'Completed') {
            $redirect_url = 'https://payment.shadhinwifi.com/payment/failure?' . 'Already Paid for this month!';
            return redirect($redirect_url);
        } else {
            $redirect_url = 'https://payment.shadhinwifi.com/payment/failure?' . 'Something went wrong!';
            return redirect($redirect_url);
        }
    }

    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $paymentDelete = Payment::where('trx_id', $tran_id)->where('transaction_status', 'pending')->delete();
        $transactionDelete = Transaction::where('trx_id', $tran_id)->latest()->first()->delete();
        $redirect_url = 'https://payment.shadhinwifi.com/payment/failure';
        return redirect($redirect_url);
    }

    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $paymentDelete = Payment::where('trx_id', $tran_id)->where('transaction_status', 'pending')->delete();
        $transactionDelete = Transaction::where('trx_id', $tran_id)->latest()->first()->delete();
        $redirect_url = 'https://payment.shadhinwifi.com/payment/failure';
        return redirect($redirect_url);
    }

    public function ipn(Request $request){}

}