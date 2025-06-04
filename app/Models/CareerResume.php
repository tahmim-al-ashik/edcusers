<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerResume extends Model
{
    use HasFactory;

    protected $fillable = [
        'career_id',
        'full_name_bn',
        'full_name_en',
        'mobile_number',
        'whatsapp_number',
        'email',
        'nid_number',
        'date_of_birth',
        'nationality',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id',
        'address_details',
        'latitude',
        'longitude',
        'educations',
        'certifications',
        'experiences',
        'languages',
        'others_activity',
    ];
}
