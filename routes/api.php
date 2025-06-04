<?php

use App\Http\Controllers\apps\AppDashboardController;
use App\Http\Controllers\apps\AppsAccountController;
use App\Http\Controllers\apps\AppsAffiliateUserRegistrationController;
use App\Http\Controllers\apps\AppsInternetUsersController;
use App\Http\Controllers\apps\AppsNetworkSupportCenterController;
use App\Http\Controllers\apps\AppsSalesAgentController;
use App\Http\Controllers\apps\AppsSalesPointController;
use App\Http\Controllers\apps\AppsWelcomeScreenController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\BroadbandCheckoutURLController;
use App\Http\Controllers\CareerResumeController;
use App\Http\Controllers\CheckoutURLController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CorporateAgentController;
use App\Http\Controllers\CorporateClientController;
use App\Http\Controllers\CorporateInternetUsersController;
use App\Http\Controllers\CorporateSettingsController;
use App\Http\Controllers\CorporateSubAgentController;
use App\Http\Controllers\CorporateStallController;
use App\Http\Controllers\CustomerSupportRequestController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EnBnValueListController;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\graph\GraphBroadbandUserController;
use App\Http\Controllers\graph\GraphInternetUserController;
use App\Http\Controllers\graph\GraphSalesAgentUserController;
use App\Http\Controllers\graph\GraphSalesPointUserController;
use App\Http\Controllers\graph\GraphSupportCenterUserController;
use App\Http\Controllers\graph\GraphWifiUserController;
use App\Http\Controllers\HotspotCheckoutURLController;
use App\Http\Controllers\InternetPackageController;
use App\Http\Controllers\InternetPackageCorporateController;
use App\Http\Controllers\MessageAndNotificationController;
use App\Http\Controllers\mikrotik\MikrotikController;
use App\Http\Controllers\panel\employee\EmployeeDesignationController;
use App\Http\Controllers\panel\internet_user\PanelBranchInfoController;
use App\Http\Controllers\panel\internet_user\PanelIspBusinessCenterController;
use App\Http\Controllers\panel\internet_user\PanelInternetUserController;
use App\Http\Controllers\panel\internet_user\PanelPermissionController;
use App\Http\Controllers\panel\internet_user\PanelSalesAgentController;
use App\Http\Controllers\panel\internet_user\PanelSalesPointController;
use App\Http\Controllers\panel\internet_user\PanelSupportNetworkCenterController;
use App\Http\Controllers\panel\isp_client\AgentController;
use App\Http\Controllers\panel\isp_client\ISPBroadbandInternetUserController;
use App\Http\Controllers\panel\isp_client\ISPHotspotInternetUserController;
use App\Http\Controllers\panel\isp_client\ISPHotspotInternetUserBkashTokenize;
use App\Http\Controllers\panel\isp_client\ISPSettingsController;
use App\Http\Controllers\panel\isp_client\ISPTopUpController;
use App\Http\Controllers\panel\isp_client\SubAgentController;
use App\Http\Controllers\panel\location\GeoController;
use App\Http\Controllers\panel\location\GeoDistrictController;
use App\Http\Controllers\panel\location\GeoDivisionController;
use App\Http\Controllers\panel\location\GeoUnionPouroshovaController;
use App\Http\Controllers\panel\location\GeoUpazilaController;
use App\Http\Controllers\panel\location\GeoVillageController;
use App\Http\Controllers\panel\package\PackageController;
use App\Http\Controllers\panel\PanelController;
use App\Http\Controllers\panel\user\UserController;
use App\Http\Controllers\panel\user\BroadbandUserRegistrationController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPurchaseRequestsController;
use App\Http\Controllers\radius\RadiusServerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransmissionController;
use App\Http\Controllers\transmission\TransBackboneJoinTjBoxController;
use App\Http\Controllers\transmission\TransBackboneTjBoxController;
use App\Http\Controllers\transmission\TransBranchPopController;
use App\Http\Controllers\transmission\TransCommonController;
use App\Http\Controllers\transmission\TransCompanyController;
use App\Http\Controllers\transmission\TransCustomerController;
use App\Http\Controllers\transmission\TransCustomerTjBoxController;
use App\Http\Controllers\transmission\TransDistributionLoopController;
use App\Http\Controllers\transmission\TransDistributionTjBoxController;
use App\Http\Controllers\transmission\TransJoiningTjBoxController;
use App\Http\Controllers\transmission\TransLoopController;
use App\Http\Controllers\transmission\TransNTTNPopController;
use App\Http\Controllers\transmission\TransReservedLoopController;
use App\Http\Controllers\transmission\TransSubBranchPopController;
use App\Http\Controllers\transmission\TransTjBoxController;
use App\Http\Controllers\transmission\TransGeoJsonController;
use App\Http\Controllers\transmission\TransInfoSarkarPopController;
use App\Http\Controllers\UpayPaymentController;
use App\Http\Controllers\WalletPointPaymentsController;
use App\Http\Controllers\web\WebsiteController;
use App\Http\Controllers\panel\package\ShadhinPackageController;
use App\Http\Controllers\panel\internet_user\PlexusInternetUserController;
use App\Http\Controllers\panel\shadhin_client\ShadhinHotspotClientController;
use App\Http\Controllers\panel\shadhin_client\ShadhinBroadbandClientController;
use App\Http\Controllers\panel\shadhin_client\ShadhinBroadbandCheckoutURLController;
use App\Http\Controllers\panel\shadhin_client\ShadhinHotspotCheckoutURLController;
use App\Http\Controllers\school\SchoolManagerController;
use App\Http\Controllers\school\SchoolProfileController;
use App\Http\Controllers\school\NMSCommonController;
use App\Http\Controllers\school\NMSLotAdminController;
use App\Http\Controllers\school\NMSCategoryBasedAdminController;
use App\Http\Controllers\school\NMSGeoJsonController;
use App\Http\Controllers\SslCommerzBroadbandPaymentController;
use App\Http\Controllers\SslCommerzHotspotPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->group(function () {

    Route::get('/hello/parvez',function(){
        return "Hello Parvez";
    });

    // Broadband Checkout URL ---------
    Route::controller(BroadbandCheckoutURLController::class)->group(function(){
        Route::post('/broadband/payment/bkash/create','create');
        Route::get('/broadband/payment/bkash/callback','callback');
        Route::get('/broadband/payment/bkash/refund','refund');
    });

    // Hotspot Checkout URL ---------
    Route::controller(HotspotCheckoutURLController::class)->group(function(){
        Route::post('/hotspot/payment/bkash/create','create');
        Route::get('/hotspot/payment/bkash/callback','callback');
        Route::get('/hotspot/payment/bkash/refund','refund');
    });

    //  Graphs =====
    Route::controller(GraphInternetUserController::class)->group(function(){
        Route::get('/graph/internet/user','barchart');
        Route::get('/graph/internet/user/{uid}/{days}/{type}','broadbandUserLineChart');
        Route::get('/graph/internet/user/{days}/{type}','internetUserLineChart');
        Route::get('/graph/active/internet/user/{days}/{type}','activeInternetUserLineChart');
        Route::get('/{branch_id}/current/month/total/payment', 'totalPaymentCurrentMonth');
        Route::get('/{branch_id}/gross/total/payment', 'grossTotalPayment');

        // summaries
        Route::get('/total/internet/user/summary/daily/{zone_id}/{days}', 'internetUserDayWiseSummary')->middleware('auth:sanctum');
        Route::get('/total/internet/user/summary/monthly/{zone_id}/{months}', 'internetUserMonthWiseSummary')->middleware('auth:sanctum');
        Route::get('/total/internet/user/payments/summary/daily/{zone_id}/{days}', 'internetUserPaymentsDayWiseSummary')->middleware('auth:sanctum');
        Route::get('/total/internet/user/payments/summary/monthly/{zone_id}/{months}', 'internetUserPaymentsMonthWiseSummary')->middleware('auth:sanctum');
        Route::get('/total/internet/user/receivable/payments/summary/monthly/{zone_id}/{months}', 'internetUserReceivablePaymentsMonthWiseSummary');
        Route::get('/total/internet/user/monthly/record/list/{zone_id}/{from}/{to}', 'internetUserMonthlyRecordList');
        Route::get('/total/internet/user/daily/record/list/{zone_id}/{from}/{to}', 'internetUserDailyRecordList')->middleware('auth:sanctum');
    });

    Route::controller(GraphSupportCenterUserController::class)->group(function(){
        Route::get('/graph/support/center/{days}/{type}','supportCenterLineChart');
        Route::get('/graph/support_center/user','barchart')->middleware('auth:sanctum');
    });

    Route::controller(GraphSalesAgentUserController::class)->group(function(){
        Route::get('/graph/sales/agent/{days}/{type}','salesAgentLineChart');
        Route::get('/graph/sales_agent/user','barchart')->middleware('auth:sanctum');
    });

    Route::controller(GraphSalesPointUserController::class)->group(function(){
        Route::get('/graph/sales/point/{days}/{type}','salesPointLineChart');
        Route::get('/graph/sales_point/user','barchart')->middleware('auth:sanctum');
    });


    // Broadband Graphs =====
    Route::controller(GraphBroadbandUserController::class)->group(function(){
        Route::get('/graph/{branch_id}/broadband_total/user','broadbandUserBarChart');
        Route::get('/graph/{branch_id}/total/broadband/payment','broadbandTotalPaymentBarChart');
        Route::get('/{branch_id}/total/broadband/user','broadbandTotalUser');
        Route::get('/{branch_id}/total/broadband/payment','broadbandTotalPayment');
    });

    // Wi-Fi Graphs =====
    Route::controller(GraphWifiUserController::class)->group(function(){
        Route::get('/graph/{branch_id}/wifi_total/user','wifiUserBarChart');
        Route::get('/graph/{branch_id}/total/wifi/payment','wifiTotalPaymentBarChart');
        Route::get('/graph/{branch_id}/total/wifi/payment/monthly','wifiTotalPaymentMonthWiseLineChart');
        Route::get('/{branch_id}/total/wifi/user','wifiTotalUser');
        Route::get('/{branch_id}/total/wifi/payment','wifiTotalPayment');
    });

    // Shared =====
    Route::get('/shared/geo/division', [GeoDivisionController::class, 'sharedIndex']);
    Route::get('/shared/geo/district', [GeoDistrictController::class, 'sharedIndex']);
    Route::get('/shared/geo/upazila', [GeoUpazilaController::class, 'sharedIndex']);
    Route::get('/shared/geo/union_pouroshova', [GeoUnionPouroshovaController::class, 'sharedIndex']);
    Route::get('/shared/geo/village', [GeoVillageController::class, 'sharedIndex']);

    Route::controller(GeoController::class)->group(function(){
        Route::get('/shared/geo/total_count','sharedTotalLocationCount');
        Route::get('/shared/get_area_list/{area_type}/{pid}','sharedGetAreaList');
        Route::get('/shared/geo/all_latitude_longitude','getCoverageLatLng');
        Route::get('/user/all_latitude_longitude','getAllLatLong');
        Route::get('/users/get_nearest_zone/{latitude}/{longitude}','getNearestZone');
    });

    Route::get('/shared/service_package_list', [ServiceController::class, 'sharedServicePackageList']);

    Route::controller(InternetPackageController::class)->group(function(){
        Route::get('/shared/internet_package_list','sharedPackageList');
        Route::get('/shared/internet_package_list/{type}','sharedPackageList2');
        Route::get('/shared/internet_package_details/{package_id}','sharedPackageDetails');
        Route::get('/shared/internet_package_list/plexus_cloud/{type}', 'plexusCloudWebsitePackages');
        Route::post('/shared/internet_package/create_update','createUpdatePackage');
        Route::post('/shared/internet_package/delete/{package_id}','deletePackage');
    });

    Route::get('/shared/en_bn_value_list/{type}', [EnBnValueListController::class, 'getList']);

    //=====
    Route::get('/plexus/internet_user/list', [PlexusInternetUserController::class, 'getInternetUserList']);
    Route::post('/plexus/internet_user/register', [PlexusInternetUserController::class, 'webRegisterCorporateInternetUserPlexus']);
    Route::get('/graph/corporate/internet/user/{days}/{type}', [PlexusInternetUserController::class, 'internetUserLineChart']);
    Route::get('/plexus/internet_user/{id}/details', [PlexusInternetUserController::class, 'internetUserDetails']);
    Route::get('/plexus/internet_users_summary', [PlexusInternetUserController::class, 'getInternetUserSummary'])->middleware('auth:sanctum');
    Route::get('/plexus/internet_user/search/{keywords}', [PlexusInternetUserController::class, 'searchInternetUser'])->middleware('auth:sanctum');
    Route::get('/plexus/internet_user/{id}/basic', [PlexusInternetUserController::class, 'getInternetUserBasic'])->middleware('auth:sanctum');
    Route::get('/plexus/internet_user/{auth_id}/details', [PlexusInternetUserController::class, 'internetUserDetailsByNumber'])->middleware('auth:sanctum');
    Route::post('/plexus/internet_user/{id}/status_update', [PlexusInternetUserController::class, 'statusUpdate'])->middleware('auth:sanctum');
    Route::get('/plexus/internet_user/get_all_lat_lng', [PlexusInternetUserController::class, 'getAllLatLong'])->middleware('auth:sanctum');
    Route::get('/plexus/internet_user/internet/user/count', [PlexusInternetUserController::class, 'getInternetUserCount'])->middleware('auth:sanctum');
    
    // External Application
    Route::controller(InternetPackageCorporateController::class)->group(function(){
        Route::get('/corporate/internet_package/list','getPackageList');
        Route::post('/corporate/internet_package/create_update','createUpdatePackage');
        Route::get('/corporate/internet_package/details/{package_id}','getPackageDetails');
        Route::post('/corporate/internet_package/delete/{client_id}/{package_id}','deletePackage');
    });

    Route::controller(CorporateClientController::class)->group(function(){
        Route::get('/corporate/user/role/check/{auth_id}','corporateUserRoleCheck');
        Route::get('/corporate/client/list','getClientList');
        Route::post('/corporate/client/create_update','createUpdateClient');
        Route::get('/corporate/client/details/{auth_id}','getClientDetails');
        Route::post('/corporate/client/delete/{client_id}','deleteClient');
    });

    Route::controller(CorporateInternetUsersController::class)->group(function(){
        Route::get('/corporate/internet_users/list/{client_id}','getInternetUsersList');
        Route::post('/corporate/internet_users/create_update','createUpdateInternetUser');
        Route::get('/corporate/internet_users/details/{auth_id}','getInternetUserDetails');
        Route::post('/corporate/internet_users/delete/{client_id}/{username}','deleteInternetUser');
    });

    Route::get('/corporate/settings/{settings_type}', [CorporateSettingsController::class, 'getMikrotikCredentials']);

    Route::controller(CorporateAgentController::class)->group(function(){
        Route::get('/corporate/agents/{uid}/list','index');
        Route::post('/corporate/agents/store/{user_id}','store');
        Route::post('/corporate/agents/update/{client_id}/{agent_id}','update');
        Route::post('/corporate/agents/update/status/{client_id}/{agent_id}','status');
        Route::post('/corporate/agents/update/password/{client_id}/{agent_id}','password');
        Route::get('/corporate/agents/details/{client_id}/{agent_id}','getAgentDetails');
        Route::post('/corporate/agents/delete/{client_id}/{agent_id}','destroy');
    });

    Route::controller(CorporateSubAgentController::class)->group(function(){
        Route::get('/corporate/sub_agents/{uid}/list','index');
        Route::get('/corporate/sub_agents/{agentId}/list','getByAgentIndex');
        Route::post('/corporate/sub_agent/create_update','createUpdate');
        Route::get('/corporate/sub_agent/details/{id}','getSubAgentDetails');
        Route::get('/corporate/sub_agent/show/{username}','show');
        Route::post('/corporate/sub_agent/delete/{id}','destroy');
    });

    Route::controller(CorporateStallController::class)->group(function(){
        Route::get('/corporate/stall/list','index');
        Route::post('/corporate/stall/create_update','createUpdate');
        Route::get('/corporate/stall/details/{id}','getStallDetails');
        Route::get('/corporate/stall/show/{username}','show');
        Route::post('/corporate/stall/delete/{id}','destroy');
    });


    // User =====
    Route::controller(UserController::class)->group(function(){
        Route::post('/user', 'userData')->middleware('auth:sanctum');
        Route::get('/school/auth/data', 'schoolData')->middleware('auth:sanctum');
        Route::post('/access_check','access_check')->middleware('guest');
        Route::post('/users/registration','user_registration')->middleware('guest');
        Route::post('/users/login','user_login')->middleware('guest');
        Route::post('/panel/users/login','panel_user_login')->middleware('guest');
        Route::post('/panel/users/login/by/username', [UserController::class, 'panel_user_login_by_username'])->middleware('guest');
        Route::post('/users/support_center/login', 'user_login_support_center')->middleware('guest');
        Route::post('/users/password/update','password_update')->middleware('guest');
        Route::post('/users/profile/update','userProfileUpdate')->middleware('guest');
        Route::post('/users/password_recovery','passwordRecovery')->middleware('guest');
        Route::post('/users/check_internet_package_exist','checkInternetPackageExist');
        Route::get('/users/search/{searchType}/{keywords}','searchUserByKeywords')->middleware('auth:sanctum');
        Route::get('/panel/users/list', 'getUsersList')->middleware('auth:sanctum');
        Route::get('/panel/users/{row_id}/profile', 'getUserProfile')->middleware('auth:sanctum');
        Route::get('/panel/users/{uid}/permissions', 'getUserPermissions')->middleware('auth:sanctum');
        Route::post('/panel/users/{uid}/permissions/update', 'userPermissionUpdate')->middleware('auth:sanctum');
        Route::get('/panel/users/{uid}/permission_access', 'getUserPermissionAccess');
        Route::post('/panel/users/{row_id}/assign_as_employee', 'assignAsEmployee')->middleware('auth:sanctum');
        Route::post('/panel/users/send_message_to_user/{sender_uid}/{receiver_uid}', 'sendMessageToUser')->middleware('auth:sanctum');
        Route::get('/get_agent_monthly_commission_rate/{agent_type}/{agent_auth_id}', 'getAgentMonthlyCommissionRate')->middleware('auth:sanctum');
        Route::post('/user/billing/panel/external', 'userDataBillingPanel')->middleware('auth:sanctum');
        Route::get('/panel/users/{uid}/{module_name}/permissions', 'getCorporateUserPermissions')->middleware('auth:sanctum');
        Route::post('/panel/users/zone/assign', 'zoneAssign')->middleware('auth:sanctum');
    });

    // Location =====
    Route::post('/coverage/geo/store/district', [GeoDistrictController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/coverage/geo/store/upazila', [GeoUpazilaController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/coverage/geo/store/union_pouroshova', [GeoUnionPouroshovaController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/coverage/geo/store/village', [GeoVillageController::class, 'store'])->middleware('auth:sanctum');

    Route::controller(GeoController::class)->group(function(){
        Route::post('/coverage/geo/delete/{id}','geoItemDelete')->middleware('auth:sanctum');
        Route::post('/coverage/geo/update/{id}','geoItemUpdate')->middleware('auth:sanctum');
    });

    Route::controller(PanelController::class)->group(function(){
        Route::get('/panel/permissions/page_access','checkPageAccess')->middleware('auth:sanctum');
        Route::post('/panel/update_agents_missing_commission_breakdowns/{month_year}/{agent_type}/{agent_row_id}/{commission_rate}','updateMissingCommissionBreakdowns')->middleware('auth:sanctum');
    });

    // Admin Panel
    Route::get('/panel/message/list/{receiver_id}', [MessageAndNotificationController::class, 'getPanelMessageList'])->middleware('auth:sanctum');

    Route::controller(PanelInternetUserController::class)->group(function(){
        Route::get('/panel/internet_users_summary','getInternetUserSummary')->middleware('auth:sanctum');
        Route::get('/panel/internet_user/list','getInternetUserList');
        Route::get('/panel/internet_user/search/{keywords}','searchInternetUser')->middleware('auth:sanctum');
        Route::get('/panel/internet_user/{id}/basic','getInternetUserBasic')->middleware('auth:sanctum');
        Route::get('/panel/internet_user/{id}/details','internetUserDetails')->middleware('auth:sanctum');
        // Route::get('/panel/internet_user/{auth_id}/details','internetUserDetailsByNumber')->middleware('auth:sanctum');
        Route::post('/panel/internet_user/{id}/status_update','statusUpdate')->middleware('auth:sanctum');
        Route::get('/panel/internet_user/get_all_lat_lng','getAllLatLong')->middleware('auth:sanctum');
        Route::get('/panel/internet_user/internet/user/count','getInternetUserCount')->middleware('auth:sanctum');
    });

    Route::controller(PanelSupportNetworkCenterController::class)->group(function(){
        Route::get('/panel/network_support_center/{center_type}/list/{status}','getCenterListByStatus')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center_summary/{center_type}','getSupportCenterSummary')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/{center_type}/list','getCenterList')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/{center_type}/search/{keyword}','getSearchResultList')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/{id}/basic','getCenterBasic')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/{id}/details','getNetworkSupportCenterDetails')->middleware('auth:sanctum');
        Route::post('/panel/network_support_center/{id}/{employee_id}/status_update','networkSupportCenterStatusUpdate')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/skipped_packages','networkSupportCenterSkippedPackages')->middleware('auth:sanctum');
        Route::post('/panel/network_support_center/package_assign','networkSupportCenterPackageAssign')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/go_next_previous/{type}/{current_id}/{sort_by}/{status}/{district}','goNextPrevious')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/get_all_lat_lng','getAllLatLong')->middleware('auth:sanctum');
        Route::get('/panel/network_support_center/user/count','getSupportCenterUserCount')->middleware('auth:sanctum');
    });

    Route::controller(PanelSalesPointController::class)->group(function(){
        Route::get('/panel/sales_point/summary','getSalesPointSummary')->middleware('auth:sanctum');
        Route::get('/panel/sales_point/list','getSalesPointsList')->middleware('auth:sanctum');
        Route::get('/panel/sales_point/{id}/basic','getSalesPointBasic')->middleware('auth:sanctum');
        Route::get('/panel/sales_point/{id}/details','getSalesPointDetails')->middleware('auth:sanctum');
        Route::post('/panel/sales_point/{id}/{employee_id}/status_update', 'salesPointStatusUpdate')->middleware('auth:sanctum');
        Route::get('/panel/sales_point/search/{keyword}','getPointSearchResultList')->middleware('auth:sanctum');
        Route::get('/panel/sales_point/get_all_lat_lng','getAllLatLong')->middleware('auth:sanctum');
        Route::get('/panel/sales_point/user/count','getSalesPointUserCount')->middleware('auth:sanctum');
    });

    Route::controller(PanelSalesAgentController::class)->group(function(){
        Route::get('/panel/sales_agent/summary', 'getSalesAgentSummary')->middleware('auth:sanctum');
        Route::get('/panel/sales_agent/list', 'getSalesAgentsList');
        Route::get('/panel/sales_agent/{id}/basic', 'getSalesAgentBasic')->middleware('auth:sanctum');
        Route::get('/panel/sales_agent/{id}/details', 'getSalesAgentDetails')->middleware('auth:sanctum');
        Route::post('/panel/sales_agent/{id}/{employee_id}/status_update', 'salesAgentStatusUpdate')->middleware('auth:sanctum');
        Route::get('/panel/sales_agent/search/{keyword}', 'getAgentSearchResultList')->middleware('auth:sanctum');
        Route::get('/panel/sales_agent/get_all_lat_lng', 'getAllLatLong')->middleware('auth:sanctum');
        Route::get('/panel/sales_agent/user/count', 'getSalesAgentUserCount')->middleware('auth:sanctum');
    });

    Route::controller(CareerResumeController::class)->group(function(){
        Route::get('/panel/career/list', 'getApplicantList')->middleware('auth:sanctum');
        Route::get('/panel/career/{id}/details', 'getApplicantDetails')->middleware('auth:sanctum');
        Route::get('/panel/career/search/{keyword}', 'getApplicantSearchResult')->middleware('auth:sanctum');
        Route::get('/shared/career_resume_type_list/{status}', 'getCareerTypesList');
    });

    Route::get('/panel/employee/designation_list', [EmployeeDesignationController::class, 'getEmployeeDesignationList'])->middleware('auth:sanctum');

    Route::controller(EmployeeController::class)->group(function(){
        Route::get('/panel/employee/list', 'getEmployeeList')->middleware('auth:sanctum');
        Route::get('/panel/employee/{uid}/profile', 'getEmployeeDetails')->middleware('auth:sanctum');
        Route::post('/panel/employee/{uid}/panel_access_update', 'updatePanelAccess')->middleware('auth:sanctum');
        Route::post('/panel/employee/{uid}/designation_update', 'updateDesignation')->middleware('auth:sanctum');
        Route::post('/panel/employee/{uid}/status_update', 'updateStatus')->middleware('auth:sanctum');
        Route::get('/panel/employee/check_employment/{uid}', 'checkEmployment')->middleware('auth:sanctum');
    });

    Route::controller(PaymentsController::class)->group(function(){
        Route::get('/panel/payments/list', 'getPanelPaymentList')->middleware('auth:sanctum');
        Route::get('/panel/payments/old_users_paid_by_apps', 'getOldUsersPaidByApps')->middleware('auth:sanctum');
    });

    Route::controller(CommunicationController::class)->group(function(){
        Route::post('/panel/communication/{customer_uid}/{employee_uid}/create', 'createNewCommunication')->middleware('auth:sanctum');
        Route::get('/panel/communication/{customer_uid}/{type}/list', 'getCommunicationList')->middleware('auth:sanctum');
        Route::post('/panel/communication/{type}/{id}/{employee_uid}/delete', 'deleteCommunicationItem')->middleware('auth:sanctum');
    });

    Route::controller(TransmissionController::class)->group(function(){
        Route::post('/panel/transmission/company_create_update', 'transmissionCompanyCreateUpdate')->middleware('auth:sanctum');
        Route::get('/panel/transmission/company_details/{id}', 'transmissionCompanyDetails')->middleware('auth:sanctum');
        Route::get('/panel/transmission/company_summary', 'getTransmissionCompanySummary')->middleware('auth:sanctum');
        Route::get('/panel/transmission/company_list', 'getTransmissionCompanyList')->middleware('auth:sanctum');
        Route::post('/panel/transmission/delete_company/{rowId}', 'deleteTransmissionCompany')->middleware('auth:sanctum');
        Route::get('/panel/transmission/active_company_list', 'getTransmissionActiveCompanyList')->middleware('auth:sanctum');
        Route::get('/panel/transmission/active_support_center_list', 'getTransmissionActiveSupportCenterList')->middleware('auth:sanctum');
        Route::get('/panel/transmission/pop_summary', 'getTransmissionPopSummary')->middleware('auth:sanctum');
        Route::get('/panel/transmission/pop_list', 'getTransmissionPopList')->middleware('auth:sanctum');
        //Route::post('/panel/transmission/add_new_pop', 'getTransmissionAddNewPop')->middleware('auth:sanctum');
        Route::post('/panel/transmission/pop_delete/{rowId}', 'deleteTransmissionPop')->middleware('auth:sanctum');
        Route::get('/panel/transmission/pop_details/{rowId}', 'getTransmissionPopDetails')->middleware('auth:sanctum');
        Route::get('/panel/transmission/pop_nearest_customers/{latitude}/{longitude}/{radiation}', 'getTransmissionPopNearestCustomers')->middleware('auth:sanctum');
        Route::get('/panel/transmission/pop_calculate_customer_distance/{district_id}/{upazila_id}/{union_id}', 'calculatePopWiseCustomerDistance')->middleware('auth:sanctum');
        Route::get('/panel/transmission/tjbox_summary', 'getTransmissionTjboxSummary')->middleware('auth:sanctum');
        Route::get('/panel/transmission/tjbox_list', 'getTransmissionTjboxList')->middleware('auth:sanctum');
        Route::get('/panel/transmission/customer_summary', 'getTransmissionCustomerSummary')->middleware('auth:sanctum');
        Route::get('/panel/transmission/customer_list', 'getTransmissionCustomerList')->middleware('auth:sanctum');
        Route::post('/panel/transmission/customer_delete/{rowId}', 'deleteTransmissionCustomer')->middleware('auth:sanctum');
        Route::get('/panel/transmission/customer_details/{rowId}', 'getTransmissionCustomerDetails')->middleware('auth:sanctum');
        Route::get('/panel/transmission/customers_nearest_pops/{customer_id}/{latitude}/{longitude}', 'getTransmissionCustomerNearestPops')->middleware('auth:sanctum');
        //Route::get('/panel/transmission/customers_nearest_pops/test/{latitude1}/{longitude1}/{latitude2}/{longitude2}', 'testTransmissionCustomerNearestPops');
        Route::post('/panel/import/transmission_pops/{type}', 'importTransmissionPops')->middleware('auth:sanctum');
        Route::post('/panel/import/transmission_customers/{type}', 'importTransmissionCustomers')->middleware('auth:sanctum');
    });

    // OTP Reference ============================
    Route::controller(GlobalController::class)->group(function(){
        Route::get('/panel/otp/{id}', 'getOtp')->middleware('auth:sanctum');
        Route::post('/panel/otp/delete/{id}', 'deleteOtp')->middleware('auth:sanctum');
    });

    // ISP Business ============================
    Route::controller(PanelIspBusinessCenterController::class)->group(function(){
        Route::get('/panel/isp_business_center/list', 'getIspBusinessCenterList')->middleware('auth:sanctum');
        Route::get('/panel/isp_business_center/all/list', 'getIspBusinessCenterListAll')->middleware('auth:sanctum');
        Route::get('/panel/isp_business_center/{id}/details', 'getIspBusinessDetails')->middleware('auth:sanctum');
        Route::post('/panel/isp_business_center/{uid}/{employee_id}/status_update', 'ispBusinessStatusUpdate')->middleware('auth:sanctum');
        Route::get('/panel/isp_business_center/{id}/balance_wallet', 'ispBusinessBalanceWallet')->middleware('auth:sanctum');
        Route::get('/panel/isp_business_center/find_user/{mobile_number}', 'findUserForISP')->middleware('auth:sanctum');
        Route::get('/panel/internet/user/list/by/{zone_id}', 'getInternetUserListByZone')->middleware('auth:sanctum');
        Route::get('/panel/internet/user/list/by/district/{uid}', 'getInternetUserListByDistrict')->middleware('auth:sanctum');
        Route::get('/panel/internet_user/by/radiation/{radiation}/user/{uid}', 'getInternetUsersByRadiation')->middleware('auth:sanctum');
    });

    // ISP Business Client Settings ============
    Route::controller(ISPSettingsController::class)->group(function () {
        Route::get('/isp/corporate/client/list', 'corporateClientList');
        Route::get('/client/settings/{client_uid}', 'getClientSettings')->middleware('auth:sanctum');
        Route::post('/client/logo/update/{client_uid}', 'companyLogo')->middleware('auth:sanctum');
        Route::post('/client/signature/update/{client_uid}', 'signature')->middleware('auth:sanctum');
        Route::post('/client/billing/cycle/update/{client_uid}', 'billingCycle')->middleware('auth:sanctum');
        Route::post('/client/payment/method/update/{client_uid}', 'paymentMethod')->middleware('auth:sanctum');
        Route::post('/isp/corporate/client/delete/{id}', 'deleteISPBusiness');
    });

    // Permission ============================
    Route::controller(PanelPermissionController::class)->group(function () {
        Route::get('/panel/permission/list', 'getPermissionList')->middleware('auth:sanctum');
        Route::post('/panel/permission/create', 'createPermission')->middleware('auth:sanctum');
        Route::post('/panel/permission/update', 'updatePermission')->middleware('auth:sanctum');
        Route::post('/panel/permission/delete/{id}/{key_name}', 'deletePermission')->middleware('auth:sanctum');
        Route::get('/panel/transmission/permission/list', 'getTransmissionPermissionList')->middleware('auth:sanctum');
    });

    // Branch Info ===========================
    Route::controller(PanelBranchInfoController::class)->group(function () {
        Route::get('/panel/branch/list', 'getBranchList')->middleware('auth:sanctum');
        Route::post('/isp/client/broadband/user/bulk/upload', 'bulkUploadBroadbandUserRegister')->middleware('auth:sanctum');
        Route::post('/panel/{branch_name}/broadband/disable', 'broadbandUserDisable')->middleware('auth:sanctum');
        Route::post('/panel/{branch_name}/broadband/enable', 'broadbandUserEnable')->middleware('auth:sanctum');
        Route::post('/panel/broadband/current_month_status/reset', 'resetCurrentMonthZoneStatus')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/statement/broadband/list', 'broadbandStatement')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/statement/wifi/list', 'wifiStatement')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/mikrotik/broadband/total/user/list', 'broadbandMikrotikTotalUsers')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/mikrotik/broadband/active/user/list', 'broadbandMikrotikActiveUsers')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/mikrotik/broadband/inactive/user/list', 'broadbandMikrotikInactiveUsers')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/mikrotik/broadband/connected/user/list', 'broadbandMikrotikConnectedUsers')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/mikrotik/hotspot/total/user/list', 'hotspotMikrotikTotalUsers')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/mikrotik/hotspot/paid/user/list', 'hotspotMikrotikPaidUsers')->middleware('auth:sanctum');
        Route::get('/panel/{branch_name}/mikrotik/hotspot/connected/user/list', 'hotspotMikrotikConnectedUsers')->middleware('auth:sanctum');
        // ISP Client Statements
        Route::get('/panel/isp_client/{user_id}/statement/broadband/list', 'broadbandStatementISPClient')->middleware('auth:sanctum');
        Route::get('/panel/isp_client/{user_id}/statement/wifi/list', 'wifiStatementISPClient')->middleware('auth:sanctum');
        Route::get('/panel/all/lat/long/list', 'getAllLatLongPanel');
        Route::get('/panel/status/summary', 'getAllStatusSummaryPanel');
    });


    // ISP Client, Agent, Sub-Agent, Stall
    Route::controller(AgentController::class)->group(function () {
        Route::get('/isp/panel/{user_id}/agent/list', 'getAgentList')->middleware('auth:sanctum');
        Route::get('/isp/panel/agent/list', 'getAgentListAdmin')->middleware('auth:sanctum');
        Route::get('/isp/panel/agent/details/{id}', 'getAgentDetails')->middleware('auth:sanctum');
        Route::post('/isp/panel/{user_id}/agent/register','registerNewAgent')->middleware('auth:sanctum');
        Route::post('/isp/panel/{user_id}/agent/update','updateAgent')->middleware('auth:sanctum');
        Route::post('/isp/panel/{user_id}/{agent_uid}/agent/delete','deleteAgent')->middleware('auth:sanctum');
    });

    Route::controller(SubAgentController::class)->group(function () {
        Route::get('/isp/panel/{user_id}/sub_agent/list', 'getSubAgentList')->middleware('auth:sanctum');
        Route::get('/isp/panel/sub_agent/list', 'getSubAgentListAdmin')->middleware('auth:sanctum');
        Route::get('/isp/panel/sub_agent/details/{id}', 'getSubAgentDetails')->middleware('auth:sanctum');
        Route::post('/isp/panel/{user_id}/sub_agent/register', 'registerNewSubAgent')->middleware('auth:sanctum');
        Route::post('/isp/panel/{user_id}/sub_agent/update', 'updateSubAgent')->middleware('auth:sanctum');
        Route::post('/isp/panel/{user_id}/{agent_id}/{sub_agent_uid}/sub_agent/delete', 'deleteSubAgent')->middleware('auth:sanctum');
    });

    // Panel Money Of ISP Business -> client, agent, sub-agent
    Route::controller(ISPTopUpController::class)->group(function () {
        Route::get('/isp/panel/{uid}/transaction/history', 'transactionHistoryList')->middleware('auth:sanctum');
        Route::post('/isp/panel/money/admin-client/{admin_id}/{client_id}/transfer', 'addBalanceFromAdminToClient')->middleware('auth:sanctum');
        Route::post('/isp/panel/money/client-agent/{client_id}/{agent_id}/transfer', 'addBalanceFromClientToAgent')->middleware('auth:sanctum');
        Route::post('/isp/panel/money/agent-sub_agent/{agent_id}/{sub_agent_id}/transfer', 'addBalanceFromAgentToSubAgent')->middleware('auth:sanctum');
        Route::post('/isp/panel/{uid}/wallet_to_balance', 'addBalanceFromWallet')->middleware('auth:sanctum');

        Route::post('/isp/panel/{client_id}/broadband/disable', 'ispBroadbandUserDisable')->middleware('auth:sanctum');
        Route::post('/isp/panel/{client_id}/broadband/enable', 'ispBroadbandUserEnable')->middleware('auth:sanctum');
        Route::post('/isp/panel/{client_id}/{username}/broadband/enable', 'ispSingleUserEnable')->middleware('auth:sanctum');
        Route::post('/isp/panel/{client_id}/{username}/broadband/disable', 'ispSingleUserDisable')->middleware('auth:sanctum');
    });


    // ISP Broadband User list/create/edit/update -----
    Route::controller(ISPBroadbandInternetUserController::class)->group(function () {
        Route::get('/isp/broadband/{uid}/internet/user/list', 'getBroadbandInternetUserListISP')->middleware('auth:sanctum');
        Route::post('/isp/client/{client_uid}/broadband/user/create', 'createBroadbandInternetUserISP')->middleware('auth:sanctum');
        Route::post('/isp/broadband/{uid}/payment/panel-money', 'confirmBroadbandInternetUserISPPayment')->middleware('auth:sanctum');
        Route::post('/isp/broadband/{editor_id}/{internet_user_id}/update/user', 'updateBroadbandInternetUserISP')->middleware('auth:sanctum');
        Route::post('/isp/broadband/{editor_id}/{internet_user_id}/delete/user', 'deleteBroadbandInternetUserISP')->middleware('auth:sanctum');
        Route::post('/isp/broadband/{editor_id}/{internet_user_id}/bill_entry', 'billEntryBroadbandInternetUserISP')->middleware('auth:sanctum');
        Route::post('/isp/client/{client_id}/broadband/existing/user', 'createBroadbandInternetUserISPExisting')->middleware('auth:sanctum');
        Route::post('/isp/client/{uid}/broadband/package/change', 'changePackageOfExistingUser')->middleware('auth:sanctum');
        Route::post('/isp/client/broadband/user/bulk/upload', 'bulkUploadBroadbandUserRegister')->middleware('auth:sanctum');
        Route::post('/isp/client/update/connection/status/{mobile}', 'connectionStatusActive')->middleware('auth:sanctum');
    });

    // ISP Hotspot User list/create/edit/update -----
    Route::controller(ISPHotspotInternetUserController::class)->group(function () {
        Route::get('/isp/hotspot/{uid}/internet/user/list', 'getHotspotInternetUserListISP')->middleware('auth:sanctum');
        Route::get('/isp/paid/hotspot/{uid}/internet/user/list', 'getPaidHotspotInternetUserListISP')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/internet/user/create', 'createHotspotInternetUserISP')->middleware('auth:sanctum');
        // Route::post('/isp/hotspot/existing/internet/user/create', 'createHotspotInternetUserISPExisting');
        Route::post('/isp/client/{client_id}/hotspot/existing/user', 'createHotspotInternetUserISPExisting')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/{editor_id}/{internet_user_id}/update/user', 'updateHotspotInternetUserISP')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/{editor_id}/{internet_user_id}/delete/user', 'deleteHotspotInternetUserISP')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/{editor_id}/{internet_user_id}/bill_entry', 'billEntryHotspotInternetUserISP')->middleware('auth:sanctum');
        Route::get('/isp/hotspot/package/update/{zone_id}', 'packageUpdate');
        Route::post('/isp/hotspot/checking/session/expire', 'checkRadReplySession')->middleware('auth:sanctum');
        Route::post('/shadhin/hotspot/checking/session/expire', 'checkRadReplySessionShadhin')->middleware('auth:sanctum');
        

        Route::post('/isp/hotspot/client/internet/user/create', 'createHotspotInternetUserISPClient')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/bkash/info', 'createBkashInfo')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/create/payment/from/payment/portal', 'createPaymentFromPaymentPortal')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/create/payment/from/payment/portal/sslcommerz', 'createPaymentFromPaymentPortalBySSLCommerz')->middleware('auth:sanctum');
        Route::post('/panel/isp_client/bkash/tokenize/payment/create', 'bkashTokenizePaymentCreate')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/create/payment/from/portal/bkash/tokenize', 'createPaymentFromPaymentPortalBkashTokenize')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/login/check', 'checkRadcheckLogin')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/checking/expire/date', 'checkingExpireDate')->middleware('auth:sanctum');
        Route::post('/isp/hotspot/change/password', 'changePassword')->middleware('auth:sanctum');
        Route::post('/isp/client/hotspot/user/bulk/upload', 'bulkUploadHotspotUserRegister')->middleware('auth:sanctum');
    });

    Route::controller(PackageController::class)->group(function () {
        Route::get('/all/corporate/packages', 'getAllCorporatePackageList')->middleware('auth:sanctum');
        Route::get('/broadband/corporate/packages', 'getBroadbandCorporatePackageList')->middleware('auth:sanctum');
        Route::get('/edc/packages', 'getEdcPackageList')->middleware('auth:sanctum');
        Route::get('/wifi/corporate/packages', 'getWifiCorporatePackageList')->middleware('auth:sanctum');
        Route::get('/broadband/corporate/client/{uid}/packages', 'getClientWiseBroadbandCorporatePackageList')->middleware('auth:sanctum');
        Route::get('/wifi/corporate/client/{uid}/packages', 'getClientWiseWifiCorporatePackageList')->middleware('auth:sanctum');
        Route::post('/create/corporate/client/package', 'createNewCorporatePackage')->middleware('auth:sanctum');
        Route::post('/update/corporate/client/package/{id}', 'updateCorporatePackage')->middleware('auth:sanctum');
        Route::post('/enable/disable/corporate/client/package/{id}', 'enableDisableCorporatePackage')->middleware('auth:sanctum');
        Route::post('/delete/corporate/client/package/{id}', 'deleteCorporatePackage')->middleware('auth:sanctum');
        Route::post('/assign/corporate/client/{client_id}/package/{id}', 'corporateClientPackageAssign')->middleware('auth:sanctum');
        Route::get('/broadband/shadhin/packages', 'getShadhinClientWiseBroadbandPackageList')->middleware('auth:sanctum');
    });

    Route::controller(BroadbandUserRegistrationController::class)->group(function () {
        Route::get('/broadband/packages','getBroadbandPackageList')->middleware('auth:sanctum');
        Route::get('/wifi/packages','getWiFiPackageList')->middleware('auth:sanctum');
        Route::post('/broadband/user/register','broadbandUserStore')->middleware('auth:sanctum');
    });

    // Website ----------------------------------------
    Route::controller(WebsiteController::class)->group(function () {
        Route::post('/web/network_support_center/register', 'webRegisterNetworkSupportCenter')->middleware('auth:sanctum');
        Route::post('/web/isp_business_center/register', 'webRegisterISPBusinessCenter');
        // Route::get('/web/network_support_center/zone_name_update', 'webNetworkSupportCenterZoneNameUpdate');
        Route::post('/web/internet_user/register', 'webRegisterInternetUser');
        Route::post('/web/sales_agent/register', 'webRegisterSalesAgent');
        Route::post('/web/sales_point/register', 'webRegisterSalesPoint')->middleware('auth:sanctum');
        Route::post('/web/application/resume', 'webRegisterResumeApplication')->middleware('auth:sanctum');
    });


    // Apps ------------------------------------------
    Route::post('/apps/welcome_screens', [AppsWelcomeScreenController::class, 'appWelcomeScreen']);

    Route::controller(AppsAccountController::class)->group(function () {
        Route::post('/apps/account/basic', 'appAccountBasic')->middleware('auth:sanctum');
        Route::post('/apps/account/profile_update', 'appAccountProfileUpdate')->middleware('auth:sanctum');
    });

    Route::post('/apps/get_dashboard_data', [AppDashboardController::class, 'getDashboardData'])->middleware('auth:sanctum');
    Route::post('/apps/check_wallet_balance', [UserController::class, 'checkWalletBalance'])->middleware('auth:sanctum');

    Route::controller(GlobalController::class)->group(function () {
        Route::post('/apps/send_message', 'sendTextSms');
        Route::post('/apps/verify_otp/{mobile_number}/{otp_number}', 'verifyOtpNumber');
    });

    // internet connection
    Route::controller(AppsInternetUsersController::class)->group(function () {
        Route::post('/apps/purchase_internet_package', 'purchaseInternetPackage');
        Route::post('/apps/get_user_internet_password', 'getUserInternetPassword')->middleware('auth:sanctum');
        Route::get('/apps/user_internet_package/{user_auth_id}', 'userActiveInternetPackage')->middleware('auth:sanctum');
        Route::get('/apps/user_internet_zone/{user_auth_id}', 'userInternetPartnerId')->middleware('auth:sanctum');
    });

    Route::controller(RadiusServerController::class)->group(function () {
        // Route::post('/apps/check_wifi_connection_status', 'checkWifiConnectionStatus')->middleware('auth:sanctum');
        Route::post('/apps/check_wifi_connection_status', 'checkWifiConnectionStatus');
        Route::get('/apps/check_old_broadband_user_existence/{user_auth_id}', 'checkOldBroadbandUserExistence');//->middleware('auth:sanctum');
        // wifi
        Route::post('/apps/activate_wifi_package', 'activateWifiPackage')->middleware('auth:sanctum');
    });

    // payments
    Route::controller(PaymentsController::class)->group(function () {
        Route::get('/payments/check_user_zone_id/{user_auth_id}', 'checkUserZoneId');//->middleware('auth:sanctum');
        Route::post('/apps/payments/success', 'paymentSuccess')->middleware('auth:sanctum');
    });

    Route::post('/payments/wallet_point/create', [WalletPointPaymentsController::class, 'paymentWalletPointCreate']);

    Route::controller(BkashPaymentController::class)->group(function () {
        Route::post('/payments/bkash/create', 'paymentCreate');
        Route::post('/payments/bkash/execute', 'paymentExecute');
    });

    Route::controller(UpayPaymentController::class)->group(function () {
        Route::post('/payments/upay/create', 'paymentCreate');
        Route::get('/payments/upay/redirect/internet_bill_pay', 'paymentRedirect');
    });

    // transactions
    Route::get('/apps/transactions/{auth_id}/list', [TransactionController::class, 'getTransactionList'])->middleware('auth:sanctum');

    // Products
    Route::get('/apps/products/featured_products_list', [ProductController::class, 'getFeaturedProductList']);
    Route::post('/apps/products/product_purchase_registration', [ProductPurchaseRequestsController::class, 'productPurchaseRegistration'])->middleware('auth:sanctum');

    // Affiliate
    Route::controller(AppsAffiliateUserRegistrationController::class)->group(function () {
        Route::get('/apps/affiliate/internet_commission_list/{agent_type}', 'getInternetCommissionList')->middleware('auth:sanctum');
        Route::get('/apps/affiliate/product_commission_list/{agent_type}', 'getProductCommissionList')->middleware('auth:sanctum');
        Route::get('/apps/affiliate/service_commission_list/{agent_type}', 'getServiceCommissionList')->middleware('auth:sanctum');
        Route::post('/apps/affiliate/check_account_and_send_otp', 'affiliateCheckAccountAndSendOtp')->middleware('auth:sanctum');
        Route::post('/apps/affiliate/internet_user_register', 'affiliateRegisterInternetUser')->middleware('auth:sanctum');
        Route::get('/apps/affiliate/{affiliator_auth_id}/list/{affiliate_type}', 'affiliateAmarAffiliateProducts')->middleware('auth:sanctum');
        Route::get('/apps/affiliate/monthly_commission/{agent_type}/{affiliator_auth_id}/{month_year}', 'affiliateAmarMonthlyCommission')->middleware('auth:sanctum');
        Route::get('/apps/affiliate/{affiliator_auth_id}/{affiliate_type}/search/{keywords}', 'affiliateAmarAffiliateSearch')->middleware('auth:sanctum');
    });

    // Internet User
    Route::controller(AppsNetworkSupportCenterController::class)->group(function () {
        Route::get('/apps/internet_user/{partner_auth_id}/user_details/{user_auth_id}', 'getNetworkSupportCenterUserDetails')->middleware('auth:sanctum');
        Route::post('/apps/network_support_center/register', 'registerNetworkSupportCenter')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/dashboard', 'networkSupportCenterDashboard')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/user_list/{type}', 'getNetworkSupportCenterUserList')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/user_list/{type}/search/{keywords}', 'getSearchedNetworkSupportCenterUserList')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/sales_points_list', 'getNetworkSupportCenterSalesPointsList')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/sales_points_details/{sales_point_uid}', 'getNetworkSupportCenterSalesPointDetails')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/sales_agents_list', 'getNetworkSupportCenterSalesAgentsList')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/sales_agents_details/{sales_agent_uid}', 'getNetworkSupportCenterSalesAgentDetails')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/sales_agents_list/search/{keywords}', 'getSearchedNetworkSupportCenterSalesAgentList')->middleware('auth:sanctum');

        Route::get('/apps/network_support_center/{partner_auth_id}/monthly_profit_data/{filtered_date}', 'getNetworkSupportCenterMonthlyProfitData');//->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/monthly_profit_commission_list/{zone_id}/{filtered_date}/{package_type}', 'getNetworkSupportCenterMonthlyProfitCommissionList')->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/monthly_profit_broadband_users_count', 'getNetworkSupportCenterMonthlyProfitBroadbandUsersCount');//->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/{partner_auth_id}/monthly_profit_wifi_users_count', 'getNetworkSupportCenterMonthlyProfitWifiCount');//->middleware('auth:sanctum');
        Route::get('/apps/network_support_center/monthly_profit_users_list/{zone_id}/{package_type}/{status_type}', 'getNetworkSupportCenterMonthlyProfitUserList')->middleware('auth:sanctum');
    });

    //Route::get('/apps/network_support_center/{partner_auth_id}/broadband/activate', [MikrotikController::class, 'activateId'])->middleware('auth:sanctum');
    Route::controller(AppsSalesPointController::class)->group(function () {
        Route::post('/apps/sales_point/register', 'registerSalesPoint')->middleware('auth:sanctum');
        Route::get('/apps/sales_point/dashboard/{auth_id}', 'salesPointDashboard')->middleware('auth:sanctum');
    });

    Route::controller(AppsSalesAgentController::class)->group(function () {
        Route::post('/apps/sales_agent/register', 'registerSalesAgent')->middleware('auth:sanctum');
        Route::get('/apps/sales_agent/dashboard/{auth_id}', 'salesAgentDashboard')->middleware('auth:sanctum');
    });

    Route::controller(MessageAndNotificationController::class)->group(function () {
        Route::get('/apps/message/list/{auth_id}', 'getMessageList')->middleware('auth:sanctum');
        Route::get('/apps/message/details/{id}', 'getMessageDetails')->middleware('auth:sanctum');
        Route::post('/apps/send_notification', 'sendNotification')->middleware('auth:sanctum');
    });

    // Support
    Route::post('/apps/support/user_support_request', [CustomerSupportRequestController::class, 'createUserSupportRequest']);
    Route::controller(ShadhinPackageController::class)->group(function () {
        Route::get('/all/shadhin/packages', 'getAllShadhinPackageList')->middleware('auth:sanctum');
        Route::get('/broadband/shadhin/packages', 'getBroadbandShadhinPackageList')->middleware('auth:sanctum');
        Route::get('/broadband/shadhin/packages/{zone_id}', 'getShadhinClientWiseBroadbandPackageList')->middleware('auth:sanctum');
        Route::get('/wifi/shadhin/packages', 'getWifiShadhinPackageList')->middleware('auth:sanctum');
        Route::get('/broadband/shadhin/client/{zone_id}/packages', 'getSupportCenterWiseBroadbandPackageList')->middleware('auth:sanctum');
        Route::get('/wifi/shadhin/client/{zone_id}/packages', 'getSupportCenterWiseHotspotPackageList')->middleware('auth:sanctum');
        Route::post('/create/shadhin/client/package', 'createNewShadhinPackage')->middleware('auth:sanctum');
        Route::post('/update/shadhin/client/package/{id}', 'updateShadhinPackage')->middleware('auth:sanctum');
        Route::post('/enable/disable/shadhin/client/package/{id}', 'enableDisableShadhinPackage')->middleware('auth:sanctum');
        Route::post('/delete/shadhin/client/package/{id}', 'deleteShadhinPackage')->middleware('auth:sanctum');
        Route::post('/assign/shadhin/client/{zone_id}/package/{id}', 'shadhinPackageAssign')->middleware('auth:sanctum');
    });

    Route::controller(ShadhinHotspotClientController::class)->group(function(){
        Route::post('/shadhin/hotspot/client/internet/user/create', 'createHotspotInternetUserShadhinClient');
        Route::post('/users/billing/panel/hotspot/login', 'user_login_hotspot_billing')->middleware('guest');
    });

    Route::controller(ShadhinHotspotCheckoutURLController::class)->group(function(){
        Route::post('/shadhin/hotspot/payment/bkash/create', 'create')->middleware('auth:sanctum');
        Route::get('/shadhin/hotspot/payment/bkash/callback', 'callback');
        Route::get('/shadhin/hotspot/payment/bkash/refund', 'refund')->middleware('auth:sanctum');
    });

    Route::controller(ShadhinBroadbandClientController::class)->group(function(){
        Route::post('/shadhin/broadband/client/internet/user/create', 'createBroadbandInternetUserShadhinClient')->middleware('auth:sanctum');
        Route::get('/shadhin/broadband/payment/info/{internet_user_id}', 'getPaymentData')->middleware('auth:sanctum');
        Route::post('/users/billing/panel/broadband/login', 'user_login_broadband_billing')->middleware('guest');
    });

    // Broadband Checkout URL ---------
    Route::controller(ShadhinBroadbandCheckoutURLController::class)->group(function(){
        Route::post('/shadhin/broadband/payment/bkash/create','create')->middleware('auth:sanctum');
        Route::get('/shadhin/broadband/payment/bkash/callback','callback');
        Route::get('/shadhin/broadband/payment/bkash/refund','refund')->middleware('auth:sanctum');
        
    });

    // Transmission New By Parvez --------
    Route::controller(TransCommonController::class)->group(function(){
        Route::get('/trans/radius/filter/{auth_id}/{id}/{type}/{latitude}/{longitude}/{radiation}','getTransPopsByRadiation')->middleware('auth:sanctum');
        Route::get('/trans/radius/filter/{latitude}/{longitude}/{radiation}','getTransCustomerByRadiation')->middleware('auth:sanctum');
        Route::post('/trans/{auth_id}/{trans_id}/{type}/image/upload','imageUploadTransmission')->middleware('auth:sanctum');
        Route::post('/trans/{id}/image/remove','imageRemoveTransmission')->middleware('auth:sanctum');
        Route::get('/trans/customer/connection/path/{id}','getConnectionPathFromCustomer')->middleware('auth:sanctum');
        Route::get('/trans/tj_box/connection/path/{id}','getConnectionPathFromTj')->middleware('auth:sanctum');
        Route::get('/trans/loop/connection/path/{id}','getConnectionPathFromLoop')->middleware('auth:sanctum');
        Route::get('/trans/all/feature/lat/long/{auth_id}','getAllLatLong')->middleware('auth:sanctum');
        Route::get('/trans/all/feature/summary/{auth_id}','getAllTranSummary')->middleware('auth:sanctum');
    });

    // Company ---
    Route::controller(TransCompanyController::class)->group(function(){
        Route::get('/trans/company/list','transCompanyList')->middleware('auth:sanctum');
        Route::post('/trans/company/add','transCompanyAdd')->middleware('auth:sanctum');
        Route::get('/trans/company/{id}/details','transCompanyDetails')->middleware('auth:sanctum');
        Route::post('/trans/company/{id}/edit','transCompanyEdit')->middleware('auth:sanctum');
        Route::post('/trans/company/{id}/delete','transCompanyDelete')->middleware('auth:sanctum');
        Route::get('/trans/company/status/summary','summaryTransCompany')->middleware('auth:sanctum');
    });

    // NTTN Pops ---
    Route::controller(TransNTTNPopController::class)->group(function(){
        Route::get('/trans/nttn/pop/list','transNTTNPopList')->middleware('auth:sanctum');
        Route::get('/trans/nttn/pops','transNTTNPops')->middleware('auth:sanctum');
        Route::get('/trans/nttn/pop/{id}/details','transNTTNPopDetails')->middleware('auth:sanctum');
        Route::post('/trans/nttn/pop/create','createTransNTTNPop')->middleware('auth:sanctum');
        Route::post('/trans/nttn/pop/edit/{id}','editTransNTTNPop')->middleware('auth:sanctum');
        Route::post('/trans/nttn/pop/delete/{id}','deleteTransNTTNPop')->middleware('auth:sanctum');
        Route::get('/trans/nttn/pop/status/summary','summaryTransNTTNPop')->middleware('auth:sanctum');
        Route::get('/trans/nttn/pop/all/lat/long','getTransNTTNPopLatLong')->middleware('auth:sanctum');
        Route::post('/trans/nttn/pop/bulk/upload','bulkUploadTransNTTNPop')->middleware('auth:sanctum');
    });

    // Branch Pops ---
    Route::controller(TransBranchPopController::class)->group(function(){
        Route::get('/trans/branch/pop/list/{auth_id}','transBranchPopList')->middleware('auth:sanctum');
        Route::get('/trans/branch/pops/{auth_id}','transBranchPops')->middleware('auth:sanctum');
        Route::get('/trans/branch/pops/shadhin/list','transBranchPopsShadhin')->middleware('auth:sanctum');
        Route::get('/trans/branch/pops/tree/list/{auth_id}','transBranchPopsTree')->middleware('auth:sanctum');
        Route::get('/trans/branch/pop/{auth_id}/{id}/details','transBranchPopDetails')->middleware('auth:sanctum');
        Route::post('/trans/branch/pop/create/{auth_id}','createTransBranchPop')->middleware('auth:sanctum');
        Route::post('/trans/branch/pop/edit/{auth_id}/{id}','editTransBranchPop')->middleware('auth:sanctum');
        Route::post('/trans/branch/pop/delete/{auth_id}/{id}','deleteTransBranchPop')->middleware('auth:sanctum');
        Route::get('/trans/branch/pop/status/summary/{auth_id}','summaryTransBranchPop')->middleware('auth:sanctum');
        Route::get('/trans/branch/pop/all/lat/long/{auth_id}','getTransBranchPopLatLong')->middleware('auth:sanctum');
        Route::post('/trans/branch/pop/bulk/upload','bulkUploadTransBranchPop')->middleware('auth:sanctum');
        Route::get('/trans/all/lat/long/{auth_id}','getTransLatLong');
    });

    // Sub Branch Pops ---
    Route::controller(TransSubBranchPopController::class)->group(function(){
        Route::get('/trans/sub_branch/pop/list/{auth_id}','transSubBranchPopList')->middleware('auth:sanctum');
        Route::get('/trans/sub_branch/pop/{auth_id}/{id}/details','transSubBranchPopDetails')->middleware('auth:sanctum');
        Route::post('/trans/sub_branch/pop/create/{auth_id}','createTransSubBranchPop')->middleware('auth:sanctum');
        Route::post('/trans/sub_branch/pop/edit/{auth_id}/{id}','editTransSubBranchPop')->middleware('auth:sanctum');
        Route::post('/trans/sub_branch/pop/delete/{auth_id}/{id}','deleteTransSubBranchPop')->middleware('auth:sanctum');
        Route::get('/trans/sub_branch/pop/status/summary/{auth_id}','summaryTransSubBranchPop')->middleware('auth:sanctum');
        Route::get('/trans/sub_branch/pop/all/lat/long/{auth_id}','getTransSubBranchPopLatLong')->middleware('auth:sanctum');
        Route::post('/trans/sub_branch/pop/bulk/upload','bulkUploadTransSubBranchPop')->middleware('auth:sanctum');
    });

    // Tj Box All ---
    Route::controller(TransTjBoxController::class)->group(function(){
        Route::get('/trans/tj_box/list/{auth_id}','transTjBoxList')->middleware('auth:sanctum');
        Route::get('/trans/tj_boxes/{auth_id}/{pop_id}/{olt_port}','transTjBoxes')->middleware('auth:sanctum');
        Route::get('/trans/tree/{auth_id}/{tj_type}/{pop_id}/{parent_tj_id}','transTjBoxesTree')->middleware('auth:sanctum');
        Route::get('/trans/nttn/tj_boxes/{auth_id}/{pop_id}/{tj_id}','transNTTNTjBoxes')->middleware('auth:sanctum');
        Route::get('/trans/tj_box/status/summary/{auth_id}','summaryTjBox')->middleware('auth:sanctum');
        Route::get('/trans/tj_box/all/lat/long/{auth_id}','getTransTjBoxLatLong')->middleware('auth:sanctum');
        Route::get('/trans/tj_box/{tj_type}/latest/id','transTjBoxLatestId')->middleware('auth:sanctum');
    });

    // Backbone Tj Box ---
    Route::controller(TransBackboneTjBoxController::class)->group(function(){
        Route::get('/trans/backbone/tj_box/list/{auth_id}','transBackboneTjBoxList')->middleware('auth:sanctum');
        Route::get('/trans/backbone/tj_box/{auth_id}/{id}/details','transBackboneTjBoxDetails')->middleware('auth:sanctum');
        Route::post('/trans/backbone/tj_box/create/{auth_id}','createTransBackboneTjBox')->middleware('auth:sanctum');
        Route::post('/trans/backbone/tj_box/edit/{auth_id}/{id}','editTransBackboneTjBox')->middleware('auth:sanctum');
        Route::post('/trans/backbone/tj_box/delete/{auth_id}/{id}','deleteTransBackboneTjBox')->middleware('auth:sanctum');
        Route::get('/trans/backbone/tj_box/status/summary/{auth_id}','summaryTransBackboneTjBox')->middleware('auth:sanctum');
        Route::get('/trans/backbone/tj_box/all/lat/long/{auth_id}','getTransBackboneTjBoxLatLong')->middleware('auth:sanctum');
        Route::post('/trans/backbone/tj_box/bulk/upload','bulkUploadTransBackboneTjBox')->middleware('auth:sanctum');
    });

    // Backbone Join Tj Box ---
    Route::controller(TransBackboneJoinTjBoxController::class)->group(function(){
        Route::get('/trans/backbone_join/tj_box/list/{auth_id}','transBackboneJoinTjBoxList')->middleware('auth:sanctum');
        Route::get('/trans/backbone_join/tj_box/{auth_id}/{id}/details','transBackboneJoinTjBoxDetails')->middleware('auth:sanctum');
        Route::post('/trans/backbone_join/tj_box/create/{auth_id}','createTransBackboneJoinTjBox')->middleware('auth:sanctum');
        Route::post('/trans/backbone_join/tj_box/edit/{auth_id}/{id}','editTransBackboneJoinTjBox')->middleware('auth:sanctum');
        Route::post('/trans/backbone_join/tj_box/delete/{auth_id}/{id}','deleteTransBackboneJoinTjBox')->middleware('auth:sanctum');
        Route::get('/trans/backbone_join/tj_box/status/summary/{auth_id}','summaryTransBackboneJoinTjBox')->middleware('auth:sanctum');
        Route::get('/trans/backbone_join/tj_box/all/lat/long/{auth_id}','getTransBackboneJoinTjBoxLatLong')->middleware('auth:sanctum');
        Route::post('/trans/backbone_join/tj_box/bulk/upload','bulkUploadTransBackboneJoinTjBox')->middleware('auth:sanctum');
    });

    // Joining Tj Box ---
    Route::controller(TransJoiningTjBoxController::class)->group(function(){
        Route::get('/trans/joining/tj_box/list/{auth_id}','transJoiningTjBoxList')->middleware('auth:sanctum');
        Route::get('/trans/joining/tj_box/{auth_id}/{id}/details','transJoiningTjBoxDetails')->middleware('auth:sanctum');
        Route::post('/trans/joining/tj_box/create/{auth_id}','createTransJoiningTjBox')->middleware('auth:sanctum');
        Route::post('/trans/joining/tj_box/edit/{auth_id}/{id}','editTransJoiningTjBox')->middleware('auth:sanctum');
        Route::post('/trans/joining/tj_box/delete/{auth_id}/{id}','deleteTransJoiningTjBox')->middleware('auth:sanctum');
        Route::get('/trans/joining/tj_box/status/summary/{auth_id}','summaryTransJoiningTjBox')->middleware('auth:sanctum');
        Route::get('/trans/joining/tj_box/all/lat/long/{auth_id}','getTransJoiningTjBoxLatLong')->middleware('auth:sanctum');
        Route::post('/trans/joining/tj_box/bulk/upload','bulkUploadTransJoiningTjBox')->middleware('auth:sanctum');
    });

    // Distribution Tj Box ---
    Route::controller(TransDistributionTjBoxController::class)->group(function(){
        Route::get('/trans/distribution/tj_box/list/{auth_id}','transDistributionTjBoxList')->middleware('auth:sanctum');
        Route::get('/trans/distribution/tj_box/{auth_id}/{id}/details','transDistributionTjBoxDetails')->middleware('auth:sanctum');
        Route::post('/trans/distribution/tj_box/create/{auth_id}','createTransDistributionTjBox')->middleware('auth:sanctum');
        Route::post('/trans/distribution/tj_box/edit/{auth_id}/{id}','editTransDistributionTjBox')->middleware('auth:sanctum');
        Route::post('/trans/distribution/tj_box/delete/{auth_id}/{id}','deleteTransDistributionTjBox')->middleware('auth:sanctum');
        Route::get('/trans/distribution/tj_box/status/summary/{auth_id}','summaryTransDistributionTjBox')->middleware('auth:sanctum');
        Route::get('/trans/distribution/tj_box/latest/splitter/id','getLatestSplitterId')->middleware('auth:sanctum');
        Route::get('/trans/distribution/tj_box/all/lat/long/{auth_id}','getTransDistributionTjBoxLatLong')->middleware('auth:sanctum');
        Route::post('/trans/distribution/tj_box/bulk/upload','bulkUploadTransDistributionTjBox')->middleware('auth:sanctum');
    });

    // Customer Tj Box ---
    Route::controller(TransCustomerTjBoxController::class)->group(function(){
        Route::get('/trans/customer/tj_box/list/{auth_id}','transCustomerTjBoxList')->middleware('auth:sanctum');
        Route::get('/trans/customer/tj_box/{auth_id}/{id}/details','transCustomerTjBoxDetails')->middleware('auth:sanctum');
        Route::post('/trans/customer/tj_box/create/{auth_id}','createTransCustomerTjBox')->middleware('auth:sanctum');
        Route::post('/trans/customer/tj_box/edit/{auth_id}/{id}','editTransCustomerTjBox')->middleware('auth:sanctum');
        Route::post('/trans/customer/tj_box/delete/{auth_id}/{id}','deleteTransCustomerTjBox')->middleware('auth:sanctum');
        Route::get('/trans/customer/tj_box/status/summary/{auth_id}','summaryTransCustomerTjBox')->middleware('auth:sanctum');
        Route::get('/trans/customer/tj_box/all/lat/long/{auth_id}','getTransCustomerTjBoxLatLong')->middleware('auth:sanctum');
        Route::post('/trans/customer/tj_box/bulk/upload','bulkUploadTransCustomerTjBox')->middleware('auth:sanctum');
    });

    // Loop All ---
    Route::controller(TransLoopController::class)->group(function(){
        Route::get('/trans/loop/list/{auth_id}','transLoopList')->middleware('auth:sanctum');
        Route::get('/trans/loop/status/summary/{auth_id}','summaryLoop')->middleware('auth:sanctum');
        Route::get('/trans/loop/all/lat/long/{auth_id}','getTransLoopLatLong')->middleware('auth:sanctum');
        Route::get('/trans/loop/{loop_type}/latest/id','transLoopLatestId')->middleware('auth:sanctum');
    });

    // Reserved Loop ---
    Route::controller(TransReservedLoopController::class)->group(function(){
        Route::get('/trans/reserved/loop/list/{auth_id}','transReservedLoopList')->middleware('auth:sanctum');
        Route::get('/trans/reserved/loop/{auth_id}/{id}/details','transReservedLoopDetails')->middleware('auth:sanctum');
        Route::post('/trans/reserved/loop/create/{auth_id}','createTransReservedLoop')->middleware('auth:sanctum');
        Route::post('/trans/reserved/loop/edit/{auth_id}/{id}','editTransReservedLoop')->middleware('auth:sanctum');
        Route::post('/trans/reserved/loop/delete/{auth_id}/{id}','deleteTransReservedLoop')->middleware('auth:sanctum');
        Route::get('/trans/reserved/loop/status/summary/{auth_id}','summaryTransReservedLoop')->middleware('auth:sanctum');
        Route::get('/trans/reserved/loop/all/lat/long/{auth_id}','getTransReservedLoopLatLong')->middleware('auth:sanctum');
        Route::post('/trans/reserved/loop/bulk/upload','bulkUploadTransReservedLoop')->middleware('auth:sanctum');
    });

    // Distribution Loop ---
    Route::controller(TransDistributionLoopController::class)->group(function(){
        Route::get('/trans/distribution/loop/list/{auth_id}','transDistributionLoopList')->middleware('auth:sanctum');
        Route::get('/trans/distribution/loop/{auth_id}/{id}/details','transDistributionLoopDetails')->middleware('auth:sanctum');
        Route::post('/trans/distribution/loop/create/{auth_id}','createTransDistributionLoop')->middleware('auth:sanctum');
        Route::post('/trans/distribution/loop/edit/{auth_id}/{id}','editTransDistributionLoop')->middleware('auth:sanctum');
        Route::post('/trans/distribution/loop/delete/{auth_id}/{id}','deleteTransDistributionLoop')->middleware('auth:sanctum');
        Route::get('/trans/distribution/loop/status/summary/{auth_id}','summaryTransDistributionLoop')->middleware('auth:sanctum');
        Route::get('/trans/distribution/loop/all/lat/long/{auth_id}','getTransDistributionLoopLatLong')->middleware('auth:sanctum');
        Route::post('/trans/distribution/loop/bulk/upload','bulkUploadTransDistributionLoop')->middleware('auth:sanctum');
    });

    // Customer Loop ---
    Route::controller(TransCustomerController::class)->group(function(){
        Route::get('/trans/customer/list/{auth_id}','transCustomerList')->middleware('auth:sanctum');
        Route::get('/trans/customer/{auth_id}/{id}/details','transCustomerDetails')->middleware('auth:sanctum');
        Route::post('/trans/customer/create/{auth_id}','createTransCustomer')->middleware('auth:sanctum');
        Route::post('/trans/customer/edit/{auth_id}/{id}','editTransCustomer')->middleware('auth:sanctum');
        Route::post('/trans/customer/delete/{auth_id}/{id}','deleteTransCustomer')->middleware('auth:sanctum');
        Route::get('/trans/customer/status/summary/{auth_id}','summaryTransCustomer')->middleware('auth:sanctum');
        Route::get('/trans/customer/all/lat/long/{auth_id}','getTransCustomerLatLong')->middleware('auth:sanctum');
        Route::post('/trans/customer/bulk/upload','bulkUploadTransCustomer')->middleware('auth:sanctum');
    });

    // Geo Loop ---
    Route::controller(TransGeoJsonController::class)->group(function(){
        Route::get('/trans/geo/json/list/{auth_id}','index')->middleware('auth:sanctum');
        Route::get('/trans/geo/json/details/{auth_id}/{pop_code}','show')->middleware('auth:sanctum');
        Route::post('/trans/geo/json/create/{auth_id}','store')->middleware('auth:sanctum');
        Route::post('/trans/geo/json/delete/{auth_id}/{id}','delete')->middleware('auth:sanctum');
    });

    // Branch Pops ---
    Route::controller(TransInfoSarkarPopController::class)->group(function(){
        Route::get('/trans/info/sarkar/pop/list','transInfoSarkarPopList')->middleware('auth:sanctum');
        // Route::get('/trans/info/sarkar/pops','transInfoSarkarPops');
        // Route::get('/trans/info/sarkar/pops/tree/list','transInfoSarkarPopsTree');
        // Route::get('/trans/info/sarkar/pop/{id}/details','transInfoSarkarPopDetails');
        // Route::post('/trans/info/sarkar/pop/create','createTransInfoSarkarPop');
        // Route::post('/trans/info/sarkar/pop/edit/{id}','editTransInfoSarkarPop');
        // Route::post('/trans/info/sarkar/pop/delete/{id}','deleteTransInfoSarkarPop');
        // Route::get('/trans/info/sarkar/pop/status/summary','summaryTransInfoSarkarPop');
        // Route::get('/trans/info/sarkar/pop/all/lat/long','getTransInfoSarkarPopLatLong');
        // Route::post('/trans/info/sarkar/pop/bulk/upload','bulkUploadTransInfoSarkarPop');
        // Route::get('/trans/info/sarkar/all/lat/long','getTransLatLong');
    });

    // school tracking system
    Route::controller(SchoolProfileController::class)->group(function(){
        Route::get('/schools/{type}','index')->middleware('auth:sanctum');
        Route::get('/schools/without/mk/{type}/{c_status}','listWithoutMK')->middleware('auth:sanctum');
        Route::get('/schools/by/institution/type/{type}','listByInstitutionType')->middleware('auth:sanctum');
        Route::get('/nms/lot/admin/top/sheet','topSheet')->middleware('auth:sanctum');
        Route::get('/nms/lot/admin/invoice','invoice')->middleware('auth:sanctum');
        Route::get('/schools/fiber/length/{type}','schoolFiberLengthList')->middleware('auth:sanctum');
        Route::get('/schools/map/{auth_id}/{type}','schoolMap')->middleware('auth:sanctum');
        Route::get('/schools/all/map/{auth_id}','schoolAllMap')->middleware('auth:sanctum');
        Route::post('/school/create','store')->middleware('auth:sanctum');
        Route::get('/school/show/{id}','show')->middleware('auth:sanctum');
        Route::put('/school/update/{id}','update')->middleware('auth:sanctum');
        Route::delete('/school/delete/{id}','delete')->middleware('auth:sanctum');
        Route::post('/school/bulk/import','bulkUploadSchoolInfo')->middleware('auth:sanctum');
        Route::post('/change/school/status/{id}','statusUpdate')->middleware('auth:sanctum');
        Route::get('/bandwidth/usages/school/{uid}','getBandwidthUsage')->middleware('auth:sanctum');
        Route::get('/get/school/info/sarkar/pop/distance/{latitude}/{longitude}/{radiation}','getSchoolInfoSarkarPopDistance')->middleware('auth:sanctum');
    });

    // school tracking system
    Route::controller(SchoolManagerController::class)->group(function(){
        Route::get('/school/managers','index')->middleware('auth:sanctum');
        Route::get('/school/managers/{auth_id}','schoolManagersAccordingToLot')->middleware('auth:sanctum');
        Route::post('/school/manager/create','store')->middleware('auth:sanctum');
        Route::get('/school/manager/show/{uid}','show')->middleware('auth:sanctum');
        Route::put('/school/manager/update/{uid}','update')->middleware('auth:sanctum');
        Route::delete('/school/manager/delete/{uid}','delete')->middleware('auth:sanctum');
        Route::get('/school/managers/permissions','permissions')->middleware('auth:sanctum');
        Route::put('/school/managers/permissions/access/{uid}','access')->middleware('auth:sanctum');
        Route::post('/school/managers/profile/update/{uid}','profileUpdate')->middleware('auth:sanctum');
        Route::put('/school/managers/change/password','passwordUpdate')->middleware('auth:sanctum');
        Route::get('/school/auth/profile/{uid}','profileDetails')->middleware('auth:sanctum');
    });

    // NMS Summary - Common Controller
    Route::controller(NMSCommonController::class)->group(function(){
        Route::get('/nms/total/status/summary/{lot_id}','totalSummary')->middleware('auth:sanctum');
        Route::get('/nms/category/wise/status/summary/{lot_id}','categoryWiseSummary')->middleware('auth:sanctum');
    });

    // NMS Summary - Common Controller
    Route::controller(NMSLotAdminController::class)->group(function(){
        Route::get('/nms/lot/admin/list','list')->middleware('auth:sanctum');
        Route::get('/nms/lot/admin/less/data/list','lessDataList')->middleware('auth:sanctum');
        Route::get('/nms/lot/admin/show/{uid}','show')->middleware('auth:sanctum');
        Route::post('/nms/lot/admin/create','store')->middleware('auth:sanctum');
        Route::put('/nms/lot/admin/status/update/{uid}','statusUpdate')->name('nms_lot_admin.status.update')->middleware('auth:sanctum');
        Route::put('/nms/lot/admin/update/{uid}','update')->name('nms_lot_admin.update')->middleware('auth:sanctum');
        Route::delete('/nms/lot/admin/delete/{id}','destroy')->middleware('auth:sanctum');
    });

    // NMS Category Based Admin Controller
    Route::controller(NMSCategoryBasedAdminController::class)->group(function(){
        Route::get('/nms/category/based/admin/list','list')->middleware('auth:sanctum'); // dcoumention done
        Route::get('/nms/category/based/admin/show/{uid}','show')->middleware('auth:sanctum');
        Route::post('/nms/category/based/admin/create','store')->middleware('auth:sanctum');
        Route::put('/nms/category/based/admin/update/{uid}','update')->middleware('auth:sanctum');
        Route::delete('/nms/category/based/admin/delete/{id}','destroy')->middleware('auth:sanctum');
    });

    // NMS GEOJSON ROUTES
    Route::controller(NMSGeoJsonController::class)->group(function(){
        Route::get('/nms/geo/json/list/{institution_type}','index')->middleware('auth:sanctum');
        Route::get('/nms/geo/json/list/by/types/{lot_uid}','listByIdAndTypes')->middleware('auth:sanctum');
        Route::get('/nms/geo/json/details/{lot_id}/{institution_type}','show')->middleware('auth:sanctum');
        Route::post('/nms/geo/json/create/{auth_id}','store')->middleware('auth:sanctum');
        Route::post('/nms/geo/json/delete/{auth_id}/{id}','delete')->middleware('auth:sanctum');
    });

    Route::controller(SslCommerzBroadbandPaymentController::class)->group(function(){
        Route::post('/pay', [SslCommerzBroadbandPaymentController::class, 'index'])->middleware('auth:sanctum');
        Route::post('/success', [SslCommerzBroadbandPaymentController::class, 'success']);
        Route::post('/fail', [SslCommerzBroadbandPaymentController::class, 'fail']);
        Route::post('/cancel', [SslCommerzBroadbandPaymentController::class, 'cancel']);
        Route::post('/ipn', [SslCommerzBroadbandPaymentController::class, 'ipn']);
    });

    Route::controller(SslCommerzHotspotPaymentController::class)->group(function(){
        Route::post('/hotspot/pay', [SslCommerzHotspotPaymentController::class, 'index'])->middleware('auth:sanctum');
        Route::post('/hotspot/success', [SslCommerzHotspotPaymentController::class, 'success']);
        Route::post('/hotspot/fail', [SslCommerzHotspotPaymentController::class, 'fail']);
        Route::post('/hotspot/cancel', [SslCommerzHotspotPaymentController::class, 'cancel']);
        Route::post('/hotspot/ipn', [SslCommerzHotspotPaymentController::class, 'ipn']);
    });


});
