<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class network_support_center_package extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'zone_id',
        'package_id',
    ];
}
