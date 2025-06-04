<?php

namespace App\Http\Requests\School;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SchoolUpdateRequest extends FormRequest
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
            'name' => 'required|string|max:200',
            'connection_code' => 'required|string|max:255',
            'division' => 'required|string|max:10',
            'district' => 'required|string|max:10',
            'upazila' => 'required|string|max:10',
            'union' => 'required|string|max:10',
            'village' => 'required|string|max:20',
            'latitude' => 'required|string|max:20',
            'longitude' => 'required|string|max:20',
            'auth_id' => 'required|numeric',
            'package_id' => 'required|string|max:20',
            'head_teacher_name' => 'required|string|max:200',
            'head_teacher_mobile' => 'required|string|max:20',
            'fiber_id' => 'required|string|max:50',
            'fiber_core' => 'required|string|max:50',
            'db_signal' => 'required|string|max:50',
            'fiber_start_meter' => 'required|string|max:20',
            'fiber_end_meter' => 'required|string|max:20',
            'fiber_length' => 'required|string|max:20',
            'onu_mac' => 'required|string|max:50',
            'router_login_username' => 'required|string|max:50',
            'router_login_password' => 'required|string|max:50',
            'router_login_mac' => 'required|string|max:50',
            'router_remote_management_port' => 'required|string|max:50',
            'gateway' => 'required|string|max:50',
            'subnet_mask' => 'required|string|max:50',
            'dnsv4_primary' => 'required|string|max:50',
            'dnsv4_secondary' => 'required|string|max:50',
            'ipv4_ip' => 'required|string|max:50',
            'ipv6_ip' => 'required|string|max:50',
            'slaac_enabled' => 'required|string|in:yes,no,others',
            'icmp_enabled' => 'required|string|in:yes,no,others',
            'router_model' => 'required|string|max:50',
            'router_serial_number' => 'required|string|max:50',
            'tj_box_quantity' => 'required|string|max:10',
            'fiber_patch_cord_quantity' => 'required|string|max:10',
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
    // public function messages()
    // {
    //     return [
    //         'email.required' => 'Email is required!',
    //         'name.required' => 'Name is required!',
    //     ];
    // }
}
