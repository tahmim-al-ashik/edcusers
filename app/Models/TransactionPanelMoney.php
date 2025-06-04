<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPanelMoney extends Model
{
    use HasFactory;
    protected $fillable = [
        'amount',
        'sender_uid',
        'receiver_uid',
        'trx_id',
        'invoice_number',
        'payment_id',
        'type',
        'status',
        'remarks'
    ];
}
