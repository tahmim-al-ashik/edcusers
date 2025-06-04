<?php

namespace App\Http\Controllers\panel\package;

use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CorporateClient;
use App\Models\CorporateAgent;
use App\Models\CorporateSubAgent;
use App\Models\InternetPackage;
use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\NetworkSupportCenter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    // All Corporate Package List --------------
    public function getAllCorporatePackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list
        $query = InternetPackageCorporate::orderBy('id','desc');
        $packageList = $query->get([
            'id',
            'package_name',
            'package_type',
            'en_title',
            'bn_title',
            'price',
            'upload_speed',
            'download_speed',
            'expiration',
            'is_active',
            'weight'
        ]);

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }

    // All Broadband Corporate Package List --------------
    public function getBroadbandCorporatePackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list
        $query = InternetPackageCorporate::where('package_type','broadband')->orderBy('id','desc');
        $packageList = $query->get([
            'id',
            'package_name',
            'package_type',
            'en_title',
            'bn_title',
            'price',
            'upload_speed',
            'download_speed',
            'expiration',
            'is_active',
            'weight'
        ]);

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }

    // All Wifi Corporate Package List --------------
    public function getWifiCorporatePackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list
        $query = InternetPackageCorporate::where('package_type','wifi')->orderBy('id','desc');
        $packageList = $query->get([
            'id',
            'package_name',
            'package_type',
            'en_title',
            'bn_title',
            'price',
            'upload_speed',
            'download_speed',
            'expiration',
            'is_active',
            'weight'
        ]);

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }

    public function getEdcPackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // fetching list
        $query = InternetPackageCorporate::where('package_type','edc')->orderBy('id','desc');
        $packageList = $query->get();

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }

    // Get Package List According to Client Id
    public function getClientWiseBroadbandCorporatePackageList($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $admin = User::where('id',$uid)->value('base_role');
        if($admin !== 'corporate' && $admin !== 'agent' && $admin !== 'sub_agent'){
            // Fetch the packages from InternetPackageCorporate using the array of package IDs
            $packageList = InternetPackageCorporate::where('package_type','broadband')->orderBy('id','desc')->get();
            $returned_data['status'] = 'success';
            $returned_data['results']['list'] = $packageList;
        }else{
            $client = CorporateClient::where('uid', $uid)->exists();
            $agent = CorporateAgent::where('uid', $uid)->exists();
            $sub_agent = CorporateSubAgent::where('uid', $uid)->exists();

            if($client){
                $packageFromClientTable = CorporateClient::where('uid', $uid)->value('package_list');
            }elseif($agent){
                $editor_id_from_agent = CorporateAgent::where('uid',$uid)->value('client_id');
                $packageFromClientTable = CorporateClient::where('uid', $editor_id_from_agent)->value('package_list');
            }elseif($sub_agent){
                $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$uid)->value('client_id');
                $packageFromClientTable = CorporateClient::where('uid', $editor_id_from_sub_agent)->value('package_list');
            }

            // Convert the package_list to an array if it's a JSON string
            $packageListArray = json_decode($packageFromClientTable, true);

            if (is_array($packageListArray)) {
                // Fetch the packages from InternetPackageCorporate using the array of package IDs
                $packageList = InternetPackageCorporate::whereIn('id', $packageListArray)->where('package_type','broadband')->orderBy('id','desc')->get();

                $returned_data['status'] = 'success';
                $returned_data['results']['list'] = $packageList;
            } else {
                // Handle case where package_list is not in expected format
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Invalid package list format';
            }
        }
        return ResponseWrapper::End($returned_data);
    }

    // Get Package List According to Client Id
    public function getClientWiseWifiCorporatePackageList($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $role = User::where('id',$uid)->value('base_role');
        $packageFromClientTable = '';
        if($role !== 'corporate' && $role !== 'agent' && $role !== 'sub_agent'){
            // Fetch the packages from InternetPackageCorporate using the array of package IDs
            $packageList = InternetPackageCorporate::where('package_type','wifi')->orderBy('id','desc')->get();
            $returned_data['status'] = 'success';
            $returned_data['results']['list'] = $packageList;
        }else{
            $client = CorporateClient::where('uid', $uid)->exists();
            $agent = CorporateAgent::where('uid', $uid)->exists();
            $sub_agent = CorporateSubAgent::where('uid', $uid)->exists();
            if($client){
                $packageFromClientTable = CorporateClient::where('uid', $uid)->value('package_list');
            }elseif($agent){
                $editor_id_from_agent = CorporateAgent::where('uid',$uid)->value('client_id');
                $packageFromClientTable = CorporateClient::where('uid', $editor_id_from_agent)->value('package_list');
            }elseif($sub_agent){
                $editor_id_from_sub_agent = CorporateSubAgent::where('uid',$uid)->value('client_id');
                $packageFromClientTable = CorporateClient::where('uid', $editor_id_from_sub_agent)->value('package_list');
            }

            // Convert the package_list to an array if it's a JSON string
            $packageListArray = json_decode($packageFromClientTable, true);

            if (is_array($packageListArray)) {
                // Fetch the packages from InternetPackageCorporate using the array of package IDs
                $packageList = InternetPackageCorporate::whereIn('id', $packageListArray)->where('package_type','wifi')->orderBy('id','desc')->get();
                $returned_data['status'] = 'success';
                $returned_data['results']['list'] = $packageList;
            } else {
                // Handle case where package_list is not in expected format
                $returned_data['status'] = 'error';
                $returned_data['message'] = 'Invalid package list format';
            }
        }
        return ResponseWrapper::End($returned_data);
    }

    // Create Corporate Package -------------
    public function createNewCorporatePackage(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $validated = $request->validate([
            'en_title' => 'required',
            'package_type' => 'required',
            'bn_title' => 'required',
            'price' => 'required',
            'upload_speed' => 'required',
            'download_speed' => 'required',
            'validity' => 'required',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // create new profile
        $corporatePackage = new InternetPackageCorporate();
        $corporatePackage->package_name = $request->get('en_title');
        $corporatePackage->package_type = $request->get('package_type');
        $corporatePackage->en_title = $request->get('en_title');
        $corporatePackage->bn_title = $request->get('bn_title');
        $corporatePackage->price = $request->get('price');
        $corporatePackage->upload_speed = $request->get('upload_speed');
        $corporatePackage->download_speed = $request->get('download_speed');
        $corporatePackage->expiration = $request->get('validity');
        $corporatePackage->is_active = '1';
        $corporatePackage->save();

        $packageId = InternetPackageCorporate::orderBy('id','desc')->first();

        $returned_data['status'] = 'success';
        $returned_data['response'] = [
            'id' =>  $packageId->id,
            'package_name' =>  $request->get('en_title'),
            'package_type' =>  $request->get('package_type'),
            'en_title' =>  $request->get('en_title'),
            'bn_title' =>  $request->get('bn_title'),
            'price' =>  $request->get('price'),
            'upload_speed' =>  $request->get('upload_speed'),
            'download_speed' =>  $request->get('download_speed'),
            'expiration' =>  $request->get('validity'),
            'status' =>  "Active",
        ];
        return ResponseWrapper::End($returned_data);
    }

    // Update Corporate Package -------------
    public function updateCorporatePackage(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $validated = $request->validate([
            'en_title' => 'required',
            'package_type' => 'required',
            'bn_title' => 'required',
            'price' => 'required',
            'upload_speed' => 'required',
            'download_speed' => 'required',
            'validity' => 'required',
        ]);

        if(!$validated){
            $returned_data['status'] = 'error';
            $returned_data['message'] = "Validation Failed!";
            return ResponseWrapper::End($returned_data);
        }

        // Update user profile
        $package = InternetPackageCorporate::where('id', $id)->first();
        if ($package) {
            $package->update([
                'package_name' => $request->get('en_title'),
                'package_type' => $request->get('package_type'),
                'en_title' => $request->get('en_title'),
                'bn_title' => $request->get('bn_title'),
                'price' => $request->get('price'),
                'upload_speed' => $request->get('upload_speed'),
                'download_speed' => $request->get('download_speed'),
                'expiration' => $request->get('validity')
            ]);
        }

        $packageStatus = InternetPackageCorporate::where('id',$id)->value('is_active');

        $returned_data['status'] = 'success';
        $returned_data['response'] = [
            'package_name' =>  $request->get('en_title'),
            'package_type' =>  $request->get('package_type'),
            'en_title' =>  $request->get('en_title'),
            'bn_title' =>  $request->get('bn_title'),
            'price' =>  $request->get('price'),
            'upload_speed' =>  $request->get('upload_speed'),
            'download_speed' =>  $request->get('download_speed'),
            'expiration' =>  $request->get('validity'),
            'status' =>  $packageStatus === 1 ? "Active" : "Inactive",
        ];
        return ResponseWrapper::End($returned_data);
    }

    // enable disable Corporate Package -------------
    public function enableDisableCorporatePackage(Request $request, $id) : JsonResponse {
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
        $package = InternetPackageCorporate::where('id', $id)->first();
        $package->is_active = $request->get('is_active');
        $package->save();

        $returned_data['status'] = 'success';
        $returned_data['response'] = [
            'is_active' =>  $request->get('is_active') === 1 ? "Active" : "Inactive",
        ];
        return ResponseWrapper::End($returned_data);
    }

    // Delete Corporate Package
    public function deleteCorporatePackage($id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $packageExists = InternetPackageCorporate::where('id', $id)->exists();
        if ($packageExists) {
            try {
                // Delete the package
                $packageDeleted = InternetPackageCorporate::where('id', $id)->delete();

                // Fetch all clients containing the package ID
                $clients = CorporateClient::whereJsonContains('package_list', $id)->get();

                foreach ($clients as $client) {
                    // Remove the package ID from the package_list array
                    $packageList = json_decode($client->package_list, true);
                    if (($key = array_search($id, $packageList)) !== false) {
                        unset($packageList[$key]);
                    }
                    // Update the package_list in the database
                    $client->package_list = json_encode(array_values($packageList)); // re-index the array
                    $client->save();
                }

                $returned_data['results'] = true;
                $returned_data['status'] = 'success';
                $returned_data['message'] = "Package deleted successfully!";
            } catch (\Exception $e) {
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
    public function corporateClientPackageAssign($client_id, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $client = CorporateClient::where('uid', $client_id)->first();
        if($client){
            // Retrieve the package
            $package = CorporateClient::where('uid', $client_id)->first();

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
            $returned_data['message'] = "Client Not Found!";
        }
        return ResponseWrapper::End($returned_data);
    }

    // Get Package List According to Client Id
    public function getShadhinClientWiseBroadbandPackageList($uid) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Finding total Desh User
        $userCount = InternetUsers::where(function($query) use ($uid) {
            $query->where('zone_id', $uid)->orWhere('added_by', $uid);
        })->where('package_id', '511')->where('connection_status', 'active')->count();

        // Log::info($userCount);

        // Assigned Total Allowed Desh User
        $TotalAllowedDeshUser = NetworkSupportCenter::where('uid',$uid)->value('total_desh_package');

        // Log::info($TotalAllowedDeshUser);

        // checking equal or greater
        if($userCount <= $TotalAllowedDeshUser){
            $packageList = InternetPackage::where('type','broadband')->where('id', '!=', '511')->get();
        } else {
            $packageList = InternetPackage::where('type','broadband')->get();
        }

        $returned_data['status'] = 'success';
        $returned_data['results']['list'] = $packageList;
        return ResponseWrapper::End($returned_data);
    }
}
