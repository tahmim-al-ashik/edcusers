<?php

namespace App\Models\School;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NMSJson extends Model
{
    use HasFactory;
    protected $fillable = [
        'lot_id',
        'lot_uid',
        'institution_type',
        'file',
        'created_by',
        'updated_by'
    ];
}
