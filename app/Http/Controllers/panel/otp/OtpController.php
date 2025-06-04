<?php

namespace App\Http\Controllers\panel\otp;

use App\Http\Controllers\Controller;
use App\Classes\ResponseWrapper;
use App\Models\OtpReference;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getOtp($id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $latestOtpReference = OtpReference::where('mobile_number', $id)->latest()->first();
        if ($latestOtpReference) {
            $returned_data['results']['list'] = [
                'mobile_number' => $latestOtpReference->mobile_number,
                'otp' => $latestOtpReference->otp,
            ];
        } else {
            $returned_data['results']['list'] = null;
        }
        return ResponseWrapper::End($returned_data);
    }

    public function deleteOtp($id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $deleted = OtpReference::where('mobile_number', $id)->delete();
        if ($deleted) {
            $returned_data['results'] = true;
        } else {
            $returned_data['results'] = false;
        }
        return ResponseWrapper::End($returned_data);
    }

}
