<?php

namespace Database\Seeders;

use App\Models\CareerResumesType;
use Illuminate\Database\Seeder;

class CareerResumeType extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CareerResumesType::create(['career_name_bn'=>'কর্পোরেট সাপোর্ট', 'career_name_en'=> 'Corporate Support', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'হোম ইউজার সাপোর্ট', 'career_name_en'=> 'Home User Support', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'ট্রান্সমিশন সাপোর্ট', 'career_name_en'=> 'Transmission Support', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'লোকাল সাপোর্ট', 'career_name_en'=> 'Local Support', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'নেটওয়ার্ক মনিটরিং', 'career_name_en'=> 'Network Monitoring', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'নেটওয়ার্ক ইঞ্জিনিয়ার', 'career_name_en'=> 'Network Engineer', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'ট্রান্সমিশন ইঞ্জিনিয়ার', 'career_name_en'=> 'Transmission Engineer', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'ক্যাবল টেকনিশিয়ান', 'career_name_en'=> 'Cable Technician', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'কল সেন্টার', 'career_name_en'=> 'Call Center', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'টেলি সেলস', 'career_name_en'=> 'Tele Sales', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'সেলস মিটিং', 'career_name_en'=> 'Sales Meeting', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'রিজিওনাল মার্কেটিং', 'career_name_en'=> 'Regional Marketing', 'is_active'=>1]);
        CareerResumesType::create(['career_name_bn'=>'মার্কেট সার্ভে', 'career_name_en'=> 'Market Survey', 'is_active'=>1]);
    }
}
