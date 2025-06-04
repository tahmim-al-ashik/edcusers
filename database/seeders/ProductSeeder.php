<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::create(['id'=>1, 'title'=>'erp software', 'category_id'=> 1, 'is_featured'=>1, 'price'=> 0, 'sales_point_commission'=>0, 'sales_agent_commission'=>0, 'commission_type'=>'percentage']);
        Product::create(['id'=>2, 'title'=>'wifi software', 'category_id'=> 1, 'is_featured'=>1, 'price'=> 0, 'sales_point_commission'=>0, 'sales_agent_commission'=>0, 'commission_type'=>'percentage']);
    }
}
