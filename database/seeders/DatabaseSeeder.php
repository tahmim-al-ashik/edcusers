<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            SettingsAppSeeder::class,
            RolesSeeder::class,
            UserRolesSeeder::class,
            EnBnValueListSeeders::class,
            EmployeeDesignationSeeder::class,
            UserSeeder::class,
            UserProfileSeeder::class,
            InternetPackageSeeder::class,
            ServicesSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
