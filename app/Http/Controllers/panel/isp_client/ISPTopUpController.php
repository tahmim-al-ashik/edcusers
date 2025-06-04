<?php

namespace App\Http\Controllers\panel\isp_client;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Models\BroadbandDbPayment;
use App\Models\BroadbandDbSecret;
use App\Models\BroadbandDbZone;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use App\Models\InternetUsers;
use App\Models\TransactionPanelMoney;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ISPTopUpController extends Controller
{
    // get transaction history list --
    public function transactionHistoryList($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $roleCheck = User::where('id', $uid)->value('base_role');

        // Fetch transactions based on user role
        if ($roleCheck === 'admin') {
            $transactionList = TransactionPanelMoney::query();
        } else {
            $transactionList = TransactionPanelMoney::where(function($query) use ($uid) {
                $query->where('sender_uid', $uid)
                      ->orWhere('receiver_uid', $uid);
            });
        }

        // Order the transactions by created_at ascending
        $transactionList->orderBy('created_at', 'desc');

        // Get the required transaction details
        $transactions = $transactionList->get([
            'transaction_panel_money.id',
            'transaction_panel_money.amount',
            'transaction_panel_money.sender_uid',
            DB::raw("(SELECT full_name FROM user_profiles WHERE user_profiles.uid = transaction_panel_money.sender_uid) as sender_name"),
            DB::raw("(SELECT mobile_number FROM user_profiles WHERE user_profiles.uid = transaction_panel_money.sender_uid) as sender_mobile"),
            'transaction_panel_money.receiver_uid',
            DB::raw("(SELECT full_name FROM user_profiles WHERE user_profiles.uid = transaction_panel_money.receiver_uid) as receiver_name"),
            DB::raw("(SELECT mobile_number FROM user_profiles WHERE user_profiles.uid = transaction_panel_money.receiver_uid) as receiver_mobile"),
            'transaction_panel_money.trx_id',
            'transaction_panel_money.invoice_number',
            'transaction_panel_money.payment_id',
            'transaction_panel_money.type',
            'transaction_panel_money.status',
            'transaction_panel_money.remarks',
            'transaction_panel_money.created_at',
        ]);

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $transactions;

        // Ensure the structure of returned data is correct
        return ResponseWrapper::End($returned_data);
    }

    // balance transfer from admin to client
    public function addBalanceFromAdminToClient(Request $request, $admin_id, $client_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Validate the request input
        $request->validate([
            'balance' => 'required|numeric|min:0',
            'type' => 'required|in:add,minus'
        ]);

        // Fetch the client and update their balance
        $client = CorporateClient::where('uid', $client_id)->first();
        if ($client) {
            $balance = $request->get('balance');
            $addOrMinus = $request->get('type');
            if ($addOrMinus === 'add') {
                $new_balance = $client->balance + $balance;
            } elseif ($addOrMinus === 'minus') {
                if ($client->balance < $balance) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = "Insufficient balance. Current balance is {$client->balance} tk.";
                    return ResponseWrapper::End($returned_data);
                }
                $new_balance = $client->balance - $balance;
            }

            $client->update(['balance' => $new_balance]);

            // Generate the transaction ID and invoice ID
            $trxID = CustomHelpers::generateTransactionID('TXN');
            $invoiceID = CustomHelpers::generateInvoiceID('INV');
            $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

            $transaction = new TransactionPanelMoney;
            $transaction->amount = $request->get('balance');
            $transaction->sender_uid = $admin_id;
            $transaction->receiver_uid = $client_id;
            $transaction->trx_id = $trxID;
            $transaction->invoice_number = $invoiceID;
            $transaction->payment_id = $paymentID;
            $transaction->type = $addOrMinus === 'add' ? 'panel_money_add' : 'panel_money_remove';
            $transaction->status = 'Completed';
            $transaction->remarks = $request->get('remarks');
            $transaction->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = "Balance has been successfully " . ($addOrMinus === 'add' ? 'added' : 'removed') . ".";
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Client not found.";
        }

        return ResponseWrapper::End($returned_data);
    }

    // balance transfer from client to agent
    public function addBalanceFromClientToAgent(Request $request, $client_id, $agent_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Validate the request input
        $request->validate([
            'balance' => 'required|numeric|min:0'
        ]);

        // Check if the client user exists
        $client_exists = User::where('id', $client_id)->where('base_role', 'corporate')->exists();
        if (!$client_exists) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Client user not found.";
            return ResponseWrapper::End($returned_data);
        }

        // Fetch the client balance
        $client_balance = CorporateClient::where('uid', $client_id)->value('balance');
        if ($client_balance < $request->get('balance')) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Insufficient balance. Your current balance is {$client_balance} tk.";
            return ResponseWrapper::End($returned_data);
        }

        // Fetch the agent and update their balance
        $agent = CorporateAgent::where('uid', $agent_id)->first();
        if ($agent) {
            $new_agent_balance = $agent->balance + $request->get('balance');
            $agent->update(['balance' => $new_agent_balance]);

            // Update the client's balance
            $new_client_balance = $client_balance - $request->get('balance');
            CorporateClient::where('uid', $client_id)->update(['balance' => $new_client_balance]);

            // Generate the transaction ID and invoice ID
            $trxID = CustomHelpers::generateTransactionID('TXN');
            $invoiceID = CustomHelpers::generateInvoiceID('INV');
            $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

            $transaction = new TransactionPanelMoney;
            $transaction->amount = $request->get('balance');
            $transaction->sender_uid = $client_id;
            $transaction->receiver_uid = $agent_id;
            $transaction->trx_id = $trxID;
            $transaction->invoice_number = $invoiceID;
            $transaction->payment_id = $paymentID;
            $transaction->type = 'panel_money_add';
            $transaction->status = 'Completed';
            $transaction->remarks = $request->get('remarks');
            $transaction->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = "Balance has been successfully added.";
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Agent not found.";
        }
        return ResponseWrapper::End($returned_data);
    }

    // balance transfer from agent to sub-agent
    public function addBalanceFromAgentToSubAgent(Request $request, $agent_id, $sub_agent_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Validate the request input
        $request->validate([
            'balance' => 'required|numeric|min:0'
        ]);

        // Check if the admin user exists
        $agent_exists = User::where('id', $agent_id)->where('base_role','agent')->exists();
        if (!$agent_exists) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Agent user not found.";
            return ResponseWrapper::End($returned_data);
        }

        // Fetch the client balance
        $agent_balance = CorporateAgent::where('uid', $agent_id)->value('balance');
        if ($agent_balance < $request->get('balance')) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Insufficient balance. Your current balance is {$agent_balance} tk.";
            return ResponseWrapper::End($returned_data);
        }

        // Fetch the client and update their balance
        $subAgent = CorporateSubAgent::where('uid', $sub_agent_id)->first();
        if ($subAgent) {
            $new_balance = $subAgent->balance + $request->get('balance');
            $subAgent->update(['balance' => $new_balance]);

            // Update the client's balance
            $new_agent_balance = $agent_balance - $request->get('balance');
            CorporateAgent::where('uid', $agent_id)->update(['balance' => $new_agent_balance]);

            // Generate the transaction ID and invoice ID
            $trxID = CustomHelpers::generateTransactionID('TXN');
            $invoiceID = CustomHelpers::generateInvoiceID('INV');
            $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

            $transaction = new TransactionPanelMoney;
            $transaction->amount = $request->get('balance');
            $transaction->sender_uid = $agent_id;
            $transaction->receiver_uid = $sub_agent_id;
            $transaction->trx_id = $trxID;
            $transaction->invoice_number = $invoiceID;
            $transaction->payment_id = $paymentID;
            $transaction->type = 'panel_money_add';
            $transaction->status = 'Completed';
            $transaction->remarks = $request->get('remarks');
            $transaction->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = "Balance has been successfully added.";
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Sub-Agent not found.";
        }

        return ResponseWrapper::End($returned_data);
    }

    // balance transfer from agent to sub-agent
    public function addBalanceFromWallet($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Checking User
        $client = CorporateClient::where('uid', $uid)->exists();
        $agent = CorporateAgent::where('uid', $uid)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $uid)->exists();
        if (!$client && !$agent && !$sub_agent) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'User not found';
            return ResponseWrapper::End($returned_data);
        }

        $usersTableCheck = User::where('id', $uid)->exists();
        $usersProfileTableCheck = UserProfile::where('uid', $uid)->exists();
        if (!$usersTableCheck && !$usersProfileTableCheck) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'You are not allowed to add this!';
            return ResponseWrapper::End($returned_data);
        }

        // Determine the user's role and corresponding table
        $userTable = null;
        if ($client = CorporateClient::where('uid', $uid)->first()) {
            $userTable = $client;
        } elseif ($agent = CorporateAgent::where('uid', $uid)->first()) {
            $userTable = $agent;
        } elseif ($sub_agent = CorporateSubAgent::where('uid', $uid)->first()) {
            $userTable = $sub_agent;
        }

        if ($userTable) {
            $userProfileTable = UserProfile::where('uid', $uid)->first();

            // Generate the transaction ID and invoice ID
            $trxID = CustomHelpers::generateTransactionID('TXN');
            $invoiceID = CustomHelpers::generateInvoiceID('INV');
            $paymentID = CustomHelpers::generatePaymentID($trxID, $invoiceID);

            $transaction = new TransactionPanelMoney;
            $transaction->amount = $userProfileTable->wallet_amount;
            $transaction->sender_uid = $uid;
            $transaction->receiver_uid = $uid;
            $transaction->trx_id = $trxID;
            $transaction->invoice_number = $invoiceID;
            $transaction->payment_id = $paymentID;
            $transaction->type = 'wallet_balance_transfer';
            $transaction->status = 'Completed';
            $transaction->save();

            $new_balance = $userTable->balance + $userProfileTable->wallet_amount;
            $new_wallet = 0;

            $userTable->update(['balance' => $new_balance]);
            $userProfileTable->update(['wallet_amount' => $new_wallet]);


            $returned_data['status'] = 'success';
            $returned_data['message'] = "Balance has been successfully added.";
        } else {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "User not found.";
        }

        return ResponseWrapper::End($returned_data);
    }

    public function ispBroadbandUserDisable($client_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);
        // Fetch branch information
        $branchInfo = CorporateClient::where('uid', '=', $client_id)->get();

        // Check if branch information exists
        if (!$branchInfo) {
            $returned_data['results']['message'] = 'Zone information not found';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        // Api Variables
		$ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
		$mkUser = $branchInfo->implode('mikrotik_username', ', ');
		$mkPass = $branchInfo->implode('mikrotik_password', ', ');
		$API = new RouterOsApi();

        // Connect to MikroTik Router
        if ($API->connect($ipAddr, $mkUser, $mkPass)) {

            // Get current month and year
            $currentMonth = date('n');
            $currentYear = now()->format('Y');

            // Fetch usernames that need to be disabled
            $usernames = BroadbandDbSecret::where('client_id', $client_id)
            // ->where('status', 0)
            ->whereNotIn('username', function ($query) use ($currentMonth, $currentYear) { // if paid for currect month
                $query->select('username')->from('payment')->where('month', $currentMonth)->whereYear('payment_date', $currentYear);
            })
            ->pluck('username')
            ->toArray();

            // Log::info($usernames);
            // dd('hello');
            foreach ($usernames as $username) {
                if ($API->connect($ipAddr, $mkUser, $mkPass)) {
                    $arrID = $API->comm("/ppp/secret/print", [
                        ".proplist" => ".id",
                        "?name" => $username,
                    ]);

                    // Check if $arrID has any result
                    if (!empty($arrID)) {
                        $API->comm("/ppp/secret/disable", [
                            ".id" => $arrID[0][".id"]
                        ]);
                    }

                    $id = $API->comm("/ppp/active/getall", [
                        ".proplist" => ".id",
                        "?name" => $username,
                    ]);

                    // Check if $id has any result
                    if (!empty($id)) {
                        $API->comm("/ppp/active/remove", [
                            ".id" => $id[0][".id"]
                        ]);
                    }

                    $API->disconnect();
                }

                // Update the status and dateOf_Inactive in the BroadbandDbSecret table
                BroadbandDbSecret::where('username', $username)->update(['status' => 1, 'dateOf_Inactive' => now()]);
                InternetUsers::where('uid', User::where('auth_id',$username)->value('id'))->where('zone_id',$client_id)->update([
                    'connection_status' => 'inactive',
                    'updated_at' => Carbon::now(),
                ]);
            }

            // Update the status and dateOf_Inactive in the BroadbandDbSecret table
            CorporateClient::where('uid', $client_id)->update(['current_month_user_disable_status' => 0]);

            // Prepare the response data
            $returned_data['results']['success'] = true;

            // Return the response
            return ResponseWrapper::End($returned_data);
        } else {
            $returned_data['results']['message'] = 'Failed to connect to MikroTik Router';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
    }

    public function ispBroadbandUserEnable($client_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Fetch branch information and Check if branch information exists
        $branchInfo = CorporateClient::query()->select('mikrotik_ip', 'mikrotik_username', 'mikrotik_password')->where('uid', '=', $client_id)->first();
        if (!$branchInfo) {
            $returned_data['results']['message'] = 'Zone information not found';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        // Initialize RouterOS API and Connect to MikroTik Router
        $API = new RouterosAPI();
        if (!$API->connect($branchInfo->mikrotik_ip, $branchInfo->mikrotik_username, $branchInfo->mikrotik_password)) {
            $returned_data['results']['message'] = 'Failed to connect to MikroTik Router';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        $currentMonth = now()->format('n');
        $previousMonth = $currentMonth - 1;
        $currentYear = now()->format('Y');

        // If current month is January, set previous month to December and adjust year accordingly
        if ($currentMonth === '1') {
            $previousMonth = '12';
            $currentYear = now()->subYear()->format('Y');
        }

        // Fetch usernames that need to be enabled
        $usernames = BroadbandDbPayment::select('payment.username')
            ->join('secret', 'payment.username', '=', 'secret.username')
            ->where('secret.zone', $client_id)
            ->where('secret.status', 1)
            ->where('payment.month', $previousMonth)
            ->whereYear('payment.payment_date', $currentYear)
            ->whereIn('payment.payment_date', function ($query) {
                $query->selectRaw('MAX(payment_date)')
                    ->from('payment')
                    ->groupBy('username');
            })
            ->pluck('username')
            ->toArray();

        //Log::info($previousMonth);
        // Iterate over each username
        foreach ($usernames as $username) {
            $arrID=$API->comm("/ppp/secret/print",array(".proplist"=> ".id","?name" => $username));
                    $API->comm("/ppp/secret/enable",array(".id" => $arrID[0][".id"]));

            // $arrID=$API->comm("/ppp/secret/print", array(".proplist"=> ".id", "?name" => $username));
            // $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));
            $API->disconnect();

            // Update the status and dateOf_Inactive in the BroadbandDbSecret table
            BroadbandDbSecret::where('username', $username)->update(['status' => 0, 'dateOf_Inactive' => now()->subMonth()]);
            InternetUsers::where('uid', User::where('auth_id',$username)->value('id'))->where('zone_id',$client_id)->update([
                'connection_status' => 'active',
                'updated_at' => Carbon::now(),
            ]);
        }

        // Update the status and dateOf_Inactive in the BroadbandDbSecret table
        CorporateClient::where('uid', $client_id)->update(['current_month_user_disable_status' => 1]);

        // Disconnect from the MikroTik Router
        $API->disconnect();

        // Prepare the response data
        $returned_data['results']['success'] = true;

        // Return the response
        return ResponseWrapper::End($returned_data);
    }

    public function ispSingleUserEnable($client_id, $username) : JsonResponse
	{
        $returned_data = ResponseWrapper::Start();

        // Fetch branch information and Check if branch information exists
        $branchInfo = CorporateClient::where('uid', '=', $client_id)->get();
        if (!$branchInfo) {
            $returned_data['results']['message'] = 'Zone information not found';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        // Api Variables
		$ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
		$mkUser = $branchInfo->implode('mikrotik_username', ', ');
		$mkPass = $branchInfo->implode('mikrotik_password', ', ');
		$API = new RouterOsApi();
		if ($API->connect($ipAddr, $mkUser, $mkPass)) {

            // enable user in mikrotik
            $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id","?name" => $username));
            if (empty($arrID)) {
                $API->disconnect();
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No matching record found on the MikroTik Router!';
                return ResponseWrapper::End($returned_data);
            }
            $API->comm("/ppp/secret/enable", array(".id" => $arrID[0][".id"]));

            // change status in secret table
            BroadbandDbSecret::where('username', $username)->where('client_id',$client_id)->update([
                'status' => 0,
                'updated_at' => Carbon::now(),
            ]);

            // change status in internet user table
			InternetUsers::where('uid', User::where('auth_id',$username)->value('id'))->where('zone_id',$client_id)->update([
                'connection_status' => 'active',
                'updated_at' => Carbon::now(),
            ]);

            $returned_data['results']['message'] = 'User enabled successfully!';
            $returned_data['results']['success'] = true;
            return ResponseWrapper::End($returned_data);
		}else{
            $returned_data['results']['message'] = 'Your network zone is down!';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
	}

	public function ispSingleUserDisable($client_id, $username) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Fetch branch information and Check if branch information exists
        $branchInfo = CorporateClient::where('uid', '=', $client_id)->get();
        if (!$branchInfo) {
            $returned_data['results']['message'] = 'Zone information not found';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }

        // Api Variables
		$ipAddr = $branchInfo->implode('mikrotik_ip', ', ');
		$mkUser = $branchInfo->implode('mikrotik_username', ', ');
		$mkPass = $branchInfo->implode('mikrotik_password', ', ');
		$API = new RouterOsApi();

		if ($API->connect($ipAddr, $mkUser, $mkPass)) {
            // print all user here to disable---
            $arrID = $API->comm("/ppp/secret/print", array(".proplist"=> ".id","?name" => $username));
            if (empty($arrID)) {
                $API->disconnect();
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'No matching record found on the MikroTik Router!';
                return ResponseWrapper::End($returned_data);
            }
            $API->comm("/ppp/secret/disable", array(".id" => $arrID[0][".id"]));

            // Remove all user from mikrotik
            $id = $API->comm("/ppp/active/getall", array(".proplist"=> ".id", "?name" => $username));
            if (empty($id)) {
                $API->disconnect();
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'ID removed from secret but Not removed from active list or from Mikrotik Router.';
                return ResponseWrapper::End($returned_data);
            }
            $API->comm("/ppp/active/remove",array(".id" => $id[0][".id"]));

            // Change status in Secret
            BroadbandDbSecret::where('username', $username)->where('client_id',$client_id)->update([
                'status' => 1,
                'updated_at' => Carbon::now(),
            ]);

            // change status in internet user table
            InternetUsers::where('uid', User::where('auth_id',$username)->value('id'))->where('zone_id',$client_id)->update([
                'connection_status' => 'inactive',
                'updated_at' => Carbon::now(),
            ]);

            $returned_data['results']['message'] = 'User disabled successfully!';
            $returned_data['results']['success'] = true;
            return ResponseWrapper::End($returned_data);
		}else{
            $returned_data['results']['message'] = 'Your network zone is down!';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
	}
}
