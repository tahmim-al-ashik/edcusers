<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $base_items = Config::get('constants.roles_designations');

        foreach ($base_items as $item) {
            DB::table('roles')->updateOrInsert(
                ['id' => $item['id']],
                ['name' => $item['title']]
            );
        }
    }
}
