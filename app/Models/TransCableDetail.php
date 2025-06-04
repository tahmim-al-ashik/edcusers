<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransCableDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'cable_type',
        'fiber_code',
        'fiber_core',
        'core_capacity',
        'start_fiber_meter',
        'end_fiber_meter',
        'fiber_length',
        'joining_core_color',
        'db_signal',
        'connected_port_number'
    ];
}
