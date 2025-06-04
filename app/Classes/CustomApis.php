<?php

namespace App\Classes;

use Illuminate\Support\Facades\Log;

class CustomApis {

    public function CurlRequest($url, $data){

        $url=curl_init($url);
        $credentials=json_encode($data);
        $header=array('Content-Type:application/json');
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_POSTFIELDS, $credentials);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
        $resultData = curl_exec($url);
        curl_close($url);
        return json_decode($resultData, true);
    }

    public function RadiusUrls($url_key, $api_version = 'v1') : string {

//        $prefix = 'https://radius.shadhinwifi.com/api/'.$api_version;
        $prefix = 'http://10.0.0.52:8007/api/'.$api_version;

        $urls = [
            'userRegister' => '/user_register',
            'getUserPassword' => '/get_user_password',
        ];

        return $prefix . $urls[$url_key];
    }

}
