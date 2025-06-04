<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateClientsSettings extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_uid',
        'logo',
        'signature',
        'billing_cycle',
        'manual_disable_day',
        'payment_method',
        'bkash_username',
        'bkash_password',
        'bkash_app_key',
        'bkash_app_secret_key',
        'created_at',
        'updated_at',
    ];
}
