<?php

namespace App\Models;

use App\Models\School\NMSLotAdmin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PanelUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'auth_id',
        'user_id',
        'status',
        'panel_access',
        'password',
        'text_password',
    ];
    protected $casts = [
        'status' => 'string',
    ];

    public function lot_admin(){
        return $this->belongsTo(NMSLotAdmin::class, 'user_id', 'uid');
    }
}
