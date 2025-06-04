<?php

namespace App\Http\Controllers\graph;

use App\Http\Controllers\Controller;
use App\Classes\ResponseWrapper;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\InternetUsers;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GraphWifiUserController extends Controller
{
    /**
     * Zone & month Wise Bar Chart For Total Wifi User
     */
    public function wifiUserBarChart($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Determine the user's role and corresponding ID
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        // Calculate the date six months ago
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        // Build the condition dynamically
        $roleCondition = function($query) use ($client, $agent, $sub_agent, $user_id) {
            if ($client) {
                $query->where('iu.zone_id', $user_id);
            } elseif ($agent) {
                $query->where('iu.agent_id', $user_id);
            } elseif ($sub_agent) {
                $query->where('iu.sub_agent_id', $user_id);
            }
        };

        // Query to count total paid users per month
        $userCounts = DB::table('payments')
            ->selectRaw("
                MONTH(payments.created_at) AS month,
                YEAR(payments.created_at) AS year,
                COUNT(DISTINCT payments.uid) AS total_paid_users
            ")
            ->join('internet_users as iu', 'payments.uid', '=', 'iu.uid')
            ->where('iu.package_type', 'wifi')
            ->where('payments.transaction_status' , 'Completed')
            ->where('payments.created_at', '>=', $sixMonthsAgo)
            ->where($roleCondition)
            ->groupBy(DB::raw('YEAR(payments.created_at), MONTH(payments.created_at)'));

        // Query to count users who registered and paid within the same month
        $registeredAndPaidUsers = DB::table('internet_users as iu')
            ->selectRaw("
                MONTH(iu.created_at) AS month,
                YEAR(iu.created_at) AS year,
                COUNT(DISTINCT iu.uid) AS total_register_and_paid_for_this_month
            ")
            ->join('payments as p', 'iu.uid', '=', 'p.uid')
            ->where('iu.package_type', 'wifi')
            ->where('p.transaction_status' , 'Completed')
            ->whereRaw('MONTH(iu.created_at) = MONTH(p.created_at)')
            ->whereRaw('YEAR(iu.created_at) = YEAR(p.created_at)')
            ->where('p.created_at', '>=', $sixMonthsAgo)
            ->where($roleCondition)
            ->groupBy(DB::raw('YEAR(iu.created_at), MONTH(iu.created_at)'));

        // Combine both queries to get the final results
        $data = [];
        for ($i = 0; $i < 6; $i++) {
            $date = now()->subMonths($i)->startOfMonth();
            $year = $date->year;
            $month = str_pad($date->month, 2, '0', STR_PAD_LEFT);

            // Get total paid users for the month
            $paidUsers = $userCounts
                ->clone()
                ->whereMonth('payments.created_at', $date->month)
                ->whereYear('payments.created_at', $date->year)
                ->first();

            // Get users who registered and paid within the same month
            $registeredAndPaid = $registeredAndPaidUsers
                ->clone()
                ->whereMonth('iu.created_at', $date->month)
                ->whereYear('iu.created_at', $date->year)
                ->first();

            $data[] = [
                'year' => $year,
                'month' => $month,
                'total_paid_users' => $paidUsers ? $paidUsers->total_paid_users : 0,
                'total_register_and_paid_for_this_month' => $registeredAndPaid ? $registeredAndPaid->total_register_and_paid_for_this_month : 0,
            ];
        }

        // Reverse the data to have the most recent month first
        $data = array_reverse($data);

        $returned_data['results']['total'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Zone & month Wise Bar Chart For Total Payment of Wifi User
     */
    public function wifiTotalPaymentBarChart($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Determine the user's role and corresponding ID
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        // Calculate the date six months ago
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $currentMonth = now()->startOfMonth();

        // Generate a series of months from six months ago to the current month
        $months = [];
        for ($date = $sixMonthsAgo->copy(); $date <= $currentMonth; $date->addMonth()) {
            $months[] = $date->format('Y-m');
        }

        // Build the query to get payment totals grouped by year and month
        $userCountsQuery = Payment::selectRaw(
                'MONTH(payments.created_at) AS month,
                YEAR(payments.created_at) AS year,
                CAST(SUM(payments.amount) AS UNSIGNED) AS total'
            )
            ->join('internet_users', 'payments.uid', '=', 'internet_users.uid');

        if ($client) {
            $userCountsQuery->where('internet_users.zone_id', $user_id)->where('package_type','wifi');
        } elseif ($agent) {
            $userCountsQuery->where('internet_users.agent_id', $user_id)->where('package_type','wifi');
        } elseif ($sub_agent) {
            $userCountsQuery->where('internet_users.sub_agent_id', $user_id)->where('package_type','wifi');
        }

        $userCounts = $userCountsQuery->where('payments.transaction_status' , 'Completed')
            ->where('payments.created_at', '>=', $sixMonthsAgo)
            ->groupBy(DB::raw('YEAR(payments.created_at), MONTH(payments.created_at)'))
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        // Fill in missing months with zero counts
        $data = [];
        foreach ($months as $month) {
            $data[] = [
                'year' => substr($month, 0, 4),
                'month' => substr($month, 5, 2),
                'total' => $userCounts->get($month)->total ?? 0,
            ];
        }

        $returned_data['results']['total'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Month Wise Line For Total Payment of Wifi User
     */
    public function wifiTotalPaymentMonthWiseLineChart($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Determine the user's role and corresponding ID
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        // Calculate the date six months ago
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $currentMonth = now()->startOfMonth();

        // Generate a series of months from six months ago to the current month
        $months = [];
        for ($date = $sixMonthsAgo->copy(); $date <= $currentMonth; $date->addMonth()) {
            $months[] = $date->format('Y-m');
        }

        // Build the query to get payment totals grouped by year and month
        $userCountsQuery = Payment::selectRaw(
                'MONTH(payments.created_at) AS month,
                YEAR(payments.created_at) AS year,
                CAST(SUM(payments.amount) AS UNSIGNED) AS total'
            )
            ->join('internet_users', 'payments.uid', '=', 'internet_users.uid');

        if ($client) {
            $userCountsQuery->where('internet_users.zone_id', $user_id)->where('package_type','wifi');
        } elseif ($agent) {
            $userCountsQuery->where('internet_users.agent_id', $user_id)->where('package_type','wifi');
        } elseif ($sub_agent) {
            $userCountsQuery->where('internet_users.sub_agent_id', $user_id)->where('package_type','wifi');
        }

        $userCounts = $userCountsQuery->where('payments.transaction_status' , 'Completed')
            ->where('payments.created_at', '>=', $sixMonthsAgo)
            ->groupBy(DB::raw('YEAR(payments.created_at), MONTH(payments.created_at)'))
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        // Fill in missing months with zero counts
        $data = [];
        foreach ($months as $month) {
            $data[] = [
                'year' => substr($month, 0, 4),
                'month' => substr($month, 5, 2),
                'total' => $userCounts->get($month)->total ?? 0,
            ];
        }

        $returned_data['results']['total'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Zone Wise Total Wifi User
     */
    public function wifiTotalUser($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        set_time_limit(0);

        $userCounts = InternetUsers::selectRaw('COUNT(internet_users.uid) AS total')
        ->where(function($query) use ($user_id) {
            $query->where(function($query) use ($user_id) {
                $client = CorporateClient::where('uid', $user_id)->exists();
                if ($client) {
                    $query->where('internet_users.zone_id', $user_id)->where('package_type','wifi');
                } else {
                    $agent = CorporateAgent::where('uid', $user_id)->exists();
                    if ($agent) {
                        $query->where('internet_users.agent_id', $user_id)->where('package_type','wifi');
                    } else {
                        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();
                        if ($sub_agent) {
                            $query->where('internet_users.sub_agent_id', $user_id)->where('package_type','wifi');
                        }
                    }
                }
            });
        });

        $returned_data['results']['total'] = $userCounts->get();
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Zone Wise Total Wifi Payment
     */
    public function wifiTotalPayment($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        set_time_limit(0);

        // Determine the user's role and corresponding ID
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        $userCounts = Payment::selectRaw(
                'CAST(SUM(payments.amount) AS UNSIGNED) AS total'
            )
            ->join('internet_users', 'payments.uid', '=', 'internet_users.uid')
            ->where('payments.transaction_status' , 'Completed');

        if ($client) {
            $userCounts->where('internet_users.zone_id', $user_id)->where('package_type','wifi');
        } elseif ($agent) {
            $userCounts->where('internet_users.agent_id', $user_id)->where('package_type','wifi');
        } elseif($sub_agent) {
            $userCounts->where('internet_users.sub_agent_id', $user_id)->where('package_type','wifi');
        }

        $returned_data['results']['total'] = $userCounts->get();
        return ResponseWrapper::End($returned_data);
    }
}

