<?php

namespace App\Http\Controllers\school;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\PanelUser;
use App\Models\School\NMSCategoryBasedAdmin;
use App\Models\School\SchoolManager;
use App\Models\School\SchoolProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NMSCommonController extends Controller
{
    /**
     * Total Status Summary
     *
     * @return \Illuminate\Http\Response
     */
    public function totalSummary($lot_id): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $user = PanelUser::where('id', $lot_id)->first();

        // Base Query
        $query = SchoolProfile::query();
        if ($lot_id !== 'null' && $user) {
            if($user->base_role === 'lot_admin'){

                $query = $query->where('lot_id', $lot_id);

            } else if($user->base_role === 'edc_manager'){

                $manager = SchoolManager::where('uid', $user->user_id)->first();
                $query = $query->where('lot_id', $manager->lot_id);

                if ($manager->assigned_union_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('union_id', $manager->assigned_union_id);
                    });
                } elseif ($manager->assigned_upazila_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('upazila_id', $manager->assigned_upazila_id);
                    });
                } elseif ($manager->assigned_district_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('district_id', $manager->assigned_district_id);
                    });
                } elseif ($manager->assigned_division_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('division_id', $manager->assigned_division_id);
                    });
                }
            }
        }

        $query = $query->selectRaw("
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' THEN 1 ELSE 0 END), 0) AS total_inactive,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' THEN 1 ELSE 0 END), 0) AS total_pending
            ");

        $query = $query->first();

        $returned_data['results'] = $query;
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }

    /**
     * Total Status Summary
     *
     * @return \Illuminate\Http\Response
     */
    public function categoryWiseSummary($lot_id): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $user = PanelUser::where('id', $lot_id)->first();

        // Base Query
        $query = SchoolProfile::query();
        if ($lot_id !== 'null' && $user) {
            if($user->base_role === 'lot_admin'){

                $query = $query->where('lot_id', $lot_id);

            } else if($user->base_role === 'edc_manager'){

                $manager = SchoolManager::where('uid', $user->user_id)->first();
                $query = $query->where('lot_id', $manager->lot_id);

                if ($manager->assigned_union_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('union_id', $manager->assigned_union_id);
                    });
                } elseif ($manager->assigned_upazila_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('upazila_id', $manager->assigned_upazila_id);
                    });
                } elseif ($manager->assigned_district_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('district_id', $manager->assigned_district_id);
                    });
                } elseif ($manager->assigned_division_id) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('division_id', $manager->assigned_division_id);
                    });
                }
            }
        }

        $query = $query->selectRaw("
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'primary_education' THEN 1 ELSE 0 END), 0) AS active_primary_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'primary_education' THEN 1 ELSE 0 END), 0) AS inactive_primary_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'primary_education' THEN 1 ELSE 0 END), 0) AS pending_primary_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'secondary_education' THEN 1 ELSE 0 END), 0) AS active_secondary_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'secondary_education' THEN 1 ELSE 0 END), 0) AS inactive_secondary_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'secondary_education' THEN 1 ELSE 0 END), 0) AS pending_secondary_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'technical_education' THEN 1 ELSE 0 END), 0) AS active_technical_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'technical_education' THEN 1 ELSE 0 END), 0) AS inactive_technical_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'technical_education' THEN 1 ELSE 0 END), 0) AS pending_technical_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'national_university' THEN 1 ELSE 0 END), 0) AS active_national_university,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'national_university' THEN 1 ELSE 0 END), 0) AS inactive_national_university,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'national_university' THEN 1 ELSE 0 END), 0) AS pending_national_university,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'madrasa_education' THEN 1 ELSE 0 END), 0) AS active_madrasa_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'madrasa_education' THEN 1 ELSE 0 END), 0) AS inactive_madrasa_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'madrasa_education' THEN 1 ELSE 0 END), 0) AS pending_madrasa_education,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'govt_org' THEN 1 ELSE 0 END), 0) AS active_govt_org,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'govt_org' THEN 1 ELSE 0 END), 0) AS inactive_govt_org,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'govt_org' THEN 1 ELSE 0 END), 0) AS pending_govt_org,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'law_ministry' THEN 1 ELSE 0 END), 0) AS active_law_ministry,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'law_ministry' THEN 1 ELSE 0 END), 0) AS inactive_law_ministry,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'law_ministry' THEN 1 ELSE 0 END), 0) AS pending_law_ministry,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'diabetic_association' THEN 1 ELSE 0 END), 0) AS active_diabetic_association,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'diabetic_association' THEN 1 ELSE 0 END), 0) AS inactive_diabetic_association,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'diabetic_association' THEN 1 ELSE 0 END), 0) AS pending_diabetic_association,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'land_board' THEN 1 ELSE 0 END), 0) AS active_land_board,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'land_board' THEN 1 ELSE 0 END), 0) AS inactive_land_board,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'land_board' THEN 1 ELSE 0 END), 0) AS pending_land_board,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'social_service' THEN 1 ELSE 0 END), 0) AS active_social_service,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'social_service' THEN 1 ELSE 0 END), 0) AS inactive_social_service,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'social_service' THEN 1 ELSE 0 END), 0) AS pending_social_service,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'active' AND school_profiles.institution_type = 'health_service' THEN 1 ELSE 0 END), 0) AS active_health_service,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'inactive' AND school_profiles.institution_type = 'health_service' THEN 1 ELSE 0 END), 0) AS inactive_health_service,
                COALESCE(SUM(CASE WHEN school_profiles.status = 'pending' AND school_profiles.institution_type = 'health_service' THEN 1 ELSE 0 END), 0) AS pending_health_service
            ")->first();

        // Ensure the response is null if no data is found
        if (!$query || empty(array_filter((array) $query))) {
            $returned_data['results'] = null;
        } else {
            $returned_data['results'] = $query;
        }

        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
