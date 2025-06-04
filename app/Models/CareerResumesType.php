<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerResumesType extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'career_name_bn',
        'career_name_en',
        'is_active'
    ];
}
