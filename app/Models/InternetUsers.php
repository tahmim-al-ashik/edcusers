<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternetUsers extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'zone_id',
        'agent_id',
        'sub_agent_id',
        'added_by',
        'package_id',
        'package_type',
        'package_expire_date',
        'previous_conn_type',
        'provider_names',
        'latitude',
        'longitude',
        'password',
        'password_broadband',
        'user_type',
        'billing_address',
        'serial_number',
        'broadband_pop_id',
        'connection_media',
        'installation_charge',
        'connection_status'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'uid', 'uid');
    }
    public function latestCommunication()
    {
        return $this->hasOne(Communication::class, 'customer_uid', 'uid')->where('type','internet_user')->orderBy('id', 'desc');
    }
}
