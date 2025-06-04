<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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
            User::create([
                'id'=> $buser['id'],
                'auth_id' => $buser['auth_id'],
                'base_role' => $buser['base_role'],
                'password' => Hash::make($buser['password']),
                'panel_access' => true,
            ]);
        }
    }
}
