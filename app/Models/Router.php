<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'ip_address', 'username', 'password', 'port', 
        'description', 'location', 'is_active'
    ];

    public function statusLogs()
    {
        return $this->hasMany(RouterStatusLog::class);
    }

    public function connectedDevices()
    {
        return $this->hasMany(ConnectedDevice::class);
    }

    public function latestStatus()
    {
        return $this->hasOne(RouterStatusLog::class)->latestOfMany();
    }
}