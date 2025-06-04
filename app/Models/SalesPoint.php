<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'uid',
        'store_name',
        'monthly_commission_rate',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id',
        'address',
        'latitude',
        'longitude',
        'trade_licence',
        'status',
        'logo_source',
        'data_object',
    ];
}
