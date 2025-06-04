<?php

namespace App\Http\Controllers\panel\partner;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\BroadbandDbOperator;
use App\Models\WifiDbRadCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UserListController extends Controller
{
    public function hotspotUserList($branch_name) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = WifiDbRadCheck::query();
        $query->leftJoin('userinfo','radcheck.username', '=', 'userinfo.username');
        $query->join('radusergroup','radcheck.username', '=', 'radusergroup.username');
        $query->select('radcheck.username', 'radcheck.branch', 'radcheck.value', 'radusergroup.groupname', 'userinfo.firstname', 'radcheck.updatetime');
        $query->where('radcheck.branch', '=', $branch_name);
        $query->groupBy('radcheck.username');

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'radcheck.username',
            'radcheck.branch as branch_name',
            'radcheck.value',
            'radusergroup.groupname',
            'userinfo.firstname',
            'radcheck.updatetime',
        ]);

        return ResponseWrapper::End($returned_data);
    }
}
