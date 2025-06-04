<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateHistory extends Model
{
    use HasFactory;
    //public $timestamps = false;

    protected $fillable = ['affiliator_uid', 'product_type', 'product_id', 'commission_amount'];
}
