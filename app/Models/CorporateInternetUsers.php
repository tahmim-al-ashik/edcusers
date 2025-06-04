<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateInternetUsers extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'service_type',
        'company_name',
        'company_type',
        'requirements',
        'status'
    ];
}
