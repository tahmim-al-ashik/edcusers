<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadbandDbPaymentToken extends Model
{
    use HasFactory;
    protected $connection = 'mikrotikDb';
    protected $table = 'bkash_tokens';
    public $timestamps = false;
    protected $fillable = [
        'mr_username',
        'access_token',
        'token_type',
        'expires_in',
        'refresh_token',
    ];
}
