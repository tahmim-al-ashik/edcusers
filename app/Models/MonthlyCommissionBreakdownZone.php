<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyCommissionBreakdownZone extends Model
{
    use HasFactory;
    protected $fillable = [
        'zone_id',
        'date_month',
        'commission_rate_type',
        'commission_rate_wifi',
        'commission_rate_broadband',
    ];
}
