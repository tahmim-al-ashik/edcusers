<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSupportRequest extends Model
{
    use HasFactory;

    protected $fillable = ['mobile_number','support_type'];
}
