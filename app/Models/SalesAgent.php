<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'zone_id',
        'nid',
        'birth_date',
        'status',
        'photo_source',
        'monthly_commission_rate',
        'data_object',
    ];
}
