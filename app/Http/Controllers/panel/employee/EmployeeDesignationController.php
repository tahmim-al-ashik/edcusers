<?php

namespace App\Http\Controllers\panel\employee;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\EmployeeDesignation;
use Illuminate\Http\JsonResponse;

class EmployeeDesignationController extends Controller
{
    public function getEmployeeDesignationList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = EmployeeDesignation::all();
        return ResponseWrapper::End($returned_data);
    }
}
