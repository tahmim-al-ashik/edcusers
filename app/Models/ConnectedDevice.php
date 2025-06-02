<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectedDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 'mac_address', 'ip_address', 
        'hostname', 'interface', 'bytes_in', 
        'bytes_out', 'active', 'last_seen'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}