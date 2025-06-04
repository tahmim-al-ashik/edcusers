<?php

namespace Database\Seeders;

use App\Models\AgentSettings;
use Illuminate\Database\Seeder;

class AgentSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AgentSettings::create(['id'=>1,'sp_ipmc'=>3, 'sa_ipmc'=>2]);
    }
}
