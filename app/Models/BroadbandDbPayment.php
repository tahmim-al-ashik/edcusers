<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadbandDbPayment extends Model
{
    use HasFactory;
    protected $connection = 'mikrotikDb';
    protected $table = 'payment';
    public $timestamps = false;
}
