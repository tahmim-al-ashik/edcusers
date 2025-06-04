<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_test_mode',
        'uid',
        'zone_id',
        'vendor_name',
        'trx_id',
        'invoice_number',
        'amount',
        'process_status',
        'purpose',
        'package',
        'payment_id',
        'transaction_status',
    ];
}
