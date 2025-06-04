<?php

namespace App\Http\Controllers\graph;

use App\Http\Controllers\Controller;
use App\Classes\ResponseWrapper;
use App\Models\CorporateAgent;
use App\Models\CorporateClient;
use App\Models\CorporateSubAgent;
use App\Models\InternetUsers;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GraphInternetUserController extends Controller
{
    /**
     * Internet User Barchart
     */
    public function barchart() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Initialize an array to hold all months for the last 6 months, including the current month
        $resultsByMonthAndStatus = [];

        // Calculate the date six months ago
        $sixMonthsAgo = now()->subMonths(5); // Adjusted to cover the current month as well

        // Get the total count of records according to month and connection status for the previous 6 months
        $totalByMonthAndStatus = InternetUsers::selectRaw('YEAR(created_at) AS year, MONTH(created_at) AS month, connection_status, COUNT(*) AS total')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('year', 'month', 'connection_status')
            ->orderBy('created_at', 'asc')
            ->get();

        // Loop through the totalByMonthAndStatus results and populate the resultsByMonthAndStatus array
        foreach ($totalByMonthAndStatus as $record) {
            $year = $record->year;
            $month = $record->month;
            $status = $record->connection_status;
            $total = $record->total;

            if (!isset($resultsByMonthAndStatus[$month])) {
                $resultsByMonthAndStatus[$month] = [];
            }
            $resultsByMonthAndStatus[$month][$status] = $total;

            // Calculate total count for the month
            if (!isset($resultsByMonthAndStatus[$month]['total'])) {
                $resultsByMonthAndStatus[$month]['total'] = 0;
            }
            $resultsByMonthAndStatus[$month]['total'] += $total;

            if (!isset($resultsByMonthAndStatus[$month]['month'])) {
                $resultsByMonthAndStatus[$month]['month'] = $month;
            }

            if (!isset($resultsByMonthAndStatus[$month]['year'])) {
                $resultsByMonthAndStatus[$month]['year'] = $year;
            }
        }

        // Fill in missing months with 0 counts
        for ($i = 0; $i < 6; $i++) {
            $year = date('Y', strtotime("-$i months"));
            $month = date('n', strtotime("-$i months"));

            if (!isset($resultsByMonthAndStatus[$month])) {
                $resultsByMonthAndStatus[$month] = [
                    'total' => 0,
                    'pending' => 0,
                    'active' => 0,
                    'year' => (int)$year,
                    'month' => (int)$month
                    // Add more status types if needed
                ];
            }
        }

        // Sort the array by year and month
        ksort($resultsByMonthAndStatus);

        // Assign the formatted results to the returned data
        $returned_data['results']['total_by_month_and_status'] = $resultsByMonthAndStatus;

        // Optionally, you can get the total count of all records regardless of month and status
        $returned_data['results']['total'] = InternetUsers::count();

        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User line chart
     */
    public function broadbandUserLineChart($user_id, $days, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Calculate the date x days ago
        $startDate = now()->subDays($days)->startOfDay();

        // user's role
        $admin = User::where('id', $user_id)->value('base_role') === 'admin';
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        $query = InternetUsers::selectRaw(
            "DAY(created_at) AS day,
            MONTH(created_at) AS month,
            YEAR(created_at) AS year,
            COUNT(uid) AS total"
        )->where('package_type', $type)->where('created_at', '>=', $startDate);

        if (!$admin) {
            if ($client) {
                $query->where('zone_id', $user_id);
            } elseif ($agent) {
                $query->where('agent_id', $user_id);
            } elseif ($sub_agent) {
                $query->where('sub_agent_id', $user_id);
            }
        }

        $results = $query->groupBy(DB::raw('YEAR(created_at), MONTH(created_at), DAY(created_at)'))
            ->orderBy('created_at', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . $item->month . '-' . $item->day;
            });

        // Fill in missing days with zero counts
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;
            $key = $year . '-' . $month . '-' . $day;

            $data[] = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'total' => $results->get($key)->total ?? 0
            ];
        }

        // Reverse the data to have the most recent day first
        $data = array_reverse($data);

        $returned_data['results']['total'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User line chart
     */
    public function internetUserLineChart($days, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Calculate the date x days ago
        $startDate = now()->subDays($days)->startOfDay();

        $query = InternetUsers::selectRaw(
            "DAY(created_at) AS day,
            MONTH(created_at) AS month,
            YEAR(created_at) AS year,
            COUNT(uid) AS total,
            COUNT(CASE WHEN connection_status = 'active' THEN 1 END) AS total_active,
            COUNT(CASE WHEN connection_status = 'pending' THEN 1 END) AS total_pending"
        )->where('created_at', '>=', $startDate);

        if($type !== 'all'){
            $query->where('package_type', $type);
        }

        $results = $query->groupBy(DB::raw('YEAR(created_at), MONTH(created_at), DAY(created_at)'))
            ->orderBy('created_at', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . $item->month . '-' . $item->day;
            });

        // Fill in missing days with zero counts
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;
            $key = $year . '-' . $month . '-' . $day;

            $data[] = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'total' => $results->get($key)->total ?? 0,
                'total_active' => $results->get($key)->total_active ?? 0,
                'total_pending' => $results->get($key)->total_pending ?? 0
            ];
        }

        // Reverse the data to have the most recent day first
        $data = array_reverse($data);

        $returned_data['results']['total'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Active Internet User line chart
     */
    public function activeInternetUserLineChart($days, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Calculate the date x days ago
        $startDate = now()->subDays($days)->startOfDay();

        $query = InternetUsers::selectRaw(
            "DAY(created_at) AS day,
            MONTH(created_at) AS month,
            YEAR(created_at) AS year,
            COUNT(CASE WHEN connection_status = 'active' THEN 1 END) AS total_active"
        )->where('created_at', '>=', $startDate);

        if($type !== 'all'){
            $query->where('package_type', $type);
        }

        $results = $query->groupBy(DB::raw('YEAR(created_at), MONTH(created_at), DAY(created_at)'))
            ->orderBy('created_at', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . $item->month . '-' . $item->day;
            });

        // Fill in missing days with zero counts
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;
            $key = $year . '-' . $month . '-' . $day;

            $data[] = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'total' => $results->get($key)->total ?? 0,
                'total_active' => $results->get($key)->total_active ?? 0,
                'total_pending' => $results->get($key)->total_pending ?? 0
            ];
        }

        // Reverse the data to have the most recent day first
        $data = array_reverse($data);

        $returned_data['results']['total'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Active Internet User line chart
     */
    public function activeInternetUserMonthlyLineChart() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Initialize an array to hold all months for the last 6 months, including the current month
        $resultsByMonthAndStatus = [];

        // Calculate the date six months ago
        $sixMonthsAgo = now()->subMonths(5); // Adjusted to cover the current month as well

        // Get the total count of records according to month and connection status for the previous 6 months
        $totalByMonthAndStatus = InternetUsers::selectRaw('YEAR(created_at) AS year, MONTH(created_at) AS month, connection_status, COUNT(*) AS total')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('year', 'month', 'connection_status')
            ->orderBy('created_at', 'asc')
            ->get();

        // Loop through the totalByMonthAndStatus results and populate the resultsByMonthAndStatus array
        foreach ($totalByMonthAndStatus as $record) {
            $year = $record->year;
            $month = $record->month;
            $status = $record->connection_status;
            $total = $record->total;

            if (!isset($resultsByMonthAndStatus[$month])) {
                $resultsByMonthAndStatus[$month] = [];
            }
            $resultsByMonthAndStatus[$month][$status] = $total;

            // Calculate total count for the month
            if (!isset($resultsByMonthAndStatus[$month]['total'])) {
                $resultsByMonthAndStatus[$month]['total'] = 0;
            }
            $resultsByMonthAndStatus[$month]['total'] += $total;

            if (!isset($resultsByMonthAndStatus[$month]['month'])) {
                $resultsByMonthAndStatus[$month]['month'] = $month;
            }

            if (!isset($resultsByMonthAndStatus[$month]['year'])) {
                $resultsByMonthAndStatus[$month]['year'] = $year;
            }
        }

        // Fill in missing months with 0 counts
        for ($i = 0; $i < 6; $i++) {
            $year = date('Y', strtotime("-$i months"));
            $month = date('n', strtotime("-$i months"));

            if (!isset($resultsByMonthAndStatus[$month])) {
                $resultsByMonthAndStatus[$month] = [
                    'total' => 0,
                    'pending' => 0,
                    'active' => 0,
                    'year' => (int)$year,
                    'month' => (int)$month
                    // Add more status types if needed
                ];
            }
        }

        // Sort the array by year and month
        ksort($resultsByMonthAndStatus);

        // Assign the formatted results to the returned data
        $returned_data['results']['total_by_month_and_status'] = $resultsByMonthAndStatus;

        // Optionally, you can get the total count of all records regardless of month and status
        $returned_data['results']['total'] = InternetUsers::count();

        return ResponseWrapper::End($returned_data);

    }

    public function grossTotalPayment($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        set_time_limit(0);

        // Determine the user's role and corresponding ID
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        $grossTotalPayment = Payment::selectRaw('CAST(SUM(payments.amount) AS UNSIGNED) AS total')
        ->join('internet_users', 'payments.uid', '=', 'internet_users.uid')
        ->where('payments.transaction_status' , 'Completed');

        if ($client) {
            $grossTotalPayment->where('internet_users.zone_id', $user_id);
        } elseif ($agent) {
            $grossTotalPayment->where('internet_users.agent_id', $user_id);
        } elseif($sub_agent) {
            $grossTotalPayment->where('internet_users.sub_agent_id', $user_id);
        }

        $returned_data['results']['total'] = $grossTotalPayment->get();
        return ResponseWrapper::End($returned_data);
    }

    public function totalPaymentCurrentMonth($user_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        set_time_limit(0);

        // Determine the user's role and corresponding ID
        $client = CorporateClient::where('uid', $user_id)->exists();
        $agent = CorporateAgent::where('uid', $user_id)->exists();
        $sub_agent = CorporateSubAgent::where('uid', $user_id)->exists();

        $currentMonthPayment = Payment::selectRaw('CAST(SUM(payments.amount) AS UNSIGNED) AS total')
        ->join('internet_users', 'payments.uid', '=', 'internet_users.uid')
        ->where('payments.transaction_status' , 'Completed')
        ->whereMonth('payments.created_at', date('m'))
        ->whereYear('payments.created_at', date('Y'));

        if ($client) {
            $currentMonthPayment->where('internet_users.zone_id', $user_id);
        } elseif ($agent) {
            $currentMonthPayment->where('internet_users.agent_id', $user_id);
        } elseif($sub_agent) {
            $currentMonthPayment->where('internet_users.sub_agent_id', $user_id);
        }

        $returned_data['results']['total'] = $currentMonthPayment->get();
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User Day Wise Summary
     */
    public function internetUserDayWiseSummary($zone_id, $days): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        $base_role = User::where('id', $zone_id)->value('base_role');

        // Calculate date range
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        // Fetching user count per day
        $query = Payment::query();
        if($zone_id !== "null"){
            $query = $query->where('payments.zone_id', $zone_id);
        }
        // $query->leftJoin('internet_users', 'internet_users.uid', 'payments.uid');
        $query->selectRaw("
                DATE(payments.created_at) as date,
                COUNT(DISTINCT payments.uid) as total
            ")
            ->where('payments.transaction_status', 'Completed')
            ->whereNotIn('payments.package', ['broadband', 'wifi'])
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        // Apply package type filter if provided
        // if (!empty($type) && $type !== 'null') {
        //     $query->where('internet_users.package_type', $type);
        // }

        // Fetch data and format properly
        $results = $query->get()->keyBy('date');

        // Generate complete date-wise response
        $formattedResults = [];
        for ($i = 0; $i <= $days; $i++) {
            $date = now()->subDays($days - $i)->startOfDay();
            $dateString = $date->toDateString();

            $formattedResults[] = [
                "year" => $date->year,
                "month" => $date->month,
                "day" => $date->day,
                "total" => $results[$dateString]->total ?? 0, // Default to 0 if no data for the date
            ];
        }

        // Add results to response
        $returned_data['results']['total'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User Month Wise Summary
     */
    public function internetUserMonthWiseSummary($zone_id, $months): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        $base_role = User::where('id', $zone_id)->value('base_role');

        // Calculate date range
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        // Fetching user count per month
        $query = Payment::query();
        if($zone_id !== "null"){
            $query = $query->where('payments.zone_id', $zone_id);
        }
        $query->selectRaw("
            YEAR(payments.created_at) as year,
            MONTH(payments.created_at) as month,
            COUNT(DISTINCT payments.uid) as total
        ")
        ->where('payments.transaction_status', 'Completed')
        ->whereNotIn('payments.package', ['broadband', 'wifi'])
        // ->whereMonth('payments.created_at', $startDate);
        ->whereBetween('payments.created_at', [$startDate, $endDate]);

        // $query->leftJoin('internet_users', 'internet_users.uid', 'payments.uid');
        $query->groupBy('year', 'month')
        ->orderBy('year', 'ASC')
        ->orderBy('month', 'ASC');

        // Fetch data and format properly
        $results = $query->get()->keyBy(function ($item) {
            return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
        });

        // Generate complete month-wise response
        $formattedResults = [];
        for ($i = 0; $i <= $months; $i++) {
            $date = now()->subMonths($months - $i)->startOfMonth();
            $key = $date->year . '-' . str_pad($date->month, 2, '0', STR_PAD_LEFT);

            $formattedResults[] = [
                "year" => $date->year,
                "month" => $date->month,
                "total" => $results[$key]->total ?? 0,
            ];
        }

        // Add results to response
        $returned_data['results']['total'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User Payments Day Wise Summary
     */
    public function internetUserPaymentsDayWiseSummary($zone_id, $days): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        $base_role = User::where('id', $zone_id)->value('base_role');

        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $query = Payment::query();
        if($zone_id !== "null"){
            $query = $query->where('payments.zone_id', $zone_id);
        }
        $query->selectRaw('
                DATE(payments.created_at) as date,
                CAST(SUM(payments.amount) AS UNSIGNED) AS total
            ')
        // ->join('internet_users', 'payments.uid', '=', 'internet_users.uid')
        ->where('payments.transaction_status', 'Completed')
        ->whereNotIn('payments.package', ['broadband', 'wifi'])
        ->whereBetween('payments.created_at', [$startDate, $endDate]);

        // if (!empty($type) && $type !== 'null') {
        //     $query->where('internet_users.package_type', $type);
        // }

        $results = $query->groupBy('date')->orderBy('date', 'ASC')->get()->keyBy('date');

        // Generating complete date-wise response
        $formattedResults = collect(range(0, $days))->map(function ($i) use ($results, $days) {
            $date = now()->subDays($days - $i)->toDateString();
            return [
                "year" => now()->subDays($days - $i)->year,
                "month" => now()->subDays($days - $i)->month,
                "day" => now()->subDays($days - $i)->day,
                "total" => $results[$date]->total ?? 0,
            ];
        })->toArray();

        $returned_data['results']['total'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User Received Payments Month Wise Summary
     */
    public function internetUserPaymentsMonthWiseSummary($zone_id, $months): JsonResponse {
        $returned_data = ResponseWrapper::Start();

        set_time_limit(0);

        $base_role = User::where('id', $zone_id)->value('base_role');

        // calculate date
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        // Fetching user payments per month
        $query = Payment::query();
        if($zone_id !== "null"){
            $query = $query->where('payments.zone_id', $zone_id);
        }
        $query->selectRaw('
                YEAR(payments.created_at) as year,
                MONTH(payments.created_at) as month,
                CAST(SUM(payments.amount) AS UNSIGNED) AS total
            ')
            // ->join('internet_users', 'payments.uid', '=', 'internet_users.uid')
            ->where('payments.transaction_status', 'Completed')
            ->whereNotIn('payments.package', ['broadband', 'wifi'])
            // ->whereMonth('payments.created_at', $startDate)
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->groupBy('year', 'month')
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC');


        // if (!empty($type) && $type !== 'null') {
        //     $query->where('internet_users.package_type', $type);
        // }

        $results = $query->get()->keyBy(function ($item) {
            return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
        });

        // Generating complete month-wise response
        $formattedResults = collect(range(0, $months))->map(function ($i) use ($results, $months) {
            $date = now()->subMonths($months - $i)->startOfMonth();
            $key = $date->year . '-' . str_pad($date->month, 2, '0', STR_PAD_LEFT);
            return [
                "year" => $date->year,
                "month" => $date->month,
                "total" => $results[$key]->total ?? 0,
            ];
        })->toArray();

        $returned_data['results']['total'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User Receivable Payments Month Wise Summary
     */
    public function internetUserReceivablePaymentsMonthWiseSummary($zone_id, $months): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        $base_role = User::where('id', $zone_id)->value('base_role');

        // Loop through each month and calculate receivable amount
        $formattedResults = [];

        for ($i = 0; $i <= $months; $i++) {
            $currentMonth = now()->subMonths($months - $i)->startOfMonth();
            $previousMonth = $currentMonth->copy()->subMonth()->startOfMonth();

            // Get users who paid in the previous month
            $previousMonthUsers = Payment::query();
            if($zone_id !== "null"){
                $previousMonthUsers = $previousMonthUsers->where('payments.zone_id', $zone_id);
            }
            $previousMonthUsers->select('payments.uid', 'internet_package_corporates.price')
                ->where('payments.transaction_status', 'Completed')
                ->whereMonth('payments.created_at', $previousMonth)
                ->whereNotIn('payments.package', ['broadband','wifi'])
                ->join('internet_package_corporates', 'payments.package', '=', 'internet_package_corporates.package_name');

            // Get users who are paying in the current month (new users)
            $currentMonthUsers = Payment::query();
            if($zone_id !== "null"){
                $currentMonthUsers = $currentMonthUsers->where('payments.zone_id', $zone_id);
            }
            $currentMonthUsers->select('payments.uid', 'payments.amount')
                ->where('payments.transaction_status', 'Completed')
                ->whereMonth('payments.created_at', $currentMonth->month)
                ->whereYear('payments.created_at', $currentMonth->year)
                ->whereNotIn('payments.package', ['broadband','wifi'])
                // ->join('internet_users', 'payments.uid', '=', 'internet_users.uid')
                ->whereNotExists(function($query) use ($currentMonth) {
                    $query->select(DB::raw(1))
                          ->from('payments as p')
                          ->whereRaw('p.uid = payments.uid')
                          ->whereNotIn('p.package', ['broadband', 'wifi'])
                          ->where('p.transaction_status', 'Completed')
                          ->whereMonth('p.created_at', '<=', $currentMonth->month)
                          ->whereYear('p.created_at', '<=', $currentMonth->year);
                });

            // if (!empty($type) && $type !== 'null') {
            //     $previousMonthUsers->where('internet_users.package_type', $type);
            //     $currentMonthUsers->where('internet_users.package_type', $type);
            // }

            // Calculate sum for each category
            $previousMonthReceivable = $previousMonthUsers->sum('internet_package_corporates.price');
            $currentMonthReceivable = $currentMonthUsers->sum('amount');
            $totalReceivable = $previousMonthReceivable + $currentMonthReceivable;

            // Get total users count (including previous and current month)
            $totalUsers = $previousMonthUsers->count() + $currentMonthUsers->count();

            $formattedResults[] = [
                "year" => $currentMonth->year,
                "month" => $currentMonth->month,
                "total_receivable" => round($totalReceivable),
                "total_payable_users" => $totalUsers
            ];
        }

        $returned_data['results']['total'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User Monthly Record List With Date Filter
     */
    public function internetUserMonthlyRecordList($zone_id, $from, $to): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        $base_role = User::where('id', $zone_id)->value('base_role');

        // Convert `from` and `to` into Carbon instances
        $startDate = Carbon::createFromFormat('d-m-y', $from)->startOfMonth();
        $endDate = Carbon::createFromFormat('d-m-y', $to)->endOfMonth();

        // Initialize results array
        $formattedResults = [];

        // Loop through each month within the range
        while ($startDate->lte($endDate)) {
            $currentMonth = $startDate->copy();
            $previousMonth = $currentMonth->copy()->subMonth()->startOfMonth();

            // current month paid users
            $currentMonthPayments = Payment::query();
            if($zone_id !== "null"){
                $currentMonthPayments = $currentMonthPayments->where('payments.zone_id', $zone_id);
            }
            $currentMonthPayments->select('payments.uid', 'payments.amount')
            ->where('payments.transaction_status', 'Completed')
            ->whereNotIn('payments.package', ['broadband','wifi'])
            ->whereMonth('payments.created_at', $currentMonth);

            // current month firstly paid users
            $currentMonthFirstlyPayments = Payment::query();
            if ($zone_id !== "null") {
                $currentMonthFirstlyPayments = $currentMonthFirstlyPayments->where('payments.zone_id', $zone_id);
            }
            $currentMonthFirstlyPayments->select('payments.uid', 'payments.amount')
            ->where('payments.transaction_status', 'Completed')
            ->whereNotIn('payments.package', ['broadband', 'wifi'])
            ->whereMonth('payments.created_at', '=', $currentMonth->month)
            ->whereYear('payments.created_at', '=', $currentMonth->year)
            ->whereNotExists(function ($query) use ($currentMonth) {
                $query->select(DB::raw(1))
                    ->from('payments as p2')
                    ->whereRaw('p2.uid = payments.uid') // Check for the same user
                    ->where('p2.transaction_status', 'Completed')
                    ->whereNotIn('p2.package', ['broadband', 'wifi'])
                    ->whereMonth('p2.created_at', '<=', $currentMonth->month)
                    ->whereYear('p2.created_at', '<=', $currentMonth->year);
            });

            // previous month paid users
            $previousMonthPayments = Payment::query();
            if($zone_id !== "null"){
                $previousMonthPayments = $previousMonthPayments->where('payments.zone_id', $zone_id);
            }
            $previousMonthPayments->select('payments.uid', 'payments.amount','internet_package_corporates.price')
                ->where('payments.transaction_status', 'Completed')
                ->whereNotIn('payments.package', ['broadband','wifi'])
                ->whereMonth('payments.created_at', $previousMonth)
                ->join('internet_package_corporates', 'payments.package', '=', 'internet_package_corporates.package_name');

            // current month
            $currentMonthPaidUsers = $currentMonthPayments->count('uid');
            $currentMonthTotalPaidAmount = $currentMonthPayments->sum('amount');

            // previous month
            $previousMonthPaidUsers = $previousMonthPayments->count('uid');
            $previousMonthTotalPaidAmount = $previousMonthPayments->sum('internet_package_corporates.price');
            $previousMonthTotalPaidAmount1 = $previousMonthPayments->sum('amount');

            // current month firstly paid
            $currentMonthFirstlyPaidUsers = $currentMonthFirstlyPayments->count('uid');
            $currentMonthFirstlyTotalPaidAmount = $currentMonthFirstlyPayments->sum('amount');

            // Store results for the month
            $formattedResults[] = [
                "year" => $currentMonth->year,
                "month" => $currentMonth->month,
                "current_month_paid_users" => $currentMonthPaidUsers,
                "current_month_total_paid_amount" => round($currentMonthTotalPaidAmount),
                "previous_month_paid_users" => $previousMonthPaidUsers,
                "previous_month_total_paid_amount" => round($previousMonthTotalPaidAmount1),
                "current_month_firstly_paid_users" => $currentMonthFirstlyPaidUsers,
                "current_month_firstly_total_paid_amount" => round($currentMonthFirstlyTotalPaidAmount),
                "current_month_total_receivable_amount" => round($previousMonthTotalPaidAmount+$currentMonthFirstlyTotalPaidAmount),

            ];

            // Move to the next month
            $startDate->addMonth();
        }

        // Add results to response
        $returned_data['results']['total'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Internet User Daily Record List With Date Filter
     */
    public function internetUserDailyRecordList($zone_id, $from, $to): JsonResponse {
        $returned_data = ResponseWrapper::Start();
        set_time_limit(0);

        $base_role = User::where('id', $zone_id)->value('base_role');

        // Convert `from` and `to` into Carbon instances
        $startDate = Carbon::createFromFormat('d-m-y', $from)->startOfDay();
        $endDate = Carbon::createFromFormat('d-m-y', $to)->endOfDay();

        // Initialize results array
        $formattedResults = [];

        // Loop through each day within the range
        while ($startDate->lte($endDate)) {
            $currentDay = $startDate->copy();

            // Current day paid users
            $currentDayPayments = Payment::query();
            if($zone_id !== "null"){
                $currentDayPayments = $currentDayPayments->where('payments.zone_id', $zone_id);
            }
            $currentDayPayments->select('payments.uid', 'payments.amount')
                ->where('payments.transaction_status', 'Completed')
                ->whereNotIn('payments.package', ['broadband', 'wifi'])
                ->whereDate('payments.created_at', $currentDay);

            // Current day
            $currentDayPaidUsers = $currentDayPayments->count('uid');
            $currentDayTotalPaidAmount = $currentDayPayments->sum('amount');

            // Store results for the day
            $formattedResults[] = [
                "year" => $currentDay->year,
                "month" => $currentDay->month,
                "day" => $currentDay->day,
                "current_day_paid_users" => $currentDayPaidUsers,
                "current_day_total_paid_amount" => round($currentDayTotalPaidAmount)
            ];

            // Move to the next day
            $startDate->addDay();
        }

        // Add results to response
        $returned_data['results']['total'] = $formattedResults;
        return ResponseWrapper::End($returned_data);
    }
}
