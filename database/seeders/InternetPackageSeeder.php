<?php

namespace Database\Seeders;

use App\Models\InternetPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class InternetPackageSeeder extends Seeder
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
            InternetPackage::create([
                'id'=> 1,
                'mikrotik_radius_group_name' => 'DIGITAL-FAIR-VISITOR',
                'en_title' => 'DIGITAL FAIR',
                'bn_title' => 'ডিজিটাল মেলা',
                'type' => 'wifi',
                'zone_id' => null,
                'price' => 0.0,
                'expiration' => 4320,
                'sales_point_commission' => 0.0,
                'sales_agent_commission' => 0.0,
                'user_points' => 0,
                'is_active' => 0,
                'skip_from_display' => 0,
                'weight' => 0
            ]);
            InternetPackage::create([
                'id'=> 2,
                'mikrotik_radius_group_name' => 'SHADHIN',
                'en_title' => 'Shadhin',
                'bn_title' => 'স্বাধীন',
                'type' => 'wifi',
                'zone_id' => null,
                'price' => 155.0,
                'expiration' => 43200,
                'sales_point_commission' => 10.0,
                'sales_agent_commission' => 5.0,
                'user_points' => 155,
                'is_active' => 1,
                'skip_from_display' => 0,
                'weight' => 101
            ]);
            InternetPackage::create([
                'id'=> 3,
                'mikrotik_radius_group_name' => 'Student',
                'en_title' => 'Student',
                'bn_title' => 'স্টুডেন্ট',
                'type' => 'broadband',
                'zone_id' => null,
                'price' => 685.0,
                'expiration' => 43200,
                'sales_point_commission' => 10.0,
                'sales_agent_commission' => 5.0,
                'user_points' => 685,
                'is_active' => 1,
                'skip_from_display' => 0,
                'weight' => 111
            ]);
            InternetPackage::create([
                'id'=> 4,
                'mikrotik_radius_group_name' => 'Home',
                'en_title' => 'Home',
                'bn_title' => 'হোম',
                'type' => 'broadband',
                'zone_id' => null,
                'price' => 995.0,
                'expiration' => 4320,
                'sales_point_commission' => 10.0,
                'sales_agent_commission' => 5.0,
                'user_points' => 995,
                'is_active' => 1,
                'skip_from_display' => 0,
                'weight' => 121
            ]);
            InternetPackage::create([
                'id'=> 5,
                'mikrotik_radius_group_name' => 'FREE-PACKAGE',
                'en_title' => 'Welcome',
                'bn_title' => 'ওয়েলকাম',
                'type' => 'wifi',
                'zone_id' => null,
                'price' => 0.0,
                'expiration' => 30,
                'sales_point_commission' => 0.0,
                'sales_agent_commission' => 0.0,
                'user_points' => 0,
                'is_active' => 1,
                'skip_from_display' => 0,
                'weight' => 131
            ]);
            InternetPackage::create([
                'id'=> 6,
                'mikrotik_radius_group_name' => 'BANASREE-OPENING-PACKAGE',
                'en_title' => 'HAPPY OPENING',
                'bn_title' => 'শুভ উদ্বোধন',
                'type' => 'wifi',
                'zone_id' => null,
                'price' => 0.0,
                'expiration' => 4320,
                'sales_point_commission' => 0.0,
                'sales_agent_commission' => 0.0,
                'user_points' => 0,
                'is_active' => 0,
                'skip_from_display' => 1,
                'weight' => 141
            ]);
        }
    }
}
