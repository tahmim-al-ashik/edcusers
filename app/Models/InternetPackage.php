<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternetPackage extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'mikrotik_radius_group_name',
        'en_title',
        'bn_title',
        'type',
        'zone_id',
        'price',
        'price_bn',
        'expiration',
        'expiration_bn',
        'sales_point_commission',
        'sales_agent_commission',
        'commission_type',
        'user_points',
        'is_active',
        'skip_from_display',
        'weight',
        'bg_image_source'
    ];
}
