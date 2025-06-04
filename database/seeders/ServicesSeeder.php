<?php

namespace Database\Seeders;

use App\Models\InternetPackage;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $base_users = Config::get('constants.base_users');
        foreach ($base_users as $buser){
            Service::create(['id'=> 1, 'en_title' => 'Support & Network Center Link UP', 'bn_title' => 'সাপোর্ট এন্ড নেটওয়ার্ক সেন্টার লিংক আপ', 'service_group' => 'network_support_center', 'price' => 250000.0, 'sales_point_commission' => 10000.0, 'sales_agent_commission' => 10000.0, 'commission_type' => 'flat', 'is_active' => 1]);
            Service::create(['id'=> 2, 'en_title' => 'Support Center', 'bn_title' => 'সাপোর্ট সেন্টার', 'service_group' => 'network_support_center', 'price' => 585000.0, 'sales_point_commission' => 11700.0, 'sales_agent_commission' => 11700.0, 'commission_type' => 'flat', 'is_active' => 1]);
            Service::create(['id'=> 3, 'en_title' => 'Support Center', 'bn_title' => 'সাপোর্ট সেন্টার', 'service_group' => 'network_support_center', 'price' => 825000.0, 'sales_point_commission' => 16500.0, 'sales_agent_commission' => 16500.0, 'commission_type' => 'flat', 'is_active' => 1]);
            Service::create(['id'=> 4, 'en_title' => 'Network Center', 'bn_title' => 'নেটওয়ার্ক সেন্টার', 'service_group' => 'network_support_center', 'price' => 1185000.00, 'sales_point_commission' => 23700.0, 'sales_agent_commission' => 23700.0, 'commission_type' => 'flat', 'is_active' => 1]);
        }
    }
}
