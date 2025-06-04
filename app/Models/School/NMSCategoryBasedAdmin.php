<?php

namespace App\Models\School;

use App\Models\PanelUser;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NMSCategoryBasedAdmin extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'lot_id',
        'category_type',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id',
        'latitude',
        'longitude',
        'address_direction',
        'status',
        'created_by',
        'updated_by'
    ];
    public function users(){
        return $this->belongsTo(User::class, 'uid', 'id');
    }
    public function panel_users(){
        return $this->belongsTo(PanelUser::class, 'uid', 'user_id');
    }
    public function user_profiles(){
        return $this->belongsTo(UserProfile::class, 'uid', 'uid');
    }
}
