<?php

namespace App\Models;
use App\Models\School\SchoolManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable= [
        'uid',
        'full_name',
        'wallet_amount',
        'mobile_number',
        'whatsapp_number',
        'email',
        'profession',
        'nid',
        'gender',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id',
        'house_no',
        'ward_no',
        'road_no',
        'block_no',
        'address',
        'latitude',
        'longitude',
        'address_direction',
        'device_info'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function InternetUsers(){
        return $this->hasOne(InternetUsers::class, 'uid', 'uid')->where('connection_status','pending');
    }

    public function school_managers(){
        return $this->hasOne(SchoolManager::class, 'uid', 'uid');
    }
}
