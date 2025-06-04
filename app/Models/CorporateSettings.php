<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateSettings extends Model
{
    use HasFactory;
    protected $fillable = ['settings_name','settings_value'];
}
