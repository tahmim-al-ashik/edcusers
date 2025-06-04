<?php

namespace App\Http\Controllers\panel\user;

use App\Classes\CustomHelpers;
use App\Classes\ResponseWrapper;
use App\Http\Controllers\Controller;
use App\Models\BroadbandDbProfile;
use App\Models\BroadbandDbSubscriberInfo;
use App\Models\InternetPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BroadbandUserRegistrationController extends Controller {

    // Broadband Package list
    public function getBroadbandPackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Getting broadband package only
        $packageList = InternetPackage::where('type', 'broadband')->where('is_active','1')->where('skip_from_display','0');

        // Prepare the response data
        $returned_data['results']['list'] = $packageList->get(['id', 'en_title', 'bn_title', 'price']);

        // Return the response
        return ResponseWrapper::End($returned_data);
    }

    // Broadband Package list
    public function getWiFiPackageList() : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        // Getting broadband package only
        $packageList = InternetPackage::where('type', 'wifi')->where('is_active','1')->where('skip_from_display','0');

        // Prepare the response data
        $returned_data['results']['list'] = $packageList->get(['id', 'en_title', 'bn_title', 'price']);

        // Return the response
        return ResponseWrapper::End($returned_data);
    }

    // Store data from User Form.
    public function broadbandUserStore(Request $request) : JsonResponse {
        $returned_data = ResponseWrapper::Start();

        $this->validate($request, [
            'serial'=>'required|numeric',
            'pop_name'=>'required',
            'date'=>'required|date_format:Y-m-d',
            'zone_name'=>'required',
            'referer_name'=>'required',
            'referer_mobile'=>'required|numeric',
            'mobile' => 'required|numeric',
            'mobile' => 'required|numeric',
            'package'=>'required',
            'customer_name'=>'required',
            'gender'=>'required',
            'installation_cost'=>'required|numeric',
            'billing_address'=>'required',
            'village'=>'required',
            'post_code'=>'required',
            'upazila'=>'required',
            'district'=>'required',
            'division'=>'required',
            'billing_address'=>'required',
            'nid_passport'=>'required',
            'mobile' =>'required|numeric',
            'email'=>'required|email',
            'user_type'=>'required',
            'connection_media'=>'required',
        ],[
            'serial.required' => 'Must have serial!',
            'serial.numeric' => 'Must have numeric serial!',
            'pop_name.required' => 'Must have pop name!',
            'date.required' => 'Must have date!',
            'date.date_format' => 'Must follow date format YYYY-MM-DD!',
            'zone_name.required' => 'Must have zone name!',
            'referer_name.required' => 'Must have marketer name!',
            'referer_mobile.required' => 'Must have marketer mobile number!',
            'referer_mobile.numeric' => 'Mobile number should be numeric!',
            'mobile.required' => 'Must have username!',
            'mobile.regex' => 'Your mobile number format is invalid!',
            'mobile.unique' => 'This number is already in use!',
            'package.required' => 'Must have selected your package!',
            'customer_name.required' => 'Must have customer name!',
            'gender.required' => 'Must have selected your gender!',
            'installation_cost.required' => 'Must have installment cost!',
            'installation_cost.numeric' => 'Installation cost should be numeric!',
            'billing_address.required' => 'Must have home!',
            'village.required' => 'Must have village!',
            'post_code.required' => 'Must have post office!',
            'upazila.required' => 'Must have police station!',
            'district.required' => 'Must have district!',
            'division.required' => 'Must have division!',
            'billing_address.required' => 'Must have billing address!',
            'nid_passport.required' => 'Must have NID or Passport number!',
            'mobile.required' => 'Must have contact number!',
            'mobile.numeric' => 'Must have numeric contact number!',
            'email.required' => 'Must have email!',
            'email.email' => 'Must have valid email!',
            'user_type.required' => 'Must have user type!',
            'connection_media.required' => 'Must have connection media!',
        ]);

        $mobile = BroadbandDbSubscriberInfo::where('numAsId', $request->mobile)->first();
        if($mobile) {
            // Update the record
            $mobile->update([
                'serial' => $request->serial,
                'popId' => $request->pop_name,
                'date' => $request->date,
                'zone_name' => $request->zone_name,
                'm_name' => $request->referer_name,
                'm_mobile' => $request->referer_mobile,
                'numAsId' => $request->mobile,
                'packageId' => $request->package,
                'customerName' => $request->customer_name,
                'gender' => $request->gender,
                'instcost' => $request->installation_cost,
                'home' => $request->billing_address,
                'village' => $request->village,
                'post_office' => $request->post_code,
                'police_station' => $request->upazila,
                'district' => $request->district,
                'division' => $request->division,
                'billingAddress' => $request->billing_address,
                'nid' => $request->nid_passport,
                'numOne' => $request->mobile,
                'email' => $request->email,
                'UserType' => $request->user_type,
                'connectionMedia' => $request->connection_media,
                'acativation_date' => Carbon::now(),
            ]);
        } else {
            // Saving Data to Database -------------
            BroadbandDbSubscriberInfo::create([
                'serial' => $request->serial,
                'popId' => $request->pop_name,
                'date' => $request->date,
                'zone_name' => $request->zone_name,
                'm_name' => $request->referer_name,
                'm_mobile' => $request->referer_mobile,
                'numAsId' => $request->mobile,
                'packageId' => $request->package,
                'customerName' => $request->customer_name,
                'gender' => $request->gender,
                'instcost' => $request->installation_cost,
                'home' => $request->billing_address,
                'village' => $request->village,
                'post_office' => $request->post_code,
                'police_station' => $request->upazila,
                'district' => $request->district,
                'division' => $request->division,
                'billingAddress' => $request->billing_address,
                'nid' => $request->nid_passport,
                'numOne' => $request->mobile,
                'email' => $request->email,
                'UserType' => $request->user_type,
                'connectionMedia' => $request->connection_media,
                'acativation_date' => Carbon::now(),
            ]);
        }
        
        $packages = BroadbandDbProfile::where('name', $request->package)->first();

        // Finding the days left of the current month
        $currentDayOfMonth = date('j');
        $totalDaysInMonth = date('t');
        $daysleft = ($totalDaysInMonth-$currentDayOfMonth)+1;

        // Calculating package price 
        $price = $packages->price;
        $packageFinalFloat = $daysleft*($price / $totalDaysInMonth);
        $finalPackagePrice = (int)$packageFinalFloat;

        // Prepare the response data
        $returned_data['results']['list'] = [
            'username' => $request->mobile,
            'package_name' => $request->package,
            'zone_name' => $request->zone_name,
            'package_price' => $packages->price,
            'daysleft' => $daysleft,
            'payable_package_price' => $finalPackagePrice,
        ];

        // Return the response
        return ResponseWrapper::End($returned_data);

    }
    
}
