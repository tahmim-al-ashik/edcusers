<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['group_name' => "user", "key_name"=>"user_admin","name"=>"admin"]);
        Permission::create(['group_name' => "user", "key_name"=>"user_view","name"=>"view"]);
        Permission::create(['group_name' => "user", "key_name"=>"user_create","name"=>"create"]);
        Permission::create(['group_name' => "user", "key_name"=>"user_update","name"=>"update"]);
        Permission::create(['group_name' => "user", "key_name"=>"user_delete","name"=>"delete"]);
        Permission::create(['group_name' => "user", "key_name"=>"user_employee_assign","name"=>"employee assign"]);

        Permission::create(['group_name' => "employee", "key_name"=>"employee_admin","name"=>"admin"]);
        Permission::create(['group_name' => "employee", "key_name"=>"employee_view","name"=>"view"]);
        Permission::create(['group_name' => "employee", "key_name"=>"employee_create","name"=>"create"]);
        Permission::create(['group_name' => "employee", "key_name"=>"employee_update","name"=>"update"]);
        Permission::create(['group_name' => "employee", "key_name"=>"employee_delete","name"=>"delete"]);

        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_admin","name"=>"admin"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_view","name"=>"view"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_create","name"=>"create"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_update","name"=>"update"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_delete","name"=>"delete"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_map_view","name"=>"map view"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_zone_assign","name"=>"zone assign"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_send_message","name"=>"send message"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_communication","name"=>"communication"]);
        Permission::create(['group_name' => "support_center", "key_name"=>"support_center_communication_delete","name"=>"communication delete"]);

        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_admin","name"=>"admin"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_view","name"=>"view"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_create","name"=>"create"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_update","name"=>"update"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_delete","name"=>"delete"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_map_view","name"=>"map view"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_zone_assign","name"=>"zone assign"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_communication","name"=>"communication"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_communication_delete","name"=>"communication delete"]);
        Permission::create(['group_name' => "sales_point", "key_name"=>"sales_point_send_message","name"=>"send message"]);

        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_admin","name"=>"admin"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_view","name"=>"view"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_create","name"=>"create"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_update","name"=>"update"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_delete","name"=>"delete"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_map_view","name"=>"map view"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_zone_assign","name"=>"zone assign"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_communication","name"=>"communication"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_communication_delete","name"=>"communication delete"]);
        Permission::create(['group_name' => "sales_agent", "key_name"=>"sales_agent_send_message","name"=>"send message"]);

        Permission::create(['group_name' => "internet_user", "key_name"=>"internet_user_admin","name"=>"admin"]);
        Permission::create(['group_name' => "internet_user", "key_name"=>"internet_user_view","name"=>"view"]);
        Permission::create(['group_name' => "internet_user", "key_name"=>"internet_user_update","name"=>"update"]);
        Permission::create(['group_name' => "internet_user", "key_name"=>"internet_user_status_zone_update","name"=>"status / zone update"]);
        Permission::create(['group_name' => "internet_user", "key_name"=>"internet_user_map_view","name"=>"map view"]);
        Permission::create(['group_name' => "internet_user", "key_name"=>"internet_user_support","name"=>"support"]);
        Permission::create(['group_name' => "internet_user", "key_name"=>"internet_user_send_message","name"=>"send message"]);

        Permission::create(['group_name' => "location", "key_name"=>"location_admin","name"=>"admin"]);
        Permission::create(['group_name' => "location", "key_name"=>"location_view","name"=>"view"]);
        Permission::create(['group_name' => "location", "key_name"=>"location_create","name"=>"create"]);
        Permission::create(['group_name' => "location", "key_name"=>"location_update","name"=>"update"]);
        Permission::create(['group_name' => "location", "key_name"=>"location_delete","name"=>"delete"]);

        Permission::create(['group_name' => "transmission", "key_name"=>"transmission_admin","name"=>"admin"]);
        Permission::create(['group_name' => "transmission", "key_name"=>"transmission_view","name"=>"view"]);
        Permission::create(['group_name' => "transmission", "key_name"=>"transmission_create","name"=>"create"]);
        Permission::create(['group_name' => "transmission", "key_name"=>"transmission_update","name"=>"update"]);
        Permission::create(['group_name' => "transmission", "key_name"=>"transmission_delete","name"=>"delete"]);

        Permission::create(['group_name' => "career", "key_name"=>"career_admin","name"=>"admin"]);
        Permission::create(['group_name' => "career", "key_name"=>"career_view","name"=>"view"]);
        Permission::create(['group_name' => "career", "key_name"=>"career_create","name"=>"create"]);
        Permission::create(['group_name' => "career", "key_name"=>"career_update","name"=>"update"]);
        Permission::create(['group_name' => "career", "key_name"=>"career_delete","name"=>"delete"]);
        Permission::create(['group_name' => "career", "key_name"=>"career_communication","name"=>"communication"]);
        Permission::create(['group_name' => "career", "key_name"=>"career_communication_delete","name"=>"communication delete"]);
    }
}
