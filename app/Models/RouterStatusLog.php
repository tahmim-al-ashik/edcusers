<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouterStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 'online', 'cpu_load', 'memory_usage',
        'total_bytes_in', 'total_bytes_out', 'active_connections', 'logged_at'
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
