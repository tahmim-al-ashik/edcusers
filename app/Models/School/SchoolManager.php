<?php

namespace App\Models\School;

use App\Models\PanelUser;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolManager extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'lot_id',
        'manager_type',
        'profile_image',
        'assigned_division_id',
        'assigned_district_id',
        'assigned_upazila_id',
        'assigned_union_id',
        'mikrotik_ip',
        'mikrotik_username',
        'mikrotik_password',
        'status',
        'created_by',
        'updated_by'
    ];
    public function user(){
        return $this->belongsTo(User::class, 'uid', 'id');
    }
    public function user_profiles(){
        return $this->belongsTo(UserProfile::class, 'uid', 'uid');
    }
    public function panel_users(){
        return $this->belongsTo(PanelUser::class, 'uid', 'user_id');
    }
    public function panel_lot_admin(){
        return $this->belongsTo(PanelUser::class, 'lot_id', 'id');
    }
}
