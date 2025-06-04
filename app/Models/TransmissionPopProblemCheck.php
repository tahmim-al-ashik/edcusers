<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmissionPopProblemCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'tc_id',
        'category',
        'nttn_pop_code',
        'pop_id',
        'infra_type',
        'indoor_outdoor',
        'pop_type',
        'latitude',
        'longitude',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id'
    ];
}
