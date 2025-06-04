<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmissionCustomersProblemCheck extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_name',
        'mobile_number',
        'email',
        'contact_name',
        'contact_number',
        'contact_email',
        'contact_designation',
        'organization',
        'latitude',
        'longitude',
        'package_id',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id',
        'address'
    ];
}
