<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkSupportCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'zone_name',
        'is_test_mode',
        'simultaneous_use_disable',
        'zone_id',
        'zone_ip',
        'zone_username',
        'zone_password',
        'center_type',
        'sub_centers',
        'package_list',
        'opening_package_id',
        'total_desh_package',
        'coverage_type',
        'coverage_ids',
        'support_number',
        'whatsapp_number',
        'email',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id',
        'address',
        'latitude',
        'longitude',
        'status',
        'data_object',
        'updated_by'
    ];
}
