<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbRadReply extends Model
{
    use HasFactory;

    // Database Connection
    protected $connection = 'radiusDb';
    protected $table = 'radreply';
    protected $fillable = [
        'username',
        'attribute',
        'op',
        'value',
    ];
    public $timestamps = false;
}
