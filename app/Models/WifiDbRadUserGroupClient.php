<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbRadUserGroupClient extends Model
{
    use HasFactory;

    // Database Connection
    protected $connection = 'clientRadiusDb';
    protected $table = 'radusergroup';
    public $timestamps = false;
    protected $fillable = ['username','groupname','priority'];
    protected $primaryKey = null;
    public $incrementing = false;
}
