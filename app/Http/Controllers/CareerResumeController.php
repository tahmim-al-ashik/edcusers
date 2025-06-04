<?php

namespace App\Http\Controllers;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Models\CareerResume;
use App\Models\CareerResumesType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareerResumeController extends Controller
{
    public function getCareerTypesList(Request $request, $status) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $query = CareerResumesType::query();
        if($status !== 'all'){
            $query->where('is_active', '=', $status);
        }
        $returned_data['results'] = $query->get();

        return ResponseWrapper::End($returned_data);
    }


    public function getApplicantList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $c_status = $request->get('communication_status');
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;
        $sortBy = $request->get('sort_by') !== null ? $request->get('sort_by') : 'DESC';

        $query = CareerResume::query();
        $query->leftJoin('career_resumes_types', 'career_resumes_types.id', '=', 'career_resumes.career_id');
        $query->leftJoin('communications', function ($join) {
            $join->on('communications.customer_uid', '=', 'career_resumes.mobile_number')
                ->where('communications.type', '=', 'career')
                ->whereRaw('communications.id = (SELECT id FROM communications WHERE customer_uid = career_resumes.mobile_number AND type = "career" ORDER BY created_at DESC LIMIT 1)');
        });

        if($request->get('district') !== 'all'){
            $query->where('career_resumes.district_id', '=', $request->get('district'));
        }
        if($request->get('career') !== 'all'){
            $query->where('career_resumes.career_id', '=', $request->get('career'));
        }
        if ($c_status !== 'all' && $c_status !== null) {
            $query->whereExists(function ($query) use ($c_status) {
                $query->select(DB::raw(1))
                      ->from('communications as com2')
                      ->whereColumn('com2.customer_uid', 'career_resumes.mobile_number')
                      ->where('com2.type', '=', 'career')
                      ->where('com2.status', '=', $c_status)
                      ->whereRaw('com2.customer_uid = career_resumes.mobile_number AND com2.created_at = (SELECT MAX(created_at) FROM communications WHERE customer_uid = com2.customer_uid)');
            });
        }

        $query->orderBy('career_resumes.created_at', $sortBy);
        $query->skip($totalSkip)->take(25);

        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'career_resumes.id',
            'career_resumes.full_name_en',
            'career_resumes.full_name_bn',
            'career_resumes.career_id',
            'career_resumes_types.career_name_bn',
            'career_resumes_types.career_name_en',
            'career_resumes.email',
            'career_resumes.mobile_number',
            'communications.status as c_status',
            'career_resumes.created_at'
        ]);

        return ResponseWrapper::End($returned_data);

    }


    public function getApplicantSearchResult(Request $request,$keyword) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $query = CareerResume::query();
        $query->leftJoin('career_resumes_types', 'career_resumes_types.id', '=', 'career_resumes.career_id');
        $query->where('career_resumes.mobile_number', '=',strtolower(trim($keyword)));
        $returned_data['results']['total'] = $query->count();
        $returned_data['results']['list'] = $query->get([
            'career_resumes.id',
            'career_resumes.full_name_en',
            'career_resumes.full_name_bn',
            'career_resumes.career_id',
            'career_resumes_types.career_name_bn',
            'career_resumes_types.career_name_en',
            'career_resumes.email',
            'career_resumes.mobile_number',
            'career_resumes.created_at'
        ]);

        return ResponseWrapper::End($returned_data);

    }


    public function getApplicantDetails(CustomHelpers $customHelpers, Request $request, $id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $detailData = CareerResume::where('id', '=', $id)->first();
        $detailData['career_name'] = CareerResumesType::where('id', '=', $detailData['career_id'])->value('career_name_en');
        $detailData['address_full'] = $customHelpers->generate_user_address(null, $detailData);
        if (!empty($detailData['certifications'])) {
            $detailData['certifications'] = json_decode($detailData['certifications'], true);
        }
        if (!empty($detailData['educations'])) {
            $detailData['educations'] = json_decode($detailData['educations'], true);
        }
        if (!empty($detailData['experiences'])) {
            $detailData['experiences'] = json_decode($detailData['experiences'], true);
        }
        if (!empty($detailData['languages'])) {
            $detailData['languages'] = json_decode($detailData['languages'], true);
        }
        if (!empty($detailData['data_object'])) {
            $detailData['data_object'] = json_decode($detailData['data_object'], true);
        }

        $returned_data['results'] = $detailData;

        return ResponseWrapper::End($returned_data);
    }
}
