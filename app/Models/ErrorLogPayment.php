<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLogPayment extends Model
{
    use HasFactory;
    protected $fillable = ['auth_id','uid','zone_id','trx_id','error_type'];
}
