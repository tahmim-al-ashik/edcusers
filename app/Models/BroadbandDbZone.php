<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadbandDbZone extends Model
{
    use HasFactory;
    protected $connection = 'mikrotikDb';
    protected $table = 'zone';

    public $timestamps = false;
}
