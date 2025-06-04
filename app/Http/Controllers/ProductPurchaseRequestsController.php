<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\Product;
use App\Models\ProductPurchaseRequests;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductPurchaseRequestsController extends Controller
{
    public function productPurchaseRegistration(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        // $request->validate([
        //     'company_name' => 'required'

        // ]);

        $user = Auth::user();

        if(ProductPurchaseRequests::where('uid', '=', $user->id)->where('product_id', '=', $request->get('product_id'))->where('business_type', '=', $request->get('business_type'))->exists()){
            $returned_data['error_type'] = 'already_registered_this_package';
            return ResponseWrapper::End($returned_data);
        }


        $products = new ProductPurchaseRequests();
        $products->uid = $user->id;
        $products->product_id = $request->get('product_id');
        $products->company_name = $request->get('company_name');
        $products->isp_name = $request->get('isp_name');
        $products->broadband_users = $request->get('broadband_users');
        $products->wifi_users = $request->get('wifi_users');
        $products->business_type = $request->get('business_type');
        $products->internet_bandwidth = $request->get('internet_bandwidth');
        $products->youtube = $request->get('youtube');
        $products->facebook = $request->get('facebook');
        $products->bdix = $request->get('bdix');
        $products->nttn = $request->get('nttn');
        $products->number_of_pop = $request->get('number_of_pop');
        $products->ref_name = $request->get('ref_name');
        $products->ref_mobile_number = $request->get('ref_mobile_number');
        $products->save();

        if($products->id && !empty($request->get('ref_mobile_number'))){
            $pAgentAuthId = $request->get('ref_mobile_number');
            $productId = $request->get('product_id');
            if(!empty($pAgentAuthId)){
                $agentData = User::where('auth_id', '=', $pAgentAuthId)->first();
                if(!empty($agentData)){
                    $agentType = $agentData['base_role'];
                    $productData = Product::where('id', '=', $productId)->first();
                    $commission_type = $productData['commission_type'];
                    $package_price = $productData['price'];

                    $commission_rate = 0;
                    $commission_amount = 0;
                    if($agentType === 'sales_point'){
                        $commission_rate = $productData['sales_point_commission'];
                    } else if($agentType === 'sales_agent'){
                        $commission_rate = $productData['sales_agent_commission'];
                    }
                    //commission rate to amount
                    if($commission_type === 'percentage' && $commission_rate > 0){
                        $commission_amount = ($package_price * $commission_rate) / 100;
                    } else if($commission_rate > 0) {
                        $commission_amount = $commission_rate;
                    }
                    if($commission_amount > 0){
                        (new \App\Classes\CustomHelpers)->create_new_affiliate_history($agentData['id'], 'product_sale', $agentData['id'], $commission_amount);
                    }
                }
            }
        }

        $returned_data['results'] = $products;

        return ResponseWrapper::End($returned_data);
    }
}
