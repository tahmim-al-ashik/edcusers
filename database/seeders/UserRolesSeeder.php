<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        $base_users = Config::get('constants.base_users');
        foreach ($base_users as $buser){
            foreach ($buser['rid'] as $role){
                UserRole::create(['uid'=> $buser['id'], 'rid' => $role]);
            }
        }
    }
}
