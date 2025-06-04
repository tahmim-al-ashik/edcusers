<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateAgent extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'corporate_agents';
    public $timestamps = false;
    protected $fillable = [
        'uid',
        'client_id',
        'village_name',
        'union_name',
        'balance',
        'commission',
        'status',
        'activated_at'
    ];
    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'uid', 'uid');
    }
}
