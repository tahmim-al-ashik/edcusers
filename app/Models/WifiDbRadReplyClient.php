<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbRadReplyClient extends Model
{
    use HasFactory;

    // Database Connection
    protected $connection = 'clientRadiusDb';
    protected $table = 'radreply';
    protected $fillable = [
        'username',
        'attribute',
        'op',
        'value',
    ];
    public $timestamps = false;
}
