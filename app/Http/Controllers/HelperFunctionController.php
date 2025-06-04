<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelperFunctionController extends Controller
{
    public function StringReplace($find,$replace,$string){
        return str_replace($find,$replace,$string);
    }
}
