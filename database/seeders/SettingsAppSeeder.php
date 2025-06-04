<?php

namespace Database\Seeders;

use App\Models\SettingsApp;
use Illuminate\Database\Seeder;

class SettingsAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SettingsApp::create(['platform'=>'android', 'version'=> 100]);
    }
}
