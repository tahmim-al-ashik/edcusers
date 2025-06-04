<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchaseRequests extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'product_id',
        'company_name',
        'isp_name',
        'broadband_users',
        'wifi_users',
        'business_type',
        'internet_bandwidth',
        'youtube',
        'facebook',
        'bdix',
        'nttn',
        'number_of_pop',
        'ref_name',
        'ref_mobile_number',
    ];
}
