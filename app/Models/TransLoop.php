<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransLoop extends Model
{
    use HasFactory;
    protected $fillable = [
        'pop_id',
        'tj_box_id',
        'olt_port',
        'loop_code',
        'loop_type',
        'latitude',
        'longitude',
        'address_direction',
        'added_by_uid',
        'updated_by_uid',
        'comments',
        'status',
    ];
    // Reserved Loop
    public function reservedCableDetails(){
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','reserved_loop');
    }

    public function reservedWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','reserved_loop');
    }

    public function reservedImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','reserved_loop');
    }

    public function reservedLatLong(){
        return $this->hasMany(TransLatLong::class, 'trans_id', 'id')->where('module_type','reserved_loop');
    }

    // Distribution Loop
    public function distributionCableDetails(){
        return $this->hasMany(TransCableDetail::class, 'trans_id', 'id')->where('module_type','distribution_loop');
    }

    public function distributionWorkerInfos(){
        return $this->hasMany(TransWorkerInfo::class, 'trans_id', 'id')->where('module_type','distribution_loop');
    }

    public function distributionImages(){
        return $this->hasMany(TransImage::class, 'trans_id', 'id')->where('module_type','distribution_loop');
    }

    public function distributionLatLong(){
        return $this->hasMany(TransLatLong::class, 'trans_id', 'id')->where('module_type','distribution_loop');
    }
}
