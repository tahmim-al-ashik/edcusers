<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransLatLong extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'latitude',
        'longitude',
        'status',
    ];
}
