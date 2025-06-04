<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use App\Models\MessageAndNotification;
use App\Models\SettingsApp;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

class AppsWelcomeScreenController extends Controller
{
    public function appWelcomeScreen(Request $request) : JsonResponse {

        $user_auth_id = $request->get('auth_id');
        $profile_update_pending = true;
        $uid = (new \App\Classes\CustomHelpers)->getUidByAuthId($user_auth_id);
        $user_profile = null;
        if(!empty($user_auth_id)){
            $user_profile_data = UserProfile::where('uid', '=', $uid)->first(['full_name','mobile_number','division_id']);
            if(!empty($user_profile_data)){
                $user_profile = $user_profile_data;
                if($user_profile_data['division_id'] !== null){
                    $profile_update_pending = false;
                }
            }
        }

        $slides = array(
            array(
                "title"=>"ছাত্র ছাত্রীদের প্রয়োজন অনুযায়ী সাজানো হয়েছে স্টুডেন্ট প্যাকেজ",
                "image_url"=>url("https://files.shadhinwifi.com/shared/slideshow/slide_1.jpeg"),
                "info_title"=>"",
                "info_id"=>"",
                "details"=> 0
            ),
            array(
                "title"=>"নতুন উদ্যোক্তাদের সহযোগী - স্বাধীন ওয়াই-ফাই",
                "image_url"=>"https://files.shadhinwifi.com/shared/slideshow/slide_2.jpeg",
                "info_title"=>"",
                "info_id"=>"",
                "details"=> 0
            ),
            array(
                "title"=>"স্বাধীন ওয়াই-ফাই এখন গ্রাম বাংলার প্রতিটি প্রান্তে",
                "image_url"=>"https://files.shadhinwifi.com/shared/slideshow/slide_3.jpeg",
                "info_title"=>"",
                "info_id"=>"",
                "details"=> 0
            ),
            array(
                "title"=>"ওয়াই-ফাই প্রযুক্তি আপনার ব্যবসার আরো প্রসার ঘটাবে",
                "image_url"=>"https://files.shadhinwifi.com/shared/slideshow/slide_4.jpeg",
                "info_title"=>"",
                "info_id"=>"",
                "details"=> 0
            )
        );
        //Log::info($user_profile);
        $app_version = SettingsApp::where('platform', '=', strtolower(trim($request->get('platform'))))->value('version');

        return response()->json([
            "profile_update_pending" => $profile_update_pending,
            "slides" => $slides,
            "app_version" =>$app_version,
            "unread_message" => MessageAndNotification::where('uid', '=', $uid)->where('is_read', '=', 0)->count(),
            "user_profile" => $user_profile,
            "network_center_message" => "বাংলাদেশের মানুষের ঘরে ঘরে ইন্টারনেট সেবা পৌছে দেওয়ার লক্ষে প্রতিটি গ্রামে তৈরি হচ্ছে সাপোর্ট সেন্টার। আপনি উদ্দমী এবং দূরদর্শী হলে আপনার গ্রামে সাপোর্ট সেন্টার নিতে আজই যোগাযোগ করুন।",
            "sales_point_message" => 'অল্প সময়ে আপনার ব্যবসাকে আর্থিক ভাবে সমৃদ্ধ করতে আপনার ব্যবসার স্থানকে স্বাধীন ওয়াই-ফাই "সেলস পয়েন্ট" হিসেবে অন্তর্ভুক্ত করে নিজেকে আর্থিক ভাবে সমৃদ্ধ করতে পারেন।',
            "part_time_job_message" => 'অল্প সময়ে আপনার নিজ এলাকায় স্বাধীন ওয়াই-ফাই "রেফারেল প্রোগ্রাম" -এ সংযুক্ত হয়ে নিজেকে আর্থিক ভাবে সমৃদ্ধ করতে পারেন।'
        ]);
    }
}
