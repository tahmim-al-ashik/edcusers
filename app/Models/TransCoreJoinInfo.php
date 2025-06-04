<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransCoreJoinInfo extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'in_fiber_id',
        'out_fiber_id',
        'joining_core_color',
        'db_signal'
    ];
}
