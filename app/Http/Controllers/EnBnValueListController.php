<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\EnBnValueList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnBnValueListController extends Controller
{
    public function getList(Request $request, $type) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = EnBnValueList::where('type', '=', $type)->get();
        return ResponseWrapper::End($returned_data);
    }
}
