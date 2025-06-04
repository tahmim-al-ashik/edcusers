<?php

namespace App\Http\Controllers\panel\package;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use App\Models\InternetPackage;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\BroadbandDbSecret;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ShadhinPackageController extends Controller
{
    // All Shadhin Package List --------------
    public function getAllShadhinPackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list
        $query = InternetPackage::orderBy('id','desc');
        $packageList = $query->get([
            'id',
            'mikrotik_radius_group_name',
            'en_title',
            'bn_title',
            'type',
            'zone_id',
            'price',
            'price_bn',
            'expiration',
            'expiration_bn',
            'is_active',
            'weight'
        ]);

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }

    // All Broadband Shadhin Package List --------------
    public function getBroadbandShadhinPackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list
        $query = InternetPackage::where('type','broadband')->orderBy('id','desc');
        $packageList = $query->get([
            'id',
            'mikrotik_radius_group_name',
            'en_title',
            'bn_title',
            'type',
            'zone_id',
            'price',
            'price_bn',
            'expiration',
            'expiration_bn',
            'is_active',
            'weight'
        ]);

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }

    // All Wifi Shadhin Package List --------------
    public function getWifiShadhinPackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list
        $query = InternetPackage::where('type','wifi')->orderBy('price','asc');
        $packageList = $query->get([
            'id',
            'mikrotik_radius_group_name',
            'en_title',
            'bn_title',
            'type',
            'zone_id',
            'price',
            'price_bn',
            'expiration',
            'expiration_bn',
            'is_active',
            'weight'
        ]);

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }

    // Get Package List According to zone Id
    public function getSupportCenterWiseBroadbandPackageList($zone_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $packageFromSupportCenterTable = NetworkSupportCenter::where('zone_id', $zone_id)->value('package_list');
        // Convert the package_list to an array if it's a JSON string
        $packageListArray = json_decode($packageFromSupportCenterTable, true);

        if (is_array($packageListArray)) {
            // Fetch the packages from InternetPackageCorporate using the array of package IDs
            $packageList = InternetPackage::whereIn('id', $packageListArray)->where('type','broadband')->where('is_active', '1')->orderBy('price', 'ASC')->get();

            $returned_data['status'] = 'success';
            $returned_data['results']['list'] = $packageList;
        } else {
            // Handle case where package_list is not in expected format
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Invalid package list format';
        }
        return ResponseWrapper::End($returned_data);
    }

    // Get Package List According to Client Id
    public function getSupportCenterWiseHotspotPackageList($zone_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $packageFromSupportTable = NetworkSupportCenter::where('zone_id', $zone_id)->value('package_list');

        // Convert the package_list to an array if it's a JSON string
        $packageListArray = json_decode($packageFromSupportTable, true);

        if (is_array($packageListArray)) {
            // Fetch the packages from InternetPackageCorporate using the array of package IDs
            $packageList = InternetPackage::whereIn('id', $packageListArray)->where('type','wifi')->orderBy('price','ASC')->get();

            $returned_data['status'] = 'success';
            $returned_data['results']['list'] = $packageList;
        } else {
            // Handle case where package_list is not in expected format
            $returned_data['status'] = 'error';
            $returned_data['message'] = 'Invalid package list format';
        }

        return ResponseWrapper::End($returned_data);
    }

    // Create Shadhin Package -------------
    public function createNewShadhinPackage(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'en_title' => 'required',
            'bn_title' => 'required',
            'type' => 'required',
            'price' => 'required',
            'validity' => 'required',
            'validity_bn' => 'required',
        ],[
            'en_title.required' => 'Package name is must.',
            'bn_title.required' => 'Bangla title is must.',
            'type.required' => 'Package type is must.',
            'price.required' => 'Price is must.',
            'validity.required' => 'Validity is must.',
            'validity_bn.required' => 'Bangla validity is must.',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // create new profile
        $shadhinPackage = new InternetPackage();
        $shadhinPackage->mikrotik_radius_group_name = $request->get('en_title');
        $shadhinPackage->en_title = $request->get('en_title');
        $shadhinPackage->bn_title = $request->get('bn_title');
        $shadhinPackage->type = $request->get('type');
        $shadhinPackage->price = $request->get('price');
        $shadhinPackage->price_bn = $request->get('price_bn');
        $shadhinPackage->expiration = $request->get('validity');
        $shadhinPackage->expiration_bn = $request->get('validity_bn');
        $shadhinPackage->is_active = '1';
        $shadhinPackage->save();

        $packageId = InternetPackage::orderBy('id','desc')->first();

        $returned_data['status'] = 'success';
        $returned_data['response'] = [
            'id' =>  $packageId->id,
            'mikrotik_radius_group_name' =>  $request->get('en_title'),
            'en_title' =>  $request->get('en_title'),
            'bn_title' =>  $request->get('bn_title'),
            'type' =>  $request->get('type'),
            'price' =>  $request->get('price'),
            'price_bn' =>  $request->get('price_bn'),
            'expiration' =>  $request->get('validity'),
            'expiration_bn' =>  $request->get('validity_bn'),
            'status' =>  "Active",
        ];
        return ResponseWrapper::End($returned_data);
    }

    // Update Shadhin Package -------------
    public function updateShadhinPackage(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'en_title' => 'required',
            'type' => 'required',
            'bn_title' => 'required',
            'price' => 'required',
            'validity' => 'required',
            'validity_bn' => 'required',
        ],[
            'en_title.required' => 'Package name is must.',
            'type.required' => 'Package type is must.',
            'bn_title.required' => 'Bangla title is must.',
            'price.required' => 'Price is must.',
            'validity.required' => 'Validity is must.',
            'validity_bn.required' => 'Bangla validity is must.',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Update user profile
        $package = InternetPackage::find($id);
        if ($package) {
            $package->update($request->all());
        }

        $packageStatus = InternetPackage::where('id',$id)->value('is_active');

        $returned_data['status'] = 'success';
        $returned_data['response'] = [
            'mikrotik_radius_group_name' =>  $request->get('en_title'),
            'en_title' =>  $request->get('en_title'),
            'bn_title' =>  $request->get('bn_title'),
            'type' =>  $request->get('type'),
            'price' =>  $request->get('price'),
            'price_bn' =>  $request->get('price_bn'),
            'expiration' =>  $request->get('validity'),
            'expiration_bn' =>  $request->get('validity_bn'),
            'status' =>  $packageStatus === 1 ? "Active" : "Inactive",
        ];
        return ResponseWrapper::End($returned_data);
    }

    // enable disable Shadhin Package -------------
    public function enableDisableShadhinPackage(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'is_active' => 'required'
        ],[
            'is_active.required' => 'Status is must.'
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Update user profile
        $package = InternetPackage::where('id', $id)->first();
        $package->is_active = $request->get('is_active');
        $package->save();

        $returned_data['status'] = 'success';
        $returned_data['response'] = [
            'is_active' =>  $request->get('is_active') === 1 ? "Active" : "Inactive",
        ];
        return ResponseWrapper::End($returned_data);
    }

    // Delete Shadhin Package
    public function deleteShadhinPackage($id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $packageExists = InternetPackage::where('id', $id)->exists();

        if ($packageExists) {
            DB::beginTransaction();
            try {
                // Delete the package
                $packageDeleted = InternetPackage::where('id', $id)->delete();

                // Fetch all clients containing the package ID
                $supportCenters = NetworkSupportCenter::whereJsonContains('package_list', $id)->get();

                foreach ($supportCenters as $supportCenter) {
                    // Remove the package ID from the package_list array
                    $packageList = json_decode($supportCenter->package_list, true);
                    if (($key = array_search($id, $packageList)) !== false) {
                        unset($packageList[$key]);
                    }
                    // Update the package_list in the database
                    $supportCenter->package_list = json_encode(array_values($packageList)); // re-index the array
                    $supportCenter->save();
                }

                DB::commit();

                $returned_data['results'] = true;
                $returned_data['status'] = 'success';
                $returned_data['message'] = "Package deleted successfully!";
            } catch (\Exception $e) {
                DB::rollBack();
                $returned_data['results'] = false;
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Try again, something went wrong!";
            }
        } else {
            $returned_data['results'] = false;
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Package not found.";
        }

        return ResponseWrapper::End($returned_data);
    }

    // Package Assign
    public function shadhinPackageAssign($zone_id, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $supportCenter = NetworkSupportCenter::where('zone_id', $zone_id)->first();

        if($supportCenter){
            // Retrieve the package
            $package = NetworkSupportCenter::where('zone_id', $zone_id)->first();

            if($package) {
                // Decode the JSON package_list into a PHP array
                $packageList = json_decode($package->package_list, true);

                // If the package_list is null, initialize it as an empty array
                if (is_null($packageList)) {
                    $packageList = [];
                }

                // Check if the ID exists in the array
                if (in_array($id, $packageList)) {
                    // If the ID exists, remove it
                    $packageList = array_values(array_diff($packageList, [$id]));
                    $message = "Package removed successfully!";
                } else {
                    // If the ID does not exist, add it
                    $packageList[] = $id;
                    $message = "Package added successfully!";
                }

                // Re-encode the array into JSON
                $package->package_list = json_encode($packageList);
                $package->save();

                $returned_data['results'] = true;
                $returned_data['status'] = 'success';
                $returned_data['message'] = $message;
            } else {
                $returned_data['results'] = false;
                $returned_data['status'] = 'error';
                $returned_data['message'] = "Package Not Found!";
            }
        } else {
            $returned_data['results'] = false;
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Support Center Not Found!";
        }
        return ResponseWrapper::End($returned_data);
    }

    // Get Package List According to Client Id
    public function getShadhinClientWiseBroadbandPackageList($zone_id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Finding total Desh User
        $zone_name = NetworkSupportCenter::where('zone_id',$zone_id)->value('zone_name');

        // Finding total Desh User
        $userCount = BroadbandDbSecret::where('zone', $zone_name)->where('profile', 'Desh')->count();

        //Log::info($userCount);

        // Assigned Total Allowed Desh User
        $TotalAllowedDeshUser = NetworkSupportCenter::where('zone_id',$zone_id)->value('total_desh_package');
        //Log::info($TotalAllowedDeshUser);
        // checking equal or greater
        if($userCount >= $TotalAllowedDeshUser){
            $packageList = InternetPackage::where('type','broadband')->where('id', '!=', '511')->where('is_active', '1')->orderBy('price', 'ASC')->get();
        } else {
            $packageList = InternetPackage::where('type','broadband')->where('is_active', '1')->orderBy('price', 'ASC')->get();
        }

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }
}
