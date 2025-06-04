<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateClient extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'company_name',
        'zone_name',
        'client_type',
        'company_name',
        'village_name',
        'union_name',
        'mikrotik_ip',
        'mikrotik_username',
        'mikrotik_password',
        'hotspot_profile',
        'balance',
        'commission',
        'package_list',
        'using_softwares',
        'using_devices',
        'status',
        'updated_by',
        'current_month_user_disable_status',
        'activation_date'
    ];
}
