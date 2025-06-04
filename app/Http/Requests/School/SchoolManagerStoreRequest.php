<?php

namespace App\Http\Requests\School;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SchoolManagerStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mobile_number' => 'required|string|unique:users,auth_id',
            'whatsapp' => 'string|max:15',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:user_profiles,email',
            'division' => 'required|string|max:10',
            'district' => 'required|string|max:10',
            'upazila' => 'required|string|max:10',
            'union' => 'required|string|max:10',
            'village' => 'required|string|max:20',
            'latitude' => 'required|string|max:20',
            'longitude' => 'required|string|max:20',
            'address_direction' => 'string',
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


     /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.required' => 'Email is required!',
            'name.required' => 'Name is required!',
        ];
    }
}
