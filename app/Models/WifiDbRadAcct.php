<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbRadAcct extends Model
{
    use HasFactory;

    // Database Connection
    protected $connection = 'radiusDb';
    protected $table = 'radacct';
    public $timestamps = false;
}
