<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternetPackageCorporate extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'package_name',
        'package_type',
        'client_id',
        'en_title',
        'bn_title',
        'price',
        'upload_speed',
        'download_speed',
        'expiration',
        'is_active',
        'weight'
    ];
}
