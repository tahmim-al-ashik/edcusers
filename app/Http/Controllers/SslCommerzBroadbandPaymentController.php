<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use DB;
use Illuminate\Http\Request;
use App\Library\SslCommerz\SslCommerzNotification;
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
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SslCommerzBroadbandPaymentController extends Controller
{

    // public function exampleEasyCheckout()
    // {
    //     return view('exampleEasycheckout');
    // }

    // public function exampleHostedCheckout()
    // {
    //     return view('exampleHosted');
    // }

    public function index(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        # Here you have to receive all the order data to initate the payment.
        # Let's say, your oder transaction informations are saving in a table called "orders"
        # In "orders" table, order unique identity is "transaction_id". "status" field contain status of the transaction, "amount" is the order amount to be paid and "currency" is for storing Site Currency which will be checked with paid currency.
        $zone_id = $request->get('zone_id');
        $internet_user_id = $request->get('uid');
        $partner_user_id = NetworkSupportCenter::where('zone_id', '=', $zone_id)->value('uid');
        $mobile_number = $request->get('mobile_number');
        $amount = $request->get('payable_package_price');
        $password = User::where('auth_id', $mobile_number)->value('text_password');
        $purpose = $request->get('purpose');
        $package = $request->get('package');

        $currentYear = Carbon::now()->format('Y');
        $currentMonth = Carbon::now()->format('m');
        $checkPayment = Payment::where('uid', $internet_user_id)
                                ->whereYear('created_at', $currentYear)
                                ->whereMonth('created_at', $currentMonth)
                                ->where('transaction_status','Completed')
                                ->exists();
        if ($checkPayment) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Paid for this month!';
            return ResponseWrapper::End($returned_data);
        }

        // Fetch branch information & branch information exists & check mikrotik connection
        $branchInfo = NetworkSupportCenter::where('zone_id', '=', $zone_id)->get();

        if (!$branchInfo) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Zone information not found!';
            return ResponseWrapper::End($returned_data);
        }

        $ipAddr = $branchInfo->implode('zone_ip', ', ');
        $mkUser = $branchInfo->implode('zone_username', ', ');
        $mkPass = $branchInfo->implode('zone_password', ', ');

        $API = new RouterOsApi();
        // if (!$API->connect($ipAddr, $mkUser, $mkPass)) {
        //     $returned_data['status'] = 'error';
        //     $returned_data['message'] = 'Failed to connect to MikroTik Router!';
        //     return ResponseWrapper::End($returned_data);
        // }

        // Generate the transaction ID and invoice ID
        $trxID = CustomHelpers::generateTransactionID('TXN');
        $invoiceID = CustomHelpers::generateInvoiceID('INV');
        $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

        $post_data = array();
        $post_data['total_amount'] = $amount; # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        // $post_data['tran_id'] = uniqid(); // tran_id must be unique
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

        // payment table
        $payment = new Payment();
        $payment->uid = $internet_user_id;
        $payment->zone_id = $zone_id;
        $payment->vendor_name = 'bkash';
        $payment->trx_id = $trxID;
        $payment->invoice_number = $invoiceID;
        $payment->amount = $amount;
        $payment->payment_id = $paymentID;
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
        $transactionForBackend->method = 'SSLCommerz';
        $transactionForBackend->amount = $amount;
        $transactionForBackend->purpose = 'payment_from_broadband_billing_portal';

        if (!$transactionForBackend->save()) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Something went wrong in Backend Transaction Storing!';
            return ResponseWrapper::End($returned_data);
        }
        // Log::info($payment_options);

        $sslc = new SslCommerzNotification();
        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->makePayment($post_data, 'hosted');
        // Log::info($payment_options);
        // Token Table -------
        $existToken = PaymentToken::where('invoice_number', $invoiceID)->first();
        if(empty($existToken)){
            $paymentToken = new PaymentToken();
            $paymentToken->vendor_name = 'SSLCommerz';
            $paymentToken->invoice_number = $invoiceID;
        } else {
            $paymentToken = PaymentToken::find($existToken['id']);
        }
        // $paymentToken->token = $payment_options['sessionkey'];
        $paymentToken->save();

        $return_data['sslCommerzURL'] = $payment_options['GatewayPageURL'];
        // $return_data['sslCommerz'] = $payment_options;
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
        $sslc = new SslCommerzNotification();

        #Check order status in order tabel against the transaction id or order id.
        $paymentTable = Payment::where('trx_id', $tran_id)->first();
        $userTable = User::where('id', $paymentTable->uid)->first();
        $transactionTable = Transaction::where('trx_id', $paymentTable->trx_id)->first();
        $full_name = UserProfile::where('uid', $paymentTable->uid)->value('full_name');

        $invoice_number = $paymentTable->invoice_number;
        $uid = $paymentTable->uid;
        $mobile_number = $userTable->auth_id;
        $password = $userTable->text_password;
        $zone_id = $paymentTable->zone_id;
        $zone_name = NetworkSupportCenter::where('zone_id', $zone_id)->value('zone_name');
        $package = $paymentTable->package;
        $payable_package_price = $paymentTable->amount;
        $trx_id = $paymentTable->trx_id;
        $payment_id = $paymentTable->payment_id;
        $user_id = $transactionTable->sender_uid;

        if ($paymentTable->transaction_status == 'pending') {
            $validation = $sslc->orderValidate($request->all(), $tran_id, $amount, $currency);

            if ($validation) {
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
                    if ($frontendUrl === 'billing') {
                        $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . 'error=Something went wrong in Mikrotik Payment Storing!';
                    } else {
                        $redirect_url = 'https://user.shadhinwifi.com/payment/failure?' . 'error=Something went wrong in Mikrotik Payment Storing!';
                    }

                    // Redirect to the error URL
                    return redirect($redirect_url);
                }

                InternetUsers::where('uid', $uid)->update([
                    'connection_status' => 'active',
                    'package_id' => InternetPackage::where('mikrotik_radius_group_name', $paymentTable->package)->value('id')
                ]);

                Payment::where('payment_id', $payment_id)->update([
                    'transaction_status' => 'Completed',
                    'vendor_name' => $card_type,
                ]);

                Transaction::where('trx_id', $trx_id)->update([
                    'method' => $card_type
                ]);

                // Delete if any pending found ------
                // Payment::where('uid', $uid)
                //     ->where('transaction_status', 'pending')
                //     ->where('payment_id', '!=', $allRequest['paymentID'])
                //     ->delete();

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
                $branchInfo = NetworkSupportCenter::where('zone_id', '=', $zone_id)->get();

                // Check if branch information exists
                if (!$branchInfo) {
                    if ($frontendUrl === 'billing') {
                        $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . 'error=Zone information not found!';
                    } else {
                        $redirect_url = 'https://user.shadhinwifi.com/payment/failure?' . 'error=Zone information not found!';
                    }

                    // Redirect to the error URL
                    return redirect($redirect_url);
                }

                // Api Variables
                $ipAddr = $branchInfo->implode('zone_ip', ', ');
                $mkUser = $branchInfo->implode('zone_username', ', ');
                $mkPass = $branchInfo->implode('zone_password', ', ');
                $oldUserCheck = BroadbandDbSecret::where('username', $mobile_number)->first();
                $API = new RouterOsApi();

                // Connect to MikroTik Router
                // if ($API->connect($ipAddr, $mkUser, $mkPass)) {
                //     if(!$oldUserCheck){
                //         $API->comm('/ppp/secret/add', array('name' => $mobile_number, 'password' => $password, 'service' => 'pppoe', 'profile' => $package));
                //         $API->disconnect();
                //     }else{
                //         if($oldUserCheck->profile ==! $package){
                //             $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id","?name" => $mobile_number));
                //             $API->comm("/ppp/secret/set", array(".id" => $arrID[0][".id"], "name"=> $mobile_number, "password" => $password, "service" => 'pppoe' , "profile" => $package));
                //             $API->disconnect();
                //         }else{
                //             $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $mobile_number));
                //             $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
                //             $API->disconnect();
                //         }
                //     }
                // } else {
                //     if ($frontendUrl === 'billing') {
                //         $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . 'error=Failed to connect to Mikrotik router.';
                //     } else {
                //         $redirect_url = 'http://10.142.3.35:3000/payment/failure?' . 'error=Failed to connect to Mikrotik router.';
                //     }

                //     // Redirect to the error URL
                //     return redirect($redirect_url);
                // }

                // Save Mikrotik Secret
                if(!$oldUserCheck){
                    $paymentForMikrotikSecret = new BroadbandDbSecret();
                    $paymentForMikrotikSecret->username = $mobile_number;
                    $paymentForMikrotikSecret->password = $password;
                    $paymentForMikrotikSecret->profile = $package;
                    $paymentForMikrotikSecret->service = 'pppoe';
                    $paymentForMikrotikSecret->zone = $zone_name;
                    $paymentForMikrotikSecret->status = '0';
                    $paymentForMikrotikSecret->type = 'paid';
                    $paymentForMikrotikSecret->dateOf_Inactive = Carbon::now()->endOfMonth();
                    $paymentForMikrotikSecret->client_id = $user_id;

                    if (!$paymentForMikrotikSecret->save()) {
                        Log::info($mobile_number.'- Something went wrong in Mikrotik Secret Storing!');
                    }
                }else{
                    BroadbandDbSecret::where('username', $mobile_number)
                        ->update([
                            'profile' => $package,
                            'zone' => $zone_name,
                            'status' => '0',
                            'type' => 'paid',
                            'dateOf_Inactive' => Carbon::now()->endOfMonth(),
                            'client_id' => $user_id,
                        ]);
                }

                if ($frontendUrl === 'registration') {
                    $smsText = "অভিনন্দন! অ্যাপ ইউজার আইডি: ".$mobile_number." এবং পাসওয়ার্ড: " . $password;
                    $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($mobile_number, $smsText);
                }

                // Get the current date
                $currentDate = now();
                $daysInMonth = intval($currentDate->format('t'));
                $currentDay = intval($currentDate->format('j'));
                $remainingDays = ($daysInMonth - $currentDay) + 1;

                $response_data = [
                    'status' => 'success',
                    // 'message' => $arr['statusMessage'],
                    'transaction_id' => $trx_id,
                    'invoice_id' => $invoice_number,
                    'payment_id' => $payment_id,
                    'full_name' => $full_name,
                    'mobile_number' => $mobile_number,
                    'package' => $package,
                    'days_left' => $remainingDays,
                    'final_price' => $payable_package_price,
                    'payment_method' => 'SSLCommerz',
                    'payment_date' => Carbon::now()->toDateTimeString(),
                ];

                // Encode the response data as a query string
                $query_string = http_build_query($response_data);

                if ($frontendUrl === 'billing') {
                        $redirect_url = 'https://billing.shadhinwifi.com/payment/success?' . $query_string;
                } else {
                        $redirect_url = 'https://user.shadhinwifi.com/payment/success?' . $query_string;
                }

                return redirect($redirect_url);
            }
        } else if ($paymentTable->status == 'Processing' || $paymentTable->status == 'Completed') {
            /*
             That means through IPN Order status already updated. Now you can just show the customer that transaction is completed. No need to udate database.
             */
            // echo "Transaction is successfully Completed";

            // Check if branch information exists
            if ($frontendUrl === 'billing') {
                $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
            } else {
                $redirect_url = 'https://user.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
            }

            // Redirect to the error URL
            return redirect($redirect_url);
        } else {
            #That means something wrong happened. You can redirect customer to your product page.
            // echo "Invalid Transaction";

            // Check if branch information exists
            if ($frontendUrl === 'billing') {
                $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
            } else {
                $redirect_url = 'https://user.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
            }

            // Redirect to the error URL
            return redirect($redirect_url);
        }
    }

    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $frontendUrl = $request->input('value_a');
        $paymentDelete = Payment::where('trx_id', $tran_id)->where('transaction_status', 'pending')->delete();
        $transactionDelete = Transaction::where('trx_id', $tran_id)->latest()->first()->delete();
        if ($frontendUrl === 'billing') {
            $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
        } else {
            $redirect_url = 'https://user.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
        }
        return redirect($redirect_url);
    }

    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $frontendUrl = $request->input('value_a');
        $paymentDelete = Payment::where('trx_id', $tran_id)->where('transaction_status', 'pending')->delete();
        $transactionDelete = Transaction::where('trx_id', $tran_id)->latest()->first()->delete();
        if ($frontendUrl === 'billing') {
            $redirect_url = 'https://billing.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
        } else {
            $redirect_url = 'https://user.shadhinwifi.com/payment/failure?' . 'error=Invalid Transaction!';
        }
        return redirect($redirect_url);
    }

    public function ipn(Request $request){}

}