<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function getTransactionList(Request $request, $auth_uid) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($auth_uid);
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $returned_data['results']['total'] = 0;

        Log::info($uid);
        $query = Transaction::query();
        $query->leftJoin('user_profiles as sp', 'sp.uid', '=', 'transactions.sender_uid');
        $query->leftJoin('user_profiles as rp', 'rp.uid', '=', 'transactions.receiver_uid');
        $query->where('transactions.sender_uid', '=', $uid)->orWhere('transactions.receiver_uid', '=', $uid);
        $query->orderBy('transactions.created_at', 'DESC');
        $query->skip($totalSkip)->take(30);
        $returned_data['results']['list'] = $query->get(['sp.full_name as sender_name','rp.full_name as receiver_name','transactions.*']);

        return ResponseWrapper::End($returned_data);
    }
}
