<?php

namespace App\Http\Controllers\panel\location;

use App\Http\Controllers\Controller;
use App\Models\GeoDivision;
use Illuminate\Http\Request;

class GeoDivisionController extends Controller
{
    public function sharedIndex(Request $request){
        $columns = ['*'];
        if(isset($request->language) && $request->language === 'en'){
            $columns[] = 'en_name as name';
        } else if(isset($request->language) && $request->language === 'bn'){
            $columns[] = 'bn_name as name';
        }
        return GeoDivision::all($columns);
    }
}
