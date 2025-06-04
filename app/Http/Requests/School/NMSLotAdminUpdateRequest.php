<?php

namespace App\Http\Requests\School;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class NMSLotAdminUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'mobile_number' => 'string|max:15',
            'whatsapp_number' => 'string|max:15',
            'name' => 'required|string|max:50',
            'email' => 'required|string|email',
            'lot_isp_name' => 'required|string|max:50',
            'proprietor_name' => 'required|string|max:50',
            'proprietor_mobile' => 'required|string|max:15',
            'proprietor_email' => 'required|string|max:50',
            'bank_name' => 'required|string|max:50',
            'bank_account_name' => 'required|string|max:50',
            'bank_account_number' => 'required|string|max:50',
            'bank_branch_address' => 'required|string|max:256',
            'installation_cost' => 'required|numeric|integer',
            'package_id' => 'required',
            'division' => 'required|string|max:10',
            'district' => 'required|string|max:10',
            'upazila' => 'required|string|max:10',
            'union' => 'required|string|max:10',
            'village' => 'required|string|max:20',
            'address_direction' => 'string',
            'latitude' => 'required|string|max:20',
            'longitude' => 'required|string|max:20',
            'auth_id' => 'required|numeric',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'error_type' => 'validation_error',
            'errors' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }

    public function messages()
    {
        return [
            'email.required' => 'Email is required!',
            'name.required' => 'Name is required!',
        ];
    }
}
