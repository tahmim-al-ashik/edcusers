<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbBkashInfo extends Model
{
    use HasFactory;
    protected $connection = 'radiusDb';
    protected $table = 'bkash_info';
    public $timestamps = false;
}
