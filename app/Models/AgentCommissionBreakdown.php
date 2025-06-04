<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCommissionBreakdown extends Model
{
    use HasFactory;
    protected $fillable = [
        'agent_uid',
        'user_uid',
        'previous_wallet_amount',
        'new_commission_amount'
    ];
    // protected $fillable = [
    //     'agent_uid',
    //     'user_uid',
    //     'trx_id',
    //     'payment_amount',
    //     'commission_rate',
    //     'commission_amount'
    // ];
}
