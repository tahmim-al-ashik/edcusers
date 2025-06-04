<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransTjBoxSplitters extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'splitter_brand_name',
        'splitter_code',
        'splitter_type',
        'joining_core_color'
    ];
}
