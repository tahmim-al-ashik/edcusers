<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransCustomer extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_name',
        'customer_mobile',
        'customer_email',
        'customer_organization',
        'pop_id',
        'tj_box_id',
        'olt_port',
        'contact_person_name',
        'contact_person_number_pri',
        'contact_person_number_sec',
        'contact_person_designation',
        'contact_person_whatsapp',
        'contact_person_email',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village',
        'latitude',
        'longitude',
        'address_direction',
        'added_by_uid',
        'updated_by_uid',
        'comments',
        'status',
    ];
    public function customerWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','customer');
    }

    public function customerImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','customer');
    }
}
