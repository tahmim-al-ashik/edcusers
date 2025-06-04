<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransCompany extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_name',
        'company_type',
        'contact_person_name_pri',
        'contact_person_number_pri',
        'contact_person_email_pri',
        'contact_person_designation_pri',
        'contact_person_name_sec',
        'contact_person_number_sec',
        'contact_person_email_sec',
        'contact_person_designation_sec',
        'vendor_name',
        'added_by_uid',
        'updated_by_uid',
        'status'
    ];
}
