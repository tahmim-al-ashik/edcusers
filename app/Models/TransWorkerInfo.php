<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransWorkerInfo extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'added_by_name',
        'mobile_number',
        'work_type',
    ];
}
