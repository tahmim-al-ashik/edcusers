<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'image',
    ];
}
