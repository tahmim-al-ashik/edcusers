<?php

namespace App\Http\Requests\School;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class NMSCategoryBasedAdminStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'mobile_number' => 'string|unique:users,auth_id',
            'whatsapp_number' => 'string',
            'name' => 'required|string|max:50',
            'email' => 'required|string|email',
            'username' => 'required|string|max:50|unique:panel_users,auth_id',
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