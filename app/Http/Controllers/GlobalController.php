<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\OtpReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlobalController extends Controller
{
    public function sendTextSms(Request $request) : JsonResponse {
        $send_sms_type = $request->get('sms_type');
        $send_sms_number = $request->get('send_to');
        $numberLength = strlen($send_sms_number);
        $smsText = $request->get('message');
        $smsReturnMessage = 'user exists';
        $smsResult = '';
        $userId = OtpReference::where('mobile_number', '=', $send_sms_number)->first();
        $send_sms = 'error';
        if (empty($userId) && $numberLength >= 11) {
            if( $send_sms_type === 'otp_verification'){
                $otp = random_int(1000, 9999);
                $otp_save = new OtpReference;
                $otp_save->mobile_number = $send_sms_number;
                $otp_save->otp = $otp;
                $otp_save->limit(1);
                $otp_save->save();
                $smsText = "স্বাধীন ওয়াইফাই ওটিপি " . $otp;
                $smsResult = $otp;
                $smsReturnMessage = 'register successfully';
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($send_sms_number, $smsText);
            } else if($send_sms_type === 'new_registration_password'){
                $password = (new \App\Classes\CustomHelpers)->generate_new_password();
                $smsText = "আপনার অ্যাপ পাসওয়ার্ডটি হলো- " . $password;
                $smsResult = $password;
                $smsReturnMessage = 'register successfully';
                $send_sms = (new \App\Classes\CustomHelpers)->send_text_sms($send_sms_number, $smsText);
            }
        }
        elseif (!empty($userId) && $send_sms_type === 'otp_verification' && $numberLength >= 11) {
            $upOtp = random_int(1000, 9999);
            $updateTime = OtpReference::select('updated_at')->where('mobile_number', '=', $send_sms_number)->first();
            $date = today()->format('Y-m-d');
            $upTime = date('Y-m-d', strtotime($updateTime));
            if($upTime < $date){
                $otpUpdate = OtpReference::where('mobile_number', '=', $send_sms_number)->limit(1)->update(['otp' => $upOtp]);
                $smsuText = "স্বাধীন ওয়াইফাই নতুন ওটিপি " . $upOtp;
                $smsResult = $upOtp;
            }
        }
        return response()->json(['results'=> true, 'status'=> $send_sms, 'message'=> $smsReturnMessage]);
    }

    public function verifyOtpNumber(Request $request, $mobile_number, $otp_number) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $returned_data['results'] = OtpReference::where('mobile_number', '=', $mobile_number)->where('otp', '=', $otp_number)->exists();
        if($returned_data['results']){
            OtpReference::where('mobile_number', '=', $mobile_number)->delete();
        } else {
            $returned_data['error_type'] = 'otp_not_match';
        }
        return ResponseWrapper::End($returned_data);
    }

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
