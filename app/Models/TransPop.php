<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransPop extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'pop_code',
        'nttn_pop_code',
        'pop_sl_no',
        'pop_type',
        'pop_main_type',
        'parent_pop_id',
        'nttn_pop_id',
        'backup_nttn_pop_id',
        'scr_id',
        'db_signal',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_name',
        'address_direction',
        'latitude',
        'longitude',
        'added_by_uid',
        'updated_by_uid',
        'comments',
        'status',
    ];

    public function nttnDeviceInfos()
    {
        return $this->hasMany(TransPopDeviceInfo::class, 'trans_id', 'id')->where('module_type','nttn');
    }

    public function nttnOutputDeviceInfos()
    {
        return $this->hasMany(TransPopOutputDeviceInfo::class, 'trans_id', 'id')->where('module_type','nttn');
    }

    public function nttnCableDetails()
    {
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','nttn');
    }

    public function nttnCoreJoinInfo()
    {
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','nttn');
    }

    public function nttnWorkerInfos()
    {
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','nttn');
    }

    public function nttnImages()
    {
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','nttn');
    }

    // ------------ Branch ---------------
    public function branchDeviceInfos()
    {
        return $this->hasMany(TransPopDeviceInfo::class, 'trans_id', 'id')->where('module_type','branch');
    }

    public function branchOutputDeviceInfos()
    {
        return $this->hasMany(TransPopOutputDeviceInfo::class, 'trans_id', 'id')->where('module_type','branch');
    }

    public function branchCableDetails()
    {
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','branch');
    }

    public function branchCoreJoinInfo()
    {
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','branch');
    }

    public function branchWorkerInfos()
    {
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','branch');
    }

    public function branchImages()
    {
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','branch');
    }

    // ------------ Sub Branch ---------------
    public function subBranchDeviceInfos()
    {
        return $this->hasMany(TransPopDeviceInfo::class, 'trans_id', 'id')->where('module_type','sub_branch');
    }

    public function subBranchOutputDeviceInfos()
    {
        return $this->hasMany(TransPopOutputDeviceInfo::class, 'trans_id', 'id')->where('module_type','sub_branch');
    }

    public function subBranchCableDetails()
    {
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','sub_branch');
    }

    public function subBranchCoreJoinInfo()
    {
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','sub_branch');
    }

    public function subBranchWorkerInfos()
    {
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','sub_branch');
    }

    public function subBranchImages()
    {
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','sub_branch');
    }
}
