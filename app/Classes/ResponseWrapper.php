<?php

namespace App\Classes;

use \Illuminate\Http\JsonResponse;

class ResponseWrapper {
    static function Start() : array {
        return array(
            "results" => [],
            "status" => "error",
            "error_type" => "",
            "message" => ""
        );
    }

    static function End($data) : JsonResponse {
        if(!empty($data['results'])){
            $data['status'] = 'success';
        }
        return response()->json($data);
    }
}
