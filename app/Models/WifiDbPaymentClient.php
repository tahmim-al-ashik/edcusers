<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbPaymentClient extends Model
{
    use HasFactory;
    protected $connection = 'clientRadiusDb';
    protected $table = 'payment';
    public $timestamps = false;
}
