<?php

namespace App\Http\Controllers\school;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Classes\RouterOsApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\SchoolStoreRequest;
use App\Http\Requests\School\SchoolUpdateRequest;
use App\Imports\SchoolInfoImport;
use App\Models\GeoDistrict;
use App\Models\GeoDivision;
use App\Models\GeoUnionPouroshova;
use App\Models\GeoUpazila;
use App\Models\GeoVillage;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\PanelUser;
use App\Models\School\NMSCategoryBasedAdmin;
use App\Models\School\NMSLotAdmin;
use App\Models\School\SchoolDevice;
use App\Models\School\SchoolLatLong;
use App\Models\School\SchoolManager;
use App\Models\School\SchoolProfile;
use App\Models\TransLatLong;
use App\Models\TransPop;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SchoolProfileController extends Controller
{
    // public function index(Request $request, $type): JsonResponse
    // {
    //     $returned_data = ResponseWrapper::Start();

    //     $auth_id = $request->get('auth');
    //     $division_id = $request->get('division');
    //     $district_id = $request->get('district');
    //     $upazila_id = $request->get('upazila');
    //     $union_id = $request->get('union');
    //     $status = $request->get('status');
    //     $lot_id = $request->get('lot_id');
    //     $uptimeFilter = $request->get('uptime');
    //     $per_page = (int) $request->get('per_page') ?? 10;
    //     $search = strtolower(trim($request->get('search')));
    //     $sorting_id = $request->get('sorting_id') ?? 'created_at';
    //     $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');

    //     // Base Query
    //     $query = SchoolProfile::with([
    //         'user_profiles:uid,address_direction',
    //         // 'internet_users:uid,connection_status',
    //         'package:id,price',
    //         'panel_lot_admin:id,user_id',
    //         'panel_lot_admin.lot_admin:uid,id,name'
    //     ])->select([
    //         'school_profiles.id',
    //         'school_profiles.uid',
    //         'school_profiles.lot_id',
    //         'school_profiles.package_id',
    //         'school_profiles.school_name',
    //         'school_profiles.connection_code',
    //         'school_profiles.manager_id',
    //         'school_profiles.institution_type',
    //         'school_profiles.created_at'
    //     ]);

    //     $user = PanelUser::where('id', $auth_id)->first();

    //     if($user){
    //         if($user->base_role === 'lot_admin'){

    //             $query = $query->where('school_profiles.lot_id', $auth_id);

    //         } else if($user->base_role === 'edc_manager'){

    //             $manager = SchoolManager::where('uid', $user->user_id)->first();
    //             $query = $query->where('lot_id', $manager->lot_id);

    //             if (!empty($manager->assigned_union_id)) {
    //                 $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
    //                     $subQuery->where('union_id', $manager->assigned_union_id);
    //                 });
    //             } elseif (!empty($manager->assigned_upazila_id)) {
    //                 $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
    //                     $subQuery->where('upazila_id', $manager->assigned_upazila_id);
    //                 });
    //             } elseif (!empty($manager->assigned_district_id)) {
    //                 $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
    //                     $subQuery->where('district_id', $manager->assigned_district_id);
    //                 });
    //             } elseif (!empty($manager->assigned_division_id)) {
    //                 $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
    //                     $subQuery->where('division_id', $manager->assigned_division_id);
    //                 });
    //             }
    //         }
    //     }

    //     if ($type) {
    //         $query->where('institution_type', $type);
    //     }

    //     // Apply filters
    //     if ($division_id) {
    //         $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
    //     }
    //     if ($district_id) {
    //         $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
    //     }
    //     if ($upazila_id) {
    //         $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
    //     }
    //     if ($union_id) {
    //         $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
    //     }
    //     // if ($status) {
    //     //     $query->whereHas('internet_users', function ($subQuery) use ($status) {
    //     //         $subQuery->where('connection_status', $status);
    //     //     });
    //     // }
    //     if ($lot_id) {
    //         $query->whereHas('panel_lot_admin', fn($subQuery) => $subQuery->where('id', $lot_id));
    //     }
    //     if ($search) {
    //         $query->where(function ($subQuery) use ($search) {
    //             $subQuery->where('school_name', 'LIKE', "%{$search}%")
    //                 ->orWhereHas('user_profiles', fn($subSubQuery) => $subSubQuery->where('address_direction', 'LIKE', "%{$search}%"))
    //                 // ->orWhereHas('internet_users', fn($subSubQuery) => $subSubQuery->where('connection_status', 'LIKE', "%{$search}%"))
    //                 ->orWhereHas('panel_lot_admin.lot_admin', fn($subSubQuery) => $subSubQuery->where('name', 'LIKE', "%{$search}%"));
    //         });
    //     }

    //     // Sorting logic
    //     if ($sorting_id === 'address_direction') {
    //         $query->join('user_profiles', 'school_profiles.uid', '=', 'user_profiles.uid')
    //               ->orderBy('user_profiles.address_direction', $sorting_direction);
    //     } else {
    //         $query->orderBy($sorting_id, $sorting_direction);
    //     }

    //     // Fetch paginated data
    //     $schools = $query->paginate($per_page);

    //     // Fetch uptime dynamically and include it in the results
    //     $schoolData = $schools->getCollection()->map(function ($school) {
    //         $school->uptime = $this->fetchUptime($school); // Dynamic uptime
    //         $school->status = $this->fetchStatus($school); // Dynamic uptime
    //         $school->internet_users = [
    //             'uid' => 1,
    //             'connection_status' => $school->status === "false" ? 'Active' : "Inactive"
    //         ];
    //         return $school->only(['id', 'uid', 'school_name', 'created_at', 'user_profiles', 'internet_users', 'uptime', 'status', 'panel_lot_admin']); // Only keep necessary fields
    //     });

    //     // Apply uptime filter
    //     if ($uptimeFilter) {
    //         $schoolData = $schoolData->filter(function ($school) use ($uptimeFilter) {
    //             if ($uptimeFilter === 'online') {
    //                 return $school['uptime'] !== null;
    //             } elseif ($uptimeFilter === 'offline') {
    //                 return $school['uptime'] === null;
    //             }
    //             return true;
    //         });
    //     }

    //     // Manual sorting for uptime if requested
    //     if ($sorting_id === 'uptime') {
    //         $schoolData = $schoolData->sortBy(function ($school) {
    //             return $school['uptime'] ?? '0000-00-00 00:00:00';
    //         }, SORT_REGULAR, $sorting_direction === 'DESC');
    //     }

    //     // Update the paginator's collection with the filtered and modified data
    //     $schools->setCollection($schoolData->values());

    //     // Response structure
    //     $returned_data['pagination'] = [
    //         'currentPage' => $schools->currentPage(),
    //         'perPage' => $schools->perPage(),
    //         'totalPages' => $schools->lastPage(),
    //         'totalItems' => $schools->total(),
    //     ];
    //     $returned_data['results'] = $schools->items(); // Paginated and filtered data
    //     $returned_data['status'] = 'success';

    //     return ResponseWrapper::End($returned_data);
    // }

    public function index(Request $request, $type): JsonResponse
    {

        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        // $status = $request->get('status');
        $lot_id = $request->get('lot_id');
        $uptimeFilter = $request->get('uptime');
        $statusFilter = $request->get('status');
        $per_page = (int) $request->get('per_page') ?? 10;
        $search = strtolower(trim($request->get('search')));
        $sorting_id = $request->get('sorting_id') ?? 'created_at';
        $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');

        // Base Query
        $query = SchoolProfile::with([
            'user_profiles:uid,address_direction',
            'internet_users:uid,connection_status',
            'package:id,price',
            'panel_lot_admin:id,user_id',
            'panel_lot_admin.lot_admin:uid,id,name'
        ])->select([
            'school_profiles.id',
            'school_profiles.uid',
            'school_profiles.lot_id',
            'school_profiles.package_id',
            'school_profiles.school_name',
            'school_profiles.connection_code',
            'school_profiles.manager_id',
            'school_profiles.institution_type',
            'school_profiles.created_at'
        ]);

        $user = PanelUser::where('id', $auth_id)->first();

        if($user){
            if($user->base_role === 'lot_admin'){

                $query = $query->where('school_profiles.lot_id', $auth_id);

            } else if($user->base_role === 'edc_manager'){

                $manager = SchoolManager::where('uid', $user->user_id)->first();
                $query = $query->where('lot_id', $manager->lot_id);

                if (!empty($manager->assigned_union_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('union_id', $manager->assigned_union_id);
                    });
                } elseif (!empty($manager->assigned_upazila_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('upazila_id', $manager->assigned_upazila_id);
                    });
                } elseif (!empty($manager->assigned_district_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('district_id', $manager->assigned_district_id);
                    });
                } elseif (!empty($manager->assigned_division_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('division_id', $manager->assigned_division_id);
                    });
                }
            }
        }

        if ($type) {
            $query->where('institution_type', $type);
        }

        // Apply filters
        if ($division_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
        }
        if ($district_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
        }
        if ($upazila_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
        }
        if ($union_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
        }
        // if ($status) {
        //     $query->whereHas('internet_users', function ($subQuery) use ($status) {
        //         $subQuery->where('connection_status', $status);
        //     });
        // }
        if ($lot_id) {
            $query->whereHas('panel_lot_admin', fn($subQuery) => $subQuery->where('id', $lot_id));
        }
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('school_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('user_profiles', fn($subSubQuery) => $subSubQuery->where('address_direction', 'LIKE', "%{$search}%"))
                    // ->orWhereHas('internet_users', fn($subSubQuery) => $subSubQuery->where('connection_status', 'LIKE', "%{$search}%"))
                    ->orWhereHas('panel_lot_admin.lot_admin', fn($subSubQuery) => $subSubQuery->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // Sorting logic
        if ($sorting_id === 'address_direction') {
            $query->join('user_profiles', 'school_profiles.uid', '=', 'user_profiles.uid')
                  ->orderBy('user_profiles.address_direction', $sorting_direction);
        } else {
            $query->orderBy($sorting_id, $sorting_direction);
        }

        // Fetch paginated data
        $schools = $query->paginate($per_page);

        // Fetch uptime dynamically and include it in the results
        $schoolData = $schools->getCollection()->map(function ($school) {
            $school->uptime = $this->fetchUptime($school); // Dynamic uptime
            $school->status = $this->fetchStatus($school); // Dynamic uptime
            return $school->only(['id', 'uid', 'school_name', 'created_at', 'user_profiles', 'internet_users', 'uptime', 'status', 'panel_lot_admin']); // Only keep necessary fields
        });

        // Apply uptime filter
        if ($uptimeFilter) {
            $schoolData = $schoolData->filter(function ($school) use ($uptimeFilter) {
                if ($uptimeFilter === 'online') {
                    return $school['uptime'] = null;
                } elseif ($uptimeFilter === 'offline') {
                    return $school['uptime'] === null;
                }
                return true;
            });
        }

        if ($statusFilter) {
            $schoolData = $schoolData->filter(function ($school) use ($statusFilter) {
                if ($statusFilter === 'active') {
                    return $school['status'] === "false";
                } else {
                    return $school['status'] !== "false";
                }
            });
        }

        // Manual sorting for uptime if requested
        if ($sorting_id === 'uptime') {
            $schoolData = $schoolData->sortBy(function ($school) {
                return $school['uptime'] ?? '0000-00-00 00:00:00';
            }, SORT_REGULAR, $sorting_direction === 'DESC');
        }

        // Update the paginator's collection with the filtered and modified data
        $schools->setCollection($schoolData->values());

        // Response structure
        $returned_data['pagination'] = [
            'currentPage' => $schools->currentPage(),
            'perPage' => $schools->perPage(),
            'totalPages' => $schools->lastPage(),
            'totalItems' => $schools->total(),
        ];
        $returned_data['results'] = $schools->items(); // Paginated and filtered data
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }
    
    public function listWithoutMK(Request $request, $type, $c_status): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $status = $request->get('status');
        $lot_id = $request->get('lot_id');
        $inst_type = $request->get('institution_type');
        $per_page = (int) $request->get('per_page') ?? 10;
        $search = strtolower(trim($request->get('search')));
        $sorting_id = $request->get('sorting_id') ?? 'created_at';
        $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');
        $from_date = $request->get('from');
        $to_date = $request->get('to');

        // Base Query
        $query = SchoolProfile::with([
            'user_profiles:uid,address_direction',
            'internet_users:uid,connection_status',
            'package:id,price',
            'panel_lot_admin:id,user_id',
            'panel_lot_admin.lot_admin:uid,id,name'
        ])->select([
            'school_profiles.id',
            'school_profiles.uid',
            'school_profiles.lot_id',
            'school_profiles.package_id',
            'school_profiles.school_name',
            'school_profiles.connection_code',
            'school_profiles.manager_id',
            'school_profiles.institution_type',
            'school_profiles.created_at'
        ]);

        $user = PanelUser::where('id', $auth_id)->first();

        if($user){
            if($user->base_role === 'lot_admin'){

                $query = $query->where('school_profiles.lot_id', $auth_id);

            } else if($user->base_role === 'edc_manager'){

                $manager = SchoolManager::where('uid', $user->user_id)->first();
                $query = $query->where('lot_id', $manager->lot_id);

                if (!empty($manager->assigned_union_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('union_id', $manager->assigned_union_id);
                    });
                } elseif (!empty($manager->assigned_upazila_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('upazila_id', $manager->assigned_upazila_id);
                    });
                } elseif (!empty($manager->assigned_district_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('district_id', $manager->assigned_district_id);
                    });
                } elseif (!empty($manager->assigned_division_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('division_id', $manager->assigned_division_id);
                    });
                }
            }
        }


        if ($type !== 'all') {
            $query->where('institution_type', $type);
        }
        if ($c_status !== 'all') {
            $query->where('status', $c_status);
        }
        // Apply filters
        if ($division_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
        }
        if ($district_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
        }
        if ($upazila_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
        }
        if ($union_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
        }
        if ($inst_type) {
            $query->where('school_profiles.institution_type', $inst_type);
        }
        if ($status) {
            $query->whereHas('internet_users', function ($subQuery) use ($status) {
                $subQuery->where('connection_status', $status);
            });
        }
        if ($lot_id) {
            $query->whereHas('panel_lot_admin', fn($subQuery) => $subQuery->where('id', $lot_id));
        }

        if ($from_date && $to_date) {
            $query->whereBetween('school_profiles.created_at', [
                $from_date . ' 00:00:00',
                $to_date . ' 23:59:59'
            ]);
        }

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('school_name', 'LIKE', "%{$search}%")
                    ->orWhere('connection_code', 'LIKE', "%{$search}%")
                    ->orWhereHas('user_profiles', fn($subSubQuery) => $subSubQuery->where('address_direction', 'LIKE', "%{$search}%"))
                    ->orWhereHas('internet_users', fn($subSubQuery) => $subSubQuery->where('connection_status', 'LIKE', "%{$search}%"))
                    ->orWhereHas('package', fn($subSubQuery) => $subSubQuery->where('price', 'LIKE', "%{$search}%"))
                    ->orWhereHas('panel_lot_admin.lot_admin', fn($subSubQuery) => $subSubQuery->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // Sorting logic
        if ($sorting_id === 'address_direction') {
            $query->join('user_profiles', 'school_profiles.uid', '=', 'user_profiles.uid')
                  ->orderBy('user_profiles.address_direction', $sorting_direction);
        } else {
            $query->orderBy($sorting_id, $sorting_direction);
        }

        // Fetch paginated data
        $schools = $query->paginate($per_page);

        // Response structure
        $returned_data['pagination'] = [
            'currentPage' => $schools->currentPage(),
            'perPage' => $schools->perPage(),
            'totalPages' => $schools->lastPage(),
            'totalItems' => $schools->total(),
        ];
        $returned_data['results'] = $schools->items(); // Paginated and filtered data
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }

    public function listByInstitutionType(Request $request, $type): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $status = $request->get('status');
        $inst_type = $request->get('institution_type');
        $per_page = (int) $request->get('per_page') ?? 10;
        $search = strtolower(trim($request->get('search')));
        $sorting_id = $request->get('sorting_id') ?? 'created_at';
        $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');
        $from_date = $request->get('from');
        $to_date = $request->get('to');

        // Base Query
        $query = SchoolProfile::with([
            'user_profiles:uid,address_direction',
            'internet_users:uid,connection_status',
            'package:id,price'
        ])
        ->where('status', 'active')
        ->select([
            'school_profiles.id',
            'school_profiles.uid',
            'school_profiles.package_id',
            'school_profiles.school_name',
            'school_profiles.connection_code',
            'school_profiles.manager_id',
            'school_profiles.institution_type',
            'school_profiles.created_at'
        ]);

        $user = PanelUser::where('id', $auth_id)->first();

        if($user){
            if($user->base_role === 'lot_admin'){

                $query = $query->where('school_profiles.lot_id', $auth_id);

            } else if($user->base_role === 'edc_manager'){

                $manager = SchoolManager::where('uid', $user->user_id)->first();
                $query = $query->where('lot_id', $manager->lot_id);

                if (!empty($manager->assigned_union_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('union_id', $manager->assigned_union_id);
                    });
                } elseif (!empty($manager->assigned_upazila_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('upazila_id', $manager->assigned_upazila_id);
                    });
                } elseif (!empty($manager->assigned_district_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('district_id', $manager->assigned_district_id);
                    });
                } elseif (!empty($manager->assigned_division_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('division_id', $manager->assigned_division_id);
                    });
                }
            }
        }


        if ($type !== 'all') {
            $institutionTypes = [];
            if($type === 'educational'){
                $institutionTypes = [
                    'primary_education',
                    'technical_education',
                    'national_university',
                    'secondary_education',
                    'madrasa_education'
                ];
            } else if($type === 'non_educational'){
                $institutionTypes = [
                    'govt_org',
                    'law_ministry',
                    'diabetic_association',
                    'land_board',
                    'social_service',
                    'health_service'
                ];
            }

            if (!empty($institutionTypes)) {
                $query->whereIn('school_profiles.institution_type', $institutionTypes);
            }
        }

        // Apply filters
        if ($division_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
        }
        if ($district_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
        }
        if ($upazila_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
        }
        if ($union_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
        }
        if ($inst_type) {
            $query->where('school_profiles.institution_type', $inst_type);
        }
        if ($status) {
            $query->whereHas('internet_users', function ($subQuery) use ($status) {
                $subQuery->where('connection_status', $status);
            });
        }
        if ($from_date && $to_date) {
            $query->whereBetween('school_profiles.created_at', [
                $from_date . ' 00:00:00',
                $to_date . ' 23:59:59'
            ]);
        }
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('school_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('user_profiles', fn($subSubQuery) => $subSubQuery->where('address_direction', 'LIKE', "%{$search}%"))
                    ->orWhereHas('internet_users', fn($subSubQuery) => $subSubQuery->where('connection_status', 'LIKE', "%{$search}%"));
            });
        }

        // Sorting logic
        if ($sorting_id === 'address_direction') {
            $query->join('user_profiles', 'school_profiles.uid', '=', 'user_profiles.uid')
                  ->orderBy('user_profiles.address_direction', $sorting_direction);
        } else {
            $query->orderBy($sorting_id, $sorting_direction);
        }

        // Fetch paginated data
        $schools = $query->paginate($per_page);

        // Response structure
        $returned_data['pagination'] = [
            'currentPage' => $schools->currentPage(),
            'perPage' => $schools->perPage(),
            'totalPages' => $schools->lastPage(),
            'totalItems' => $schools->total(),
        ];
        $returned_data['results'] = $schools->items(); // Paginated and filtered data
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }

    public function topSheet(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $ids = $request->get('ids');
        $user_ids = explode(',', $ids);
        $user = PanelUser::where('id', $auth_id)->first();
        $lot_admin = NMSLotAdmin::where('uid', $user->user_id)->first();
        // if (!$lot_admin) {
        //     $returned_data['status'] = 'error';
        //     $returned_data['error_type'] = 'general';
        //     $returned_data['message'] = 'Only lot admin can generate top sheet!';
        //     return ResponseWrapper::End($returned_data);
        // }
        $divisionBn = GeoDivision::where('id', $lot_admin->division_id)->value('bn_name') ?? null;
        $districtBn = GeoDistrict::where('id', $lot_admin->district_id)->value('bn_name') ?? null;

        // Base Query
        $query = SchoolProfile::with([
            'user_profiles:uid,address_direction,union_id,upazila_id',
            'internet_users:uid,connection_status',
            'package:id,price'
        ])
        ->leftJoin('user_profiles AS up', 'up.uid', '=', 'school_profiles.uid') // Join user_profiles
        ->leftJoin('geo_union_pouroshovas AS gd', 'gd.id', '=', 'up.union_id') // Join geo_union_pouroshovas using user_profiles.union_id
        ->leftJoin('geo_upazilas AS gu', 'gu.id', '=', 'up.upazila_id') // Join geo_upazilas using user_profiles.upazila_id
        ->whereIn('school_profiles.uid', $user_ids)
        ->select([
            'school_profiles.id',
            'school_profiles.uid',
            'school_profiles.package_id',
            'school_profiles.school_name',
            'school_profiles.institution_type',
            'school_profiles.connection_code',
            'gd.bn_name as union_name',  // Corrected: Using LEFT JOIN on user_profiles.union_id
            'gu.bn_name as upazila_name', // Corrected: Using LEFT JOIN on user_profiles.upazila_id
            'school_profiles.manager_id',
            'school_profiles.head_teacher_name',
            'school_profiles.head_teacher_mobile',
            'school_profiles.edc_book_sl_no',
            'school_profiles.fiber_length',
            'school_profiles.created_at'
        ]);

        $results = [
            'isp_name' => $lot_admin->lot_isp_name,
            'lot_name' => $lot_admin->name,
            'download_speed' => InternetPackageCorporate::where('id', $lot_admin->package_id)->value('download_speed'),
            'division' => $divisionBn,
            'district' => $districtBn,
            'list' => $query->get()
        ];

        $returned_data['results'] = $results;
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }

    //
    private function fetchUptime($school)
    {
        $mobile = User::where('id',$school->uid)->value('auth_id');
        $mkInfo = SchoolManager::where('uid', $school->manager_id)->first(['mikrotik_ip', 'mikrotik_username', 'mikrotik_password']);
        $API = new RouterOsApi();
        if ($mkInfo && $API->connect($mkInfo->mikrotik_ip, $mkInfo->mikrotik_username, $mkInfo->mikrotik_password)) {
            $connectedItems = $API->comm('/ppp/active/print');
            $matchingItem = collect($connectedItems)->firstWhere('name', $mobile);
            $API->disconnect();
            return $matchingItem['uptime'] ?? null;
        } else {
            Log::error('Failed to connect to Mikrotik for manager: ' . $school->manager_id);
            return null;
        }
    }

    private function fetchStatus($school)
    {
        $mobile = User::where('id',$school->uid)->value('auth_id');
        $mkInfo = SchoolManager::where('uid', $school->manager_id)->first(['mikrotik_ip', 'mikrotik_username', 'mikrotik_password']);
        $API = new RouterOsApi();
        if ($mkInfo && $API->connect($mkInfo->mikrotik_ip, $mkInfo->mikrotik_username, $mkInfo->mikrotik_password)) {
            $ARRAY = $API->comm('/ppp/secret/print');
            $matchingItem = collect($ARRAY)->firstWhere('name', $mobile);
            $API->disconnect();
            return $matchingItem['disabled'] ?? null;
        } else {
            Log::error('Failed to connect to Mikrotik for manager: ' . $school->manager_id);
            return null;
        }
    }

    public function invoice(Request $request): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $ids = $request->get('ids');
        $user_ids = array_filter(explode(',', $ids)); // Corrected: Use array_filter to ensure user_ids is not empty

        if (empty($user_ids)) { // Corrected: Check if user_ids is empty
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'Please select some institutions to generate invoice.';
            return ResponseWrapper::End($returned_data);
        }

        $user = PanelUser::where('id', $auth_id)->first();

        if (!$user) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'User not found.';
            return ResponseWrapper::End($returned_data);
        }

        $lot_admin = NMSLotAdmin::where('uid', $user->user_id)->select([
            'id',
            'uid',
            'proprietor_name',
            'proprietor_mobile',
            'proprietor_email',
            'lot_isp_name',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'bank_branch_address',
            'installation_cost'
        ])->first();

        // if (!$lot_admin) {
        //     $returned_data['status'] = 'error';
        //     $returned_data['error_type'] = 'general';
        //     $returned_data['message'] = 'Only lot admin can generate top sheet!';
        //     return ResponseWrapper::End($returned_data);
        // }

        // Base Query
        $totals = SchoolProfile::whereIn('uid', $user_ids)
        ->selectRaw('
            SUM(fiber_length) as total_fiber_length,
            COUNT(id) as total_connections,
            COUNT(id) as total_router_quantity,
            COUNT(id) as total_onu_quantity,
            COUNT(id) as total_others_quantity,
            SUM(tj_box_quantity) as total_tj_box_quantity,
            SUM(fiber_patch_cord_quantity) as total_fiber_patch_cord_quantity,
            COUNT(id) * ? as total_price
        ', [$lot_admin->installation_cost])
        ->first();


        $results = [
            'lot_admin' => $lot_admin,
            'summary' => $totals
        ];


        $returned_data['results'] = $results;
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }

    // School Store
    public function store(SchoolStoreRequest $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = User::where('auth_id', $request->get('mobile_number'))->exists();
        $lot_id = SchoolManager::where('uid', $request->get('manager_id'))->value('lot_id');

        if($auth_id){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'This number is already in used, Try another!';
            return ResponseWrapper::End($returned_data);
        }

        $hasPermission = PanelUser::where('id',$request->get('auth_id'))->whereIn('base_role',['edc_manager','lot_admin'])->exists();
        if(!$hasPermission){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'You are not permitted!';
            return ResponseWrapper::End($returned_data);
        }

        $userData = (new \App\Classes\CustomHelpers)->create_new_user($request->get('mobile_number'), 'user','broadband');
        $uid = $userData['user']['id'];
        $password = $userData['password'];
        $package = InternetPackageCorporate::where('id', $request->get('package_id'))->value('package_name');

        // create new profile
        $userProfile = new UserProfile();
        $userProfile->uid = $uid;
        $userProfile->full_name = $request->get('name');
        $userProfile->mobile_number = $request->get('mobile_number');
        $userProfile->whatsapp_number = $request->get('whatsapp_number');
        $userProfile->email = $request->get('email');
        // $userProfile->profession = $request->get('profession');
        // $userProfile->nid = $request->get('nid');
        // $userProfile->gender = $request->get('gender');
        $userProfile->division_id = $request->get('division');
        $userProfile->district_id = $request->get('district');
        $userProfile->upazila_id = $request->get('upazila');
        $userProfile->union_id = $request->get('union');
        $userProfile->village_id = $request->get('village');
        // $userProfile->address = $request->get('address');
        $userProfile->address_direction = $request->get('address_direction');
        $userProfile->latitude = $request->get('latitude');
        $userProfile->longitude = $request->get('longitude');
        $userProfile->device_info = json_encode(["brand"=>"erp"]);
        $userProfile->save();

        // data for internet user table --------------------
        $internetUser = new InternetUsers();
        $internetUser->uid = $uid;
        $internetUser->zone_id = $request->get('auth_id');
        // $internetUser->agent_id = $agent;
        // $internetUser->sub_agent_id = $client_id;
        $internetUser->added_by = $request->get('auth_id');
        $internetUser->package_id = $request->get('package_id');
        $internetUser->package_type = 'broadband';
        $internetUser->latitude = $request->get('latitude');
        $internetUser->longitude = $request->get('longitude');
        $internetUser->password = $password;
        $internetUser->user_type = 'school';
        $internetUser->billing_address = $request->get('address_direction');
        $internetUser->broadband_pop_id = $request->get('pop_id');
        // $internetUser->connection_media = $request->get('connection_media');
        // $internetUser->installation_charge = $request->get('ins_cost');
        $internetUser->connection_status = 'pending';
        $internetUser->save();

        $lot_user_id = PanelUser::where('id', $lot_id)->value('user_id');
        $installation_cost = NMSLotAdmin::where('uid', $lot_user_id)->value('installation_cost');

        $schoolProfile = new SchoolProfile();
        $schoolProfile->uid = $uid;
        $schoolProfile->manager_id = $request->get('manager_id');
        $schoolProfile->lot_id = $lot_id;
        $schoolProfile->institution_type = $request->get('institution_type');
        $schoolProfile->school_name = $request->get('name');
        $schoolProfile->connection_code = $request->get('connection_code');
        $schoolProfile->edc_book_sl_no = $request->get('edc_book_sl_no');
        $schoolProfile->package_id = $request->get('package_id');
        $schoolProfile->electricity = $request->get('electricity');
        $schoolProfile->area_code = $request->get('area_code');
        $schoolProfile->dis_code = $request->get('dis_code');
        $schoolProfile->emis_code = $request->get('emis_code');
        $schoolProfile->head_teacher_name = $request->get('head_teacher_name');
        $schoolProfile->head_teacher_mobile = $request->get('head_teacher_mobile');
        $schoolProfile->head_teacher_ast_name = $request->get('assistant_name');
        $schoolProfile->head_teacher_ast_mobile = $request->get('assistant_mobile');
        $schoolProfile->fiber_id = $request->get('fiber_id');
        $schoolProfile->fiber_core = $request->get('fiber_core');
        $schoolProfile->db_signal = $request->get('db_signal');
        $schoolProfile->start_meter = $request->get('fiber_start_meter');
        $schoolProfile->end_meter = $request->get('fiber_end_meter');
        $schoolProfile->fiber_length = $request->get('fiber_length');
        $schoolProfile->onu_mac = $request->get('onu_mac');
        $schoolProfile->router_username = $request->get('router_login_username');
        $schoolProfile->router_password = $request->get('router_login_password');
        $schoolProfile->router_mac = $request->get('router_login_mac');
        $schoolProfile->router_remote_magt_port = $request->get('router_remote_management_port');
        $schoolProfile->gateway = $request->get('gateway');
        $schoolProfile->subnet_mask = $request->get('subnet_mask');
        $schoolProfile->dnsv4_primary = $request->get('dnsv4_primary');
        $schoolProfile->dnsv4_secondary = $request->get('dnsv4_secondary');
        $schoolProfile->ipv4_ip = $request->get('ipv4_ip');
        $schoolProfile->ipv6_ip = $request->get('ipv6_ip');
        $schoolProfile->snmp_com = $request->get('snmp_com');
        $schoolProfile->slaac_enabled = $request->get('slaac_enabled');
        $schoolProfile->icmp_enabled = $request->get('icmp_enabled');
        $schoolProfile->router_model = $request->get('router_model');
        $schoolProfile->router_serial = $request->get('router_serial_number');
        $schoolProfile->tj_box_quantity = $request->get('tj_box_quantity');
        $schoolProfile->tj_box_remarks = $request->get('tj_box_remarks');
        $schoolProfile->fiber_patch_cord_quantity = $request->get('fiber_patch_cord_quantity');
        $schoolProfile->fiber_patch_cord_remarks = $request->get('fiber_patch_cord_remarks');
        $schoolProfile->installation_cost = $installation_cost;
        $schoolProfile->status = 'pending';
        $schoolProfile->comments = $request->get('comments');
        $schoolProfile->others = $request->get('others');
        $schoolProfile->updated_by = $request->get('auth_id');
        $schoolProfile->save();

        $schoolLatLong = new SchoolLatLong();
        $schoolLatLong->uid = $uid;
        $schoolLatLong->manager_id = $request->get('manager_id');
        $schoolLatLong->lot_id = $lot_id;
        $schoolLatLong->institution_type = $request->get('institution_type');
        $schoolLatLong->division_id = $request->get('division');
        $schoolLatLong->district_id = $request->get('district');
        $schoolLatLong->upazila_id = $request->get('upazila');
        $schoolLatLong->union_id = $request->get('union');
        $schoolLatLong->latitude = $request->get('latitude');
        $schoolLatLong->longitude = $request->get('longitude');
        $schoolLatLong->status = 'pending';
        $schoolLatLong->save();

        $managerData = SchoolManager::where('uid', $request->get('manager_id'))->first();

        // API Variables
        $ipAddr = $managerData->mikrotik_ip;
        $mkUser = $managerData->mikrotik_username;
        $mkPass = $managerData->mikrotik_password;
        $API = new RouterOsApi();

        // Connect to MikroTik Router
        // if ($API->connect($ipAddr, $mkUser, $mkPass)) {
        //     $ARRAY = $API->comm('/ppp/secret/add', array('name' => $request->get('mobile_number'), 'password' => $password, 'service' => 'pppoe', 'profile' => $package));
        //     $API->disconnect();
        // } else {
        //     $returned_data['status'] = 'error';
        //     $returned_data['error_type'] = 'general';
        //     $returned_data['message'] = 'Failed to connect MikroTik Router!';
        //     return ResponseWrapper::End($returned_data);
        // }

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'School added successfully!';
        $returned_data['results'] = $uid;
        return ResponseWrapper::End($returned_data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $query = SchoolProfile::with(['user_profiles','package:id,package_name,price,upload_speed,download_speed','internet_users:uid,broadband_pop_id,connection_status','manager:uid,full_name'])->where('uid',$id)->get();
        foreach ($query as $schoolProfile) {
            $schoolProfile->setAttribute('division', GeoDivision::where('id', $schoolProfile->user_profiles->division_id)->value('en_name'));
            $schoolProfile->setAttribute('district', GeoDistrict::where('id', $schoolProfile->user_profiles->district_id)->value('en_name'));
            $schoolProfile->setAttribute('upazila', GeoUpazila::where('id', $schoolProfile->user_profiles->upazila_id)->value('en_name'));
            $schoolProfile->setAttribute('union', GeoUnionPouroshova::where('id', $schoolProfile->user_profiles->union_id)->value('en_name'));
            $schoolProfile->setAttribute('village', GeoVillage::where('id', $schoolProfile->user_profiles->village_id)->value('en_name'));
            $pop_id = TransPop::where('pop_sl_no', $schoolProfile->internet_users->broadband_pop_id)->value('id');
            $popLocation = TransLatLong::where('trans_id', $pop_id)->first();
            $schoolProfile->setAttribute('pop_lat', $popLocation->latitude);
            $schoolProfile->setAttribute('pop_long', $popLocation->longitude);
        }
        $returned_data['status'] = 'success';
        $returned_data['results'] = $query;
        $returned_data['message'] = '';
        return ResponseWrapper::End($returned_data);
    }

    public function update(SchoolUpdateRequest $request, $id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $hasPermission = PanelUser::where('id',$request->get('auth_id'))->whereIn('base_role',['edc_manager','lot_admin'])->exists();
        if(!$hasPermission){
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'You are not permitted!';
            return ResponseWrapper::End($returned_data);
        }

        // ----------------------------------------------------------------
        // update user profile table --------------------------------------
        // ----------------------------------------------------------------
        $userProfile = UserProfile::where('uid', $id)->first();
        if (!$userProfile) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'User profile not found!';
            return ResponseWrapper::End($returned_data);
        }
        $userProfile->update([
            'full_name' => $request->get('name'),
            'mobile_number' => $request->get('mobile_number'),
            'whatsapp_number' => $request->get('whatsapp_number'),
            'email' => $request->get('email'),
            'division_id' => $request->get('division'),
            'district_id' => $request->get('district'),
            'upazila_id' => $request->get('upazila'),
            'union_id' => $request->get('union'),
            'village_id' => $request->get('village'),
            'address_direction' => $request->get('address_direction'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'device_info' => json_encode(["brand"=>"erp"]),
        ]);

        // ----------------------------------------------------------------
        // update internet user table -------------------------------------
        // ----------------------------------------------------------------
        $internetUser = InternetUsers::where('uid', $id)->first();
        if (!$internetUser) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'Internet user not found!';
            return ResponseWrapper::End($returned_data);
        }
        $internetUser->update([
            'zone_id' => $request->get('auth_id'),
            'added_by' => $request->get('auth_id'),
            'package_id' => $request->get('package_id'),
            'package_type' => 'broadband',
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
            'user_type' => 'school',
            'billing_address' => $request->get('address_direction'),
            'broadband_pop_id' => $request->get('pop_id'),
            // 'connection_status' => 'active',
        ]);

        // ----------------------------------------------------------------
        // update school profile table ------------------------------------
        // ----------------------------------------------------------------
        $schoolProfile = SchoolProfile::where('uid', $id)->first();
        if (!$schoolProfile) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'School profile not found!';
            return ResponseWrapper::End($returned_data);
        }
        $schoolProfile->update([
            'school_name' => $request->get('name'),
            'connection_code' => $request->get('connection_code'),
            'edc_book_sl_no' => $request->get('edc_book_sl_no'),
            'package_id' => $request->get('package_id'),
            'electricity' => $request->get('electricity'),
            'area_code' => $request->get('area_code'),
            'dis_code' => $request->get('dis_code'),
            'emis_code' => $request->get('emis_code'),
            'head_teacher_name' => $request->get('head_teacher_name'),
            'head_teacher_mobile' => $request->get('head_teacher_mobile'),
            'head_teacher_ast_name' => $request->get('assistant_name'),
            'head_teacher_ast_mobile' => $request->get('assistant_mobile'),
            'fiber_id' => $request->get('fiber_id'),
            'fiber_core' => $request->get('fiber_core'),
            'db_signal' => $request->get('db_signal'),
            'start_meter' => $request->get('fiber_start_meter'),
            'end_meter' => $request->get('fiber_end_meter'),
            'fiber_length' => $request->get('fiber_length'),
            'onu_mac' => $request->get('onu_mac'),
            'router_username' => $request->get('router_login_username'),
            'router_password' => $request->get('router_login_password'),
            'router_mac' => $request->get('router_login_mac'),
            'router_remote_magt_port' => $request->get('router_remote_management_port'),
            'gateway' => $request->get('gateway'),
            'subnet_mask' => $request->get('subnet_mask'),
            'dnsv4_primary' => $request->get('dnsv4_primary'),
            'dnsv4_secondary' => $request->get('dnsv4_secondary'),
            'ipv4_ip' => $request->get('ipv4_ip'),
            'ipv6_ip' => $request->get('ipv6_ip'),
            'snmp_com' => $request->get('snmp_com'),
            'slaac_enabled' => $request->get('slaac_enabled'),
            'icmp_enabled' => $request->get('icmp_enabled'),
            'router_model' => $request->get('router_model'),
            'router_serial' => $request->get('router_serial_number'),
            'tj_box_quantity' => $request->get('tj_box_quantity'),
            'tj_box_remarks' => $request->get('tj_box_remarks'),
            'fiber_patch_cord_quantity' => $request->get('fiber_patch_cord_quantity'),
            'fiber_patch_cord_remarks' => $request->get('fiber_patch_cord_remarks'),
            'comments' => $request->get('comments'),
            'others' => $request->get('others'),
            'updated_by' => $request->get('auth_id'),
        ]);

        // ----------------------------------------------------------------
        // update school map table --------------------------------------
        // ----------------------------------------------------------------
        $schoolLatLong = SchoolLatLong::updateOrCreate(
            ['uid' => $id],
            [
                'division_id' => $request->get('division'),
                'district_id' => $request->get('district'),
                'upazila_id' => $request->get('upazila'),
                'union_id' => $request->get('union'),
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
                // 'status' => $request->get('status'),
            ]
        );

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'School profile updated successfully!';
        $returned_data['results'] = $id;
        return ResponseWrapper::End($returned_data);
    }

    // Delete School Infos -----
    public function delete($id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        SchoolLatLong::where('uid', $id)->delete();
        SchoolProfile::where('uid', $id)->delete();
        InternetUsers::where('uid', $id)->delete();
        UserProfile::where('uid', $id)->delete();
        User::where('id', $id)->delete();

        $returned_data['status']  = 'success';
        $returned_data['message'] = "School Deleted Successfully!";

        return ResponseWrapper::End($returned_data);
    }

    public function bulkUploadSchoolInfo(Request $request) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Validate file upload
        $validated = $request->validate([
            'file' => 'required|mimes:xlsx',
        ], [
            'file.required' => 'Please upload a file.',
            'file.mimes' => 'The file must be a valid XLSX file.',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }
        // Handle the file upload and processing
        try {
            Excel::import(new SchoolInfoImport, $request->file('file'));

            $returned_data['status'] = 'success';
            $returned_data['message'] = 'Data imported successfully!';
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }

    public function statusUpdate(Request $request, $id): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        // Check manager permission
        $hasPermission = PanelUser::where('id', $request->get('auth_id'))->whereIn('base_role', ['edc_manager','lot_admin'])->exists();
        if (!$hasPermission) {
            $returned_data['status'] = 'error';
            $returned_data['error_type'] = 'general';
            $returned_data['message'] = 'You are not permitted!';
            return ResponseWrapper::End($returned_data);
        }

        // Fetch required data
        $internetUser = InternetUsers::where('uid', $id)->first(['id','connection_status']);
        $schoolProfile = SchoolProfile::where('uid', $id)->first(['id','status', 'package_id','manager_id']);
        $userData = User::where('id', $id)->first(['id','auth_id', 'password']);
        $managerData = SchoolManager::where('uid', $schoolProfile->manager_id)->first(['id','mikrotik_ip', 'mikrotik_username', 'mikrotik_password']);

        // if (!$internetUser || !$schoolProfile || !$userData || !$managerData) {
        if (!$internetUser || !$schoolProfile || !$userData) {
            $returned_data['error_type'] = 'general';
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Data not found!';
            return ResponseWrapper::End($returned_data);
        }

        // MikroTik API variables
        $ipAddr = $managerData->mikrotik_ip;
        $mkUser = $managerData->mikrotik_username;
        $mkPass = $managerData->mikrotik_password;
        $package = InternetPackageCorporate::where('id', $schoolProfile->package_id)->value('package_name');

        // $API = new RouterOsApi();

        // // Connect to MikroTik Router
        // if (!$API->connect($ipAddr, $mkUser, $mkPass)) {
        //     $returned_data['error_type'] = 'general';
        //     $returned_data['status'] = 'error';
        //     $returned_data['message'] = 'Failed to connect to MikroTik Router!';
        //     return ResponseWrapper::End($returned_data);
        // }

        // // Handle MikroTik API calls
        // try {
        //     if ($internetUser->connection_status === 'pending') {
        //         $API->comm('/ppp/secret/add', [
        //             'name' => $userData->auth_id,
        //             'password' => $userData->password,
        //             'service' => 'pppoe',
        //             'profile' => $package,
        //         ]);
        //     } elseif ($internetUser->connection_status === 'active') {
        //         $arrID = $API->comm("/ppp/secret/print", [".proplist" => ".id", "?name" => $userData->auth_id]);
        //         $API->comm("/ppp/secret/enable", [".id" => $arrID[0][".id"]]);
        //     } elseif ($internetUser->connection_status === 'inactive') {
        //         $arrID = $API->comm("/ppp/secret/print", [".proplist" => ".id", "?name" => $userData->auth_id]);
        //         $API->comm("/ppp/secret/disable", [".id" => $arrID[0][".id"]]);
        //         $id = $API->comm("/ppp/active/getall", [".proplist" => ".id", "?name" => $userData->auth_id]);
        //         $API->comm("/ppp/active/remove", [".id" => $id[0][".id"]]);
        //     }
        // } catch (\Exception $e) {
        //     $returned_data['error_type'] = 'general';
        //     $returned_data['status'] = 'error';
        //     $returned_data['message'] = 'MikroTik API error!';
        //     return ResponseWrapper::End($returned_data);
        // } finally {
        //     $API->disconnect();
        // }

        // Update statuses
        $internetUser->connection_status = $internetUser->connection_status === 'active' ? 'inactive' : 'active';
        $internetUser->update();

        $schoolProfile->status = $schoolProfile->status === 'active' ? 'inactive' : 'active';
        $schoolProfile->update();

        $returned_data['status'] = 'success';
        $returned_data['message'] = 'School profile status changed successfully!';
        $returned_data['results'] = $id;
        return ResponseWrapper::End($returned_data);
    }

    public function getBandwidthUsage(Request $request, $uid): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $intUser = SchoolProfile::where('uid', $uid)->first();
        $managerData = SchoolManager::where('uid', $intUser->manager_id)->first(['id','mikrotik_ip', 'mikrotik_username', 'mikrotik_password']);

        // MikroTik API variables
        $ipAddr = $managerData->mikrotik_ip;
        $mkUser = $managerData->mikrotik_username;
        $mkPass = $managerData->mikrotik_password;
        $interface = User::where('id',$uid)->value('auth_id');

        try {
            $result = $this->fetchBandwidthUsage($ipAddr, $mkUser, $mkPass, $interface);
            if ($result['status'] === 'success') {
                $returned_data['status'] = 'success';
                $returned_data['results'] = $result['data'];
            } else {
                $returned_data['status'] = 'error';
                $returned_data['message'] = $result['message'];
            }
        } catch (\Exception $e) {
            $returned_data['status'] = 'error';
            $returned_data['message'] = $e->getMessage();
        }

        return ResponseWrapper::End($returned_data);
    }

    /**
     * Fetch bandwidth usage from MikroTik Router.
     */
    private function fetchBandwidthUsage(string $ip, string $user, string $password, string $interface): array
    {
        $API = new RouterosAPI();

        if (!$API->connect($ip, $user, $password)) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to the MikroTik Router!',
            ];
        }

        $API->write('/interface/monitor-traffic', false);
        $API->write('=interface=<pppoe-' .$interface.'>', false);
        $API->write('=once=', true);

        $READ = $API->read(false);
        $ARRAY = $API->parseResponse($READ);
        $API->disconnect();

        if (count($ARRAY) > 0) {
            $rx = $ARRAY[0]['rx-bits-per-second'] ?? 0;
            $tx = $ARRAY[0]['tx-bits-per-second'] ?? 0;

            return [
                'status' => 'success',
                'data' => [
                    'interface' => $interface,
                    'rx_rate' => round($rx/1024) . ' Kbps',
                    'tx_rate' => round($tx/1024) . ' Kbps',
                    'rx' => round($rx/1024),
                    'tx' => round($tx/1024),
                    'timestamp' => Carbon::now()->toDateTimeString(),
                ],
            ];
        }

        return [
            'status' => 'error',
            'message' => $ARRAY['!trap'][0]['message'] ?? 'No data available',
        ];
    }

    // Get Transmission Customer By radiation ---
    public function getSchoolInfoSarkarPopDistance($latitude, $longitude, $radiation) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $radiusInMeter = $radiation / 1000;
        $nearestBranch = TransLatLong::select(DB::raw("
            trans_lat_longs.trans_id,
            (SELECT company_id FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as company_id,
            (SELECT company_name FROM trans_companies AS tc WHERE tc.id = company_id) as company_name,
            (SELECT pop_code FROM trans_pops AS tp WHERE tp.id = trans_lat_longs.trans_id) as pop_code,
            (SELECT tj_box_code FROM trans_tj_boxes AS tj WHERE tj.id = trans_lat_longs.trans_id) as tj_code,
            (SELECT loop_code FROM trans_loops AS lo WHERE lo.id = trans_lat_longs.trans_id) as loop_code,
            (SELECT customer_name FROM trans_customers AS tcu WHERE tcu.id = trans_lat_longs.trans_id) as customer_name,
            trans_lat_longs.module_type,
            trans_lat_longs.latitude,
            trans_lat_longs.longitude,
            ROUND((6371 * acos(cos(radians('$latitude')) * cos(radians(latitude)) * cos(radians(longitude) - radians('$longitude')) + sin(radians('$latitude')) * sin(radians(latitude)))), 2) AS distance
        "))
        ->where('module_type', 'info_sarkar_pop')
        ->havingRaw('distance < ?', [$radiusInMeter])
        ->orderBy('distance')
        ->first();

        $data = [
            'nearest_branch' => $nearestBranch,
        ];

        // success response
        $returned_data['status'] = 'success';
        $returned_data['results'] = $data;
        return ResponseWrapper::End($returned_data);
    }

    public function schoolMap(Request $request, $auth_id, $type) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $user = PanelUser::where('id', $auth_id)->first();
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $status = $request->get('status');
        $skip = $request->get('skip', 0);
        $limit = $request->get('limit', 100);

        $query = SchoolLatLong::with(['school_profile:uid,school_name,status']);

        if($user->base_role === 'edc_manager'){
            $manager = SchoolManager::where('uid', $user->user_id)->first();
            $query = $query->where('lot_id', $manager->lot_id)->where('institution_type', $type);
            if ($manager) {
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
        } elseif($user->base_role === 'lot_admin'){
            $query = $query->where('lot_id', $auth_id)->where('institution_type', $type);
        } else {
            $query = $query->where('institution_type', $type);
        }

        // Apply filters
        if ($division_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
        }
        if ($district_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
        }
        if ($upazila_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
        }
        if ($union_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
        }
        if ($status) {
            $query->whereHas('school_profiles', function ($subQuery) use ($status) {
                $subQuery->where('status', $status);
            });
        }

        $query = $query->skip($skip)->limit($limit);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query->get();
        return ResponseWrapper::End($returned_data);
    }

    public function schoolAllMap(Request $request, $auth_id) : JsonResponse
    {
        $returned_data = ResponseWrapper::Start();
        $user = PanelUser::where('id', $auth_id)->first();
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $status = $request->get('status');
        $skip = $request->get('skip', 0);
        $limit = $request->get('limit', 100);
        $institutionTypes = explode(',', $request->get('institution_types'));
        $lot_id = $request->get('lot_id');

        $query = SchoolLatLong::with(['school_profile:uid,school_name,status']);

        $query = $query->whereIn('institution_type', $institutionTypes);

        if($lot_id) {
            $lotAdmin = PanelUser::where('user_id', $lot_id)->first();
            $query = $query->where('lot_id', $lotAdmin->id);
        }

        if($user->base_role === 'edc_manager'){
            $manager = SchoolManager::where('uid', $user->user_id)->first();
            $query = $query->where('lot_id', $manager->lot_id);
            if ($manager) {
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
        } elseif($user->base_role === 'lot_admin'){
            $query = $query->where('lot_id', $auth_id);
        }

        // Apply filters
        if ($division_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
        }
        if ($district_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
        }
        if ($upazila_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
        }
        if ($union_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
        }
        if ($status) {
            $query->whereHas('school_profiles', function ($subQuery) use ($status) {
                $subQuery->where('status', $status);
            });
        }

        $query = $query->skip($skip)->limit($limit);

        $returned_data['status'] = 'success';
        $returned_data['message'] = '';
        $returned_data['results']['list'] = $query->get();
        return ResponseWrapper::End($returned_data);
    }

    public function schoolFiberLengthList(Request $request, $type): JsonResponse
    {
        $returned_data = ResponseWrapper::Start();

        $auth_id = $request->get('auth');
        $manager = SchoolManager::where('uid', $auth_id)->first();
        $division_id = $request->get('division');
        $district_id = $request->get('district');
        $upazila_id = $request->get('upazila');
        $union_id = $request->get('union');
        $status = $request->get('status');
        $lot_id = $request->get('lot_id');
        $per_page = (int) $request->get('per_page') ?? 10;
        $search = strtolower(trim($request->get('search')));
        $sorting_id = $request->get('sorting_id') ?? 'created_at';
        $sorting_direction = strtoupper($request->get('sorting_direction') ?? 'DESC');

        // Base Query
        $query = SchoolProfile::with([
            'internet_users:uid,connection_status,broadband_pop_id',
            'panel_lot_admin:id,user_id',
            'panel_lot_admin.lot_admin:uid,id,name'
        ])->select([
            'school_profiles.id', 'school_profiles.uid', 'school_profiles.school_name', 'school_profiles.fiber_length', 'school_profiles.lot_id',
        ]);


        $user = PanelUser::where('id', $auth_id)->first();

        if($user){
            if($user->base_role === 'lot_admin'){

                $query = $query->where('school_profiles.lot_id', $auth_id);

            } else if($user->base_role === 'edc_manager'){

                $manager = SchoolManager::where('uid', $user->user_id)->first();
                $query = $query->where('lot_id', $manager->lot_id);

                if (!empty($manager->assigned_union_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('union_id', $manager->assigned_union_id);
                    });
                } elseif (!empty($manager->assigned_upazila_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('upazila_id', $manager->assigned_upazila_id);
                    });
                } elseif (!empty($manager->assigned_district_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('district_id', $manager->assigned_district_id);
                    });
                } elseif (!empty($manager->assigned_division_id)) {
                    $query->whereHas('user_profiles', function ($subQuery) use ($manager) {
                        $subQuery->where('division_id', $manager->assigned_division_id);
                    });
                }
            }
        }

        if ($type) {
            $query->where('institution_type', $type);
        }

        // Apply filters
        if ($division_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('division_id', $division_id));
        }
        if ($district_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('district_id', $district_id));
        }
        if ($upazila_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('upazila_id', $upazila_id));
        }
        if ($union_id) {
            $query->whereHas('user_profiles', fn($subQuery) => $subQuery->where('union_id', $union_id));
        }
        if ($status) {
            $query->whereHas('internet_users', function ($subQuery) use ($status) {
                $subQuery->where('connection_status', $status);
            });
        }
        if ($lot_id) {
            $query->whereHas('panel_lot_admin', fn($subQuery) => $subQuery->where('id', $lot_id));
        }
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('school_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('user_profiles', fn($subSubQuery) => $subSubQuery->where('address_direction', 'LIKE', "%{$search}%"))
                    ->orWhereHas('internet_users', fn($subSubQuery) => $subSubQuery->where('broadband_pop_id', 'LIKE', "%{$search}%"))
                    ->orWhereHas('internet_users', fn($subSubQuery) => $subSubQuery->where('connection_status', 'LIKE', "%{$search}%"));
            });
        }

        // Sorting logic
        if ($sorting_id === 'address_direction') {
            $query->join('user_profiles', 'school_profiles.uid', '=', 'user_profiles.uid')
                  ->orderBy('user_profiles.address_direction', $sorting_direction);
        } else {
            $query->orderBy($sorting_id, $sorting_direction);
        }

        $totalFiberLength = (clone $query)->sum('fiber_length');

        // Fetch paginated data
        $schools = $query->paginate($per_page);

        // Response structure
        $returned_data['pagination'] = [
            'currentPage' => $schools->currentPage(),
            'perPage' => $schools->perPage(),
            'totalPages' => $schools->lastPage(),
            'totalItems' => $schools->total(),
        ];
        $returned_data['results'] = $schools->items(); // Paginated and filtered data
        $returned_data['total_fiber_length'] = $totalFiberLength;
        $returned_data['status'] = 'success';

        return ResponseWrapper::End($returned_data);
    }
}
