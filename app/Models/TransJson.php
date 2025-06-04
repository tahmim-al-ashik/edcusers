<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransJson extends Model
{
    use HasFactory;
    protected $fillable = [
        'zone_id',
        'trans_id',
        'module_type',
        'file',
        'created_by',
        'updated_by'
    ];
}