<?php

namespace Database\Seeders;

use App\Models\CorporateSettings;
use Illuminate\Database\Seeder;

class CorporateSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CorporateSettings::create(['settings_name'=>'ErpAccess', 'settings_value'=> '#Erp_Password@', 'settings_type'=>'mikrotik_credentials']);
    }
}
