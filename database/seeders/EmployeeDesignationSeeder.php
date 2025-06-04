<?php

namespace Database\Seeders;

use App\Models\EmployeeDesignation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class EmployeeDesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $base_items = Config::get('constants.roles_designations');
        foreach ($base_items as $item){
            EmployeeDesignation::create(['id'=>$item['id'], 'name'=> $item['title']]);
        }
    }
}
