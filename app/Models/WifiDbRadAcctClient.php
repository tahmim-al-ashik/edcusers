<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbRadAcctClient extends Model
{
    use HasFactory;

    // Database Connection
    protected $connection = 'clientRadiusDb';
    protected $table = 'radacct';
    public $timestamps = false;
}
