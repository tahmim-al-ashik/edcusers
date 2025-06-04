<?php

namespace App\Http\Controllers\graph;

use App\Http\Controllers\Controller;
use App\Classes\ResponseWrapper;
use App\Models\NetworkSupportCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GraphSupportCenterUserController extends Controller
{
    /**
     * Support Center Count Bar chart.
     */
    public function barchart() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Initialize an array to hold all months for the last 6 months, including the current month
        $resultsByMonthAndStatus = [];

        // Calculate the date six months ago
        $sixMonthsAgo = now()->subMonths(5); // Adjusted to cover the current month as well

        // Get the total count of records according to month and connection status for the previous 6 months
        $totalByMonthAndStatus = NetworkSupportCenter::selectRaw('YEAR(created_at) AS year, MONTH(created_at) AS month, status, COUNT(*) AS total')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('year', 'month', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        // Loop through the totalByMonthAndStatus results and populate the resultsByMonthAndStatus array
        foreach ($totalByMonthAndStatus as $record) {
            $year = $record->year;
            $month = $record->month;
            $status = $record->status;
            $total = $record->total;

            // Create an entry for the month if it doesn't exist
            if (!isset($resultsByMonthAndStatus[$month])) {
                $resultsByMonthAndStatus[$month] = [
                    'year' => $year,
                    'month' => $month,
                    'total' => 0,
                    'active' => 0,
                    'pending' => 0,
                    'processing' => 0,
                    'suspend' => 0
                ];
            }

            // Update the count for the current status
            $resultsByMonthAndStatus[$month][$status] = $total;

            // Increment the total count for the month
            $resultsByMonthAndStatus[$month]['total'] += $total;
        }

        // Fill in missing months with 0 counts
        $currentYear = now()->year;
        $currentMonth = now()->month;
        for ($i = 0; $i < 6; $i++) {
            $year = date('Y', strtotime("-$i months"));
            $month = date('n', strtotime("-$i months"));


            if (!isset($resultsByMonthAndStatus[$month])) {
                $resultsByMonthAndStatus[$month] = [
                    'total' => 0,
                    'pending' => 0,
                    'active' => 0,
                    'processing' => 0,
                    'suspend' => 0,
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
        $returned_data['results']['total'] = NetworkSupportCenter::count();

        return ResponseWrapper::End($returned_data);

    }

    /**
     * Sales Agent line chart
     */
    public function supportCenterLineChart($days, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Calculate the date x days ago
        $startDate = now()->subDays($days)->startOfDay();

        $query = NetworkSupportCenter::selectRaw(
            "DAY(created_at) AS day,
            MONTH(created_at) AS month,
            YEAR(created_at) AS year,
            COUNT(uid) AS total,
            COUNT(CASE WHEN status = 'active' THEN 1 END) AS total_active,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) AS total_pending"
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
}

