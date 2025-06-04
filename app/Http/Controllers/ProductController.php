<?php

namespace App\Http\Controllers;

use App\Classes\ResponseWrapper;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getFeaturedProductList(Request $request) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $products = Product::query();
        $products->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id');
        $products->where('is_featured', '=', 1);
        $dataList = $products->get(['products.id','products.title', 'products.price', 'product_categories.en_name as category_en_name', 'product_categories.bn_name as category_bn_name']);

        $returned_data['results'] = $dataList;

        return ResponseWrapper::End($returned_data);
    }
}
