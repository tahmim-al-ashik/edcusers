<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbBkashInfoClient extends Model
{
    use HasFactory;
    protected $connection = 'clientRadiusDb';
    protected $table = 'bkash_info';
    public $timestamps = false;
}
