<?php

namespace App\Http\Controllers\transmission;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\TransCompany;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function transCompanyList(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $totalLimit = $request->get('limit') !== null ? $request->get('limit') : 1000;

        $query = TransCompany::query();
        $query = $query->get([
            'id',
            'company_name',
            'company_type',
            'contact_person_name_pri',
            'contact_person_number_pri',
            'contact_person_email_pri',
            'contact_person_designation_pri',
            'contact_person_name_sec',
            'contact_person_number_sec',
            'contact_person_email_sec',
            'contact_person_designation_sec',
            'vendor_name',
            'added_by_uid',
            'updated_by_uid',
            'status',
            'created_at'
        ]);

        if(!empty($request->get('skip')) && $request->get('skip') !== 'none'){
            $query->skip($totalSkip)->take($totalLimit);
        }
        $query->skip($totalSkip)->take($totalLimit);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function transCompanyAdd(Request $request) : JsonResponse  {
            $returned_data = ResponseWrapper::Start();

            $validated = $request->validate([
                'company_name' => 'required',
                'company_type' => 'required',

                'first_contact_person_name' => 'required',
                'first_contact_person_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'first_contact_person_email' => 'required',
                'first_contact_person_designation' => 'required',

                'second_contact_person_name' => 'required',
                'second_contact_person_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
                'second_contact_person_email' => 'required',
                'second_contact_person_designation' => 'required',

                'vendor_name' => 'required',
                'added_by_uid' => 'required',
                'updated_by_uid' => 'required',
                'status' => 'required'
            ],[
                'company_name.required' => 'Company name is required.',
                'company_type.required' => 'Company type is required.',

                'first_contact_person_name.required' => 'Primary contact person is required.',
                'first_contact_person_mobile.required' => 'Primary contact person number is required.',
                'first_contact_person_mobile.regex' => 'Primary contact person number should be from Bangladesh.',
                'first_contact_person_email.required' => 'Primary contact person email is required.',
                'first_contact_person_designation.required' => 'Primary contact person designation is required.',

                'second_contact_person_name.required' => 'Secondary contact person is required.',
                'second_contact_person_mobile.required' => 'Secondary contact person number is required.',
                'second_contact_person_mobile.regex' => 'Secondary contact person number should be from Bangladesh.',
                'second_contact_person_email.required' => 'Secondary contact person email is required.',
                'second_contact_person_designation.required' => 'Secondary contact person designation is required.',

                'vendor_name.required' => 'Vendor name is required.',
                'added_by_uid.required' => 'Added by user is required.',
                'updated_by_uid.required' => 'Updated by user is required.',
                'status.required' => 'Status is required.'
            ]);

            if(!$validated){
                $returned_data['status'] = 'success';
                $returned_data['message'] = "Validation Failed!";
                return ResponseWrapper::End($returned_data);
            }

            // create new profile
            $company = new TransCompany();
            $company->company_name = $request->get('company_name');
            $company->company_type = $request->get('company_type');
            $company->contact_person_name_pri = $request->get('first_contact_person_name');
            $company->contact_person_number_pri = $request->get('first_contact_person_mobile');
            $company->contact_person_email_pri = $request->get('first_contact_person_email');
            $company->contact_person_designation_pri = $request->get('first_contact_person_designation');
            $company->contact_person_name_sec = $request->get('second_contact_person_name');
            $company->contact_person_number_sec = $request->get('second_contact_person_mobile');
            $company->contact_person_email_sec = $request->get('second_contact_person_email');
            $company->contact_person_designation_sec = $request->get('second_contact_person_designation');
            $company->vendor_name = $request->get('vendor_name');
            $company->added_by_uid = $request->get('added_by_uid');
            $company->updated_by_uid = $request->get('updated_by_uid');
            $company->status = $request->get('status');
            $company->save();

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Company added successfully!';
            return ResponseWrapper::End($returned_data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transCompanyDetails($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = TransCompany::where('id', $id)->get();
        $returned_data['status'] = 'success';
        $returned_data['results']['details'] = $query;
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transCompanyEdit(Request $request, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'company_name' => 'required',
            'company_type' => 'required',

            'first_contact_person_name' => 'required',
            'first_contact_person_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'first_contact_person_email' => 'required',
            'first_contact_person_designation' => 'required',

            'second_contact_person_name' => 'required',
            'second_contact_person_mobile' => ['required', 'regex:/^(01[3456789])(\d{8})$/'],
            'second_contact_person_email' => 'required',
            'second_contact_person_designation' => 'required',

            'vendor_name' => 'required',
            // 'added_by_uid' => 'required',
            'updated_by_uid' => 'required',
            'status' => 'required'
        ],[
            'company_name.required' => 'Company name is required.',
            'company_type.required' => 'Company type is required.',

            'first_contact_person_name.required' => 'Primary contact person is required.',
            'first_contact_person_mobile.required' => 'Primary contact person number is required.',
            'first_contact_person_mobile.regex' => 'Primary contact person number should be from Bangladesh.',
            'first_contact_person_email.required' => 'Primary contact person email is required.',
            'first_contact_person_designation.required' => 'Primary contact person designation is required.',

            'second_contact_person_name.required' => 'Secondary contact person is required.',
            'second_contact_person_mobile.required' => 'Secondary contact person number is required.',
            'second_contact_person_mobile.regex' => 'Secondary contact person number should be from Bangladesh.',
            'second_contact_person_email.required' => 'Secondary contact person email is required.',
            'second_contact_person_designation.required' => 'Secondary contact person designation is required.',

            'vendor_name.required' => 'Vendor name is required.',
            // 'added_by_uid.required' => 'Added by user is required.',
            'updated_by_uid.required' => 'Updated by user is required.',
            'status.required' => 'Status is required.'
        ]);

        if(!$validated){
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        $company = TransCompany::where('id', $id)->first();
        if ($company) {
            $company->update([
                'company_name' => $request->get('company_name'),
                'company_type' => $request->get('company_type'),
                'contact_person_name_pri' => $request->get('first_contact_person_name'),
                'contact_person_number_pri' => $request->get('first_contact_person_mobile'),
                'contact_person_email_pri' => $request->get('first_contact_person_email'),
                'contact_person_designation_pri' => $request->get('first_contact_person_designation'),
                'contact_person_name_sec' => $request->get('second_contact_person_name'),
                'contact_person_number_sec' => $request->get('second_contact_person_mobile'),
                'contact_person_email_sec' => $request->get('second_contact_person_email'),
                'contact_person_designation_sec' => $request->get('second_contact_person_designation'),
                'vendor_name' => $request->get('vendor_name'),
                'added_by_uid' => $request->get('added_by_uid'),
                'updated_by_uid' => $request->get('updated_by_uid'),
                'status' => $request->get('status'),
            ]);
        }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'Company updated successfully!';
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transCompanyDelete($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $companyDeleted = TransCompany::where('id', $id)->delete();
        if ($companyDeleted) {
            $returned_data['status']  = 'success';
            $returned_data['message'] = "Company deleted successfully!";
        } else {
            $returned_data['status'] = 'success';
            $returned_data['message'] = "Try again something went wrong!";
        }
        return ResponseWrapper::End($returned_data);

    }

    // Summary Transmission Company
    public function summaryTransCompany() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // summary
        $summary = TransCompany::selectRaw(
            'COUNT(trans_companies.id) AS total,
             COUNT(CASE WHEN trans_companies.status = \'active\' THEN 1 END) AS total_active,
             COUNT(CASE WHEN trans_companies.status = \'pending\' THEN 1 END) AS total_pending'
        )->get();

        $returned_data['results'] = $summary;
        return ResponseWrapper::End($returned_data);
    }
}
