<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentSettings extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['sp_ipmc','sa_ipmc'];
}
