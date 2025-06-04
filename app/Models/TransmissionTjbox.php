<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmissionTjbox extends Model
{
    use HasFactory;
    protected $fillable = ['support_center_id','type_of_fiber'];
}
