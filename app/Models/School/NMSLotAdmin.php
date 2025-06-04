<?php

namespace App\Models\School;

use App\Models\InternetPackageCorporate;
use App\Models\PanelUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NMSLotAdmin extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'name',
        'mobile_number',
        'whatsapp_number',
        'email',
        'lot_username',
        'lot_isp_name',
        'proprietor_name',
        'proprietor_mobile',
        'proprietor_email',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch_address',
        'installation_cost',
        'package_id',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_id',
        'address_direction',
        'latitude',
        'longitude',
        'status',
        'created_by',
        'updated_by'
    ];
    public function panel_users(){
        return $this->belongsTo(PanelUser::class, 'uid', 'user_id');
    }
    public function package(){
        return $this->belongsTo(InternetPackageCorporate::class, 'package_id', 'id');
    }
}