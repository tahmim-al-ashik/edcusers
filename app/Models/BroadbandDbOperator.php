<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadbandDbOperator extends Model
{
    use HasFactory;
    protected $connection = 'mikrotikDb';
    protected $table = 'operator';
    public $timestamps = false;
}
