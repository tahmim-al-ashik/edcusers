<?php

namespace App\Models\School;

use App\Models\InternetUsers;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolLatLong extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'lot_id',
        'manager_id',
        'institution_type',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'latitude',
        'longitude',
        'status'
    ];

    public function user_profiles()
    {
        return $this->hasOne(UserProfile::class, 'uid', 'uid');
    }

    // In SchoolLatLong model
    public function school_profile() {
        return $this->hasOne(SchoolProfile::class, 'uid', 'uid');
    }

    public function internet_users(){
        return $this->belongsTo(InternetUsers::class, 'uid', 'uid');
    }
}


