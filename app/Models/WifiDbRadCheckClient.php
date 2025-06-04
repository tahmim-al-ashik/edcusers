<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbRadCheckClient extends Model
{
    use HasFactory;

    // Database Connection
    protected $connection = 'clientRadiusDb';
    protected $table = 'radcheck';
    protected $fillable = [
        'username', 'attribute', 'op', 'value', 'branch', 'updatetime',
    ];
    public $timestamps = false;
}
