<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbPayment extends Model
{
    use HasFactory;
    protected $connection = 'radiusDb';
    protected $table = 'payment';
    public $timestamps = false;
}
