<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransTjBox extends Model
{
    use HasFactory;
    protected $fillable = [
        'pop_id',
        'tj_box_code',
        'tj_box_type',
        'olt_port',
        'parent_tj_box_id',
        'customer_name',
        'customer_mobile',
        'latitude',
        'longitude',
        'address_direction',
        'added_by_uid',
        'updated_by_uid',
        'comments',
        'status',
    ];

    // Backbone Tj
    public function backboneCableDetails(){
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','backbone_tj');
    }

    public function backboneCoreJoinInfo(){
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','backbone_tj');
    }

    public function backboneWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','backbone_tj');
    }

    public function backboneImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','backbone_tj');
    }

    public function backboneLatLong(){
        return $this->hasMany(TransLatLong::class, 'trans_id', 'id')->where('module_type','backbone_tj');
    }

    // Backbone Tj
    public function backboneJoinCableDetails(){
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','backbone_join_tj');
    }

    public function backboneJoinCoreJoinInfo(){
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','backbone_join_tj');
    }

    public function backboneJoinWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','backbone_join_tj');
    }

    public function backboneJoinImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','backbone_join_tj');
    }

    public function backboneJoinLatLong(){
        return $this->hasMany(TransLatLong::class, 'trans_id', 'id')->where('module_type','backbone_join_tj');
    }

    // Joining Tj
    public function joiningCableDetails(){
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','joining_tj');
    }

    public function joiningCoreJoinInfo(){
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','joining_tj');
    }

    public function joiningWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','joining_tj');
    }

    public function joiningImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','joining_tj');
    }

    public function joiningLatLong(){
        return $this->hasMany(TransLatLong::class, 'trans_id', 'id')->where('module_type','joining_tj');
    }

    // Distribution Tj
    public function distributionCableDetails(){
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','distribution_tj');
    }

    public function distributionCoreJoinInfo(){
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','distribution_tj');
    }

    public function distributionSplitterInfo(){
        return $this->hasMany(TransTjBoxSplitters::class, 'trans_id', 'id')->where('module_type','distribution_tj');
    }

    public function distributionWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','distribution_tj');
    }

    public function distributionImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','distribution_tj');
    }

    public function distributionLatLong(){
        return $this->hasMany(TransLatLong::class, 'trans_id', 'id')->where('module_type','distribution_tj');
    }

    // Customer
    public function customerCableDetails(){
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','customer_tj');
    }

    public function customerCoreJoinInfo(){
        return $this->hasMany(TransCoreJoinInfo::class, 'trans_id', 'id')->where('module_type','customer_tj');
    }

    public function customerWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','customer_tj');
    }

    public function customerImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','customer_tj');
    }

    public function customerLatLong(){
        return $this->hasMany(TransLatLong::class, 'trans_id', 'id')->where('module_type','customer_tj');
    }
}
