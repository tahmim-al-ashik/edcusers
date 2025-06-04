<?php

namespace App\Http\Controllers\panel\isp_client;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\CorporateClientsSettings;
use App\Models\CorporateClient;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ISPSettingsController extends Controller
{
    public function corporateClientList() : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = CorporateClient::where('status','active')->whereNotNull('zone_name')->get(['id','uid as client_id','zone_name']);
        $returned_data['results']['success'] = true;
        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Get client settings
     *
     * @return \Illuminate\Http\Response
     */
    public function getClientSettings($client_uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = CorporateClientsSettings::where('client_uid', '=', $client_uid)->get([
            'logo',
            'signature',
            'billing_cycle',
            'manual_disable_day',
            'payment_method',
            'bkash_username',
            'bkash_password',
            'bkash_app_key',
            'bkash_app_secret_key',
        ]);
        // success/fail response
        $returned_data['results']['success'] = true;
        $returned_data['results'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * upload company logo
     *
     * @return \Illuminate\Http\Response
     */
    public function companyLogo(Request $request, $client_uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'logo' => 'required|mimes:jpeg,png,jpg|max:1024'
        ],[
            'logo.required' => 'Logo is required.',
            'logo.mimes' => 'Allowed extensions are jpeg, png, jpg.',
            'logo.max' => 'Maximum upload size is 1MB.',
        ]);

        if (!$validated) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Variables
        $logo = $request->file('logo');
        $logoExist = CorporateClientsSettings::where('client_uid', '=', $client_uid)->value('logo');

        if ($logoExist) {
            $existingLogoPath = public_path($logoExist);
            if (file_exists($existingLogoPath)) {
                if (!unlink($existingLogoPath)) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = "Failed to delete existing logo!";
                    return ResponseWrapper::End($returned_data);
                }
            } else {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Existing logo file does not exist!";
                return ResponseWrapper::End($returned_data);
            }
        }

        if ($request->hasFile('logo')) {
            $logoName = date('YmdHi') . '-' . $logo->getClientOriginalName();
            $logo->move(public_path('client/logo'), $logoName);

            CorporateClientsSettings::where('client_uid', '=', $client_uid)->update([
                'logo' => 'client/logo/' . $logoName
            ]);

            // success response
            $returned_data['results']['message'] = 'Company Logo Updated Successfully';
            $returned_data['results']['success'] = true;
            $returned_data['results']['logo'] = 'client/logo/' . $logoName;
            return ResponseWrapper::End($returned_data);
        } else {
            // fail response
            $returned_data['results']['message'] = 'Failed to upload company logo.';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
    }


    /**
     * upload & update signature
     *
     * @return \Illuminate\Http\Response
     */
    public function signature(Request $request, $client_uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'signature' => 'required|mimes:jpeg,png,jpg|max:1024'
        ],[
            'signature.required' => 'Signature is required.',
            'signature.mimes' => 'Allowed extensions are jpeg, png, jpg.',
            'signature.max' => 'Maximum upload size is 1MB.',
        ]);

        if (!$validated) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Variables
        $signature = $request->file('signature');
        $signatureExist = CorporateClientsSettings::where('client_uid', '=', $client_uid)->value('signature');

        if ($signatureExist) {
            $existingSignaturePath = public_path($signatureExist);
            if (file_exists($existingSignaturePath)) {
                if (!unlink($existingSignaturePath)) {
                    $returned_data['status'] = 'error';
                    $returned_data['message'] = "Failed to delete existing signature!";
                    return ResponseWrapper::End($returned_data);
                }
            } else {
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Existing signature file does not exist!";
                return ResponseWrapper::End($returned_data);
            }
        }

        if ($request->hasFile('signature')) {
            $signatureName = date('YmdHi') . '-' . $signature->getClientOriginalName();
            $signature->move(public_path('client/signature'), $signatureName);

            CorporateClientsSettings::where('client_uid', '=', $client_uid)->update([
                'signature' => 'client/signature/' . $signatureName
            ]);

            // success response
            $returned_data['results']['message'] = 'Company signature updated successfully';
            $returned_data['results']['success'] = true;
            $returned_data['results']['signature'] = 'client/signature/' . $signatureName;
            return ResponseWrapper::End($returned_data);
        } else {
            // fail response
            $returned_data['results']['message'] = 'Failed to upload company signature.';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
    }

    /**
     * Billing Cycle Edit Update
     *
     * @param  \Illuminate\Http\Request
     */
    public function billingCycle(Request $request, $client_uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'billing_cycle' => 'required'
        ],[
            'billing_cycle.required' => 'Billing cycle is required.'
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }
        // Variables
        $billingCycle = $request->get('billing_cycle');
        $disable_day = $request->get('disable_day');

        if($billingCycle){
            CorporateClientsSettings::where('client_uid', '=', $client_uid)->update([
                'billing_cycle' => $billingCycle,
                'manual_disable_day' => $disable_day
            ]);

            // success/fail response
            $returned_data['results']['message'] = 'Billing cycle updated successfully.';
            $returned_data['results']['success'] = true;
            $returned_data['results'] = [
                'billing_cycle' => $billingCycle,
                'manual_disable_day' => $disable_day
            ];
            return ResponseWrapper::End($returned_data);
        }else {
            // success/fail response
            $returned_data['results']['message'] = 'Failed to update billing cycle.';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
    }

    /**
     * Payment method Edit Update
     *
     * @param  \Illuminate\Http\Request
     */
    public function paymentMethod(Request $request, $client_uid) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'payment_method' => 'required'
        ],[
            'payment_method.required' => 'Payment method is required.'
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }
        // Variables
        $paymentMethod = $request->get('payment_method');
        $bkashUsername = $request->get('bkash_username');
        $bkashPassword = $request->get('bkash_password');
        $bkashAppKey = $request->get('bkash_app_key');
        $bkashAppSecretKey = $request->get('bkash_app_secret_key');

        if($paymentMethod){
            CorporateClientsSettings::where('client_uid', '=', $client_uid)->update([
                'payment_method' => $paymentMethod,
                'bkash_username' => $bkashUsername,
                'bkash_password' => $bkashPassword,
                'bkash_app_key' => $bkashAppKey,
                'bkash_app_secret_key' => $bkashAppSecretKey
            ]);

            // success/fail response
            $returned_data['results']['message'] = 'Payment method updated successfully.';
            $returned_data['results']['success'] = true;
            $returned_data['results'] = [
                'payment_method' => $paymentMethod,
                'bkash_username' => $bkashUsername,
                'bkash_password' => $bkashPassword,
                'bkash_app_key' => $bkashAppKey,
                'bkash_app_secret_key' => $bkashAppSecretKey
            ];
            return ResponseWrapper::End($returned_data);
        }else {
            // success/fail response
            $returned_data['results']['message'] = 'Failed to update payment method.';
            $returned_data['results']['success'] = false;
            return ResponseWrapper::End($returned_data);
        }
    }

    public function deleteISPBusiness(Request $request, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $corporate_client = CorporateClient::where('uid', $id)->delete();
        $user_profile = UserProfile::where('uid', $id)->delete();
        $user = User::where('id', $id)->delete();
        if ($corporate_client && $user_profile && $user) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "ISP Business User Deleted successfully!";
        } else {
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Try again something went wrong!";
        }
        return ResponseWrapper::End($returned_data);
    }
}
