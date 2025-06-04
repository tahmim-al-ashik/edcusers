<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'user_type',
        'date_month',
        'total_payment',
        'total_commission',
        'commission_rate',
        'commission_amount'
    ];
}
