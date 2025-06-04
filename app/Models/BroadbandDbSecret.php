<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadbandDbSecret extends Model
{
    use HasFactory;
    protected $connection = 'mikrotikDb';
    protected $table = 'secret';

    protected $fillable = [
        'status',
        'profile',
        'client_id',
        'agent_id',
        'sub_agent_id',
        'username',
        'password',
        'service',
        'zone',
        'type',
        'created_at',
        'updated_at',
    ];
}
