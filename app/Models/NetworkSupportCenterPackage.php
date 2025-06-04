<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkSupportCenterPackage extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'zone_id',
        'package_id',
        'type',
    ];
}
