<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateStall extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'corporate_stalls';
    protected $fillable = [
        'name',
        'username',
        'mobile',
        'whatsapp',
        'email',
        'password',
        'nid',
        'division',
        'district',
        'upazilla_thana',
        'city_corporation_union',
        'ward_village',
        'details_address',
        'latitude',
         'longitude',
         'status',
         'client_id',
         'created_at'
        ];
    public $timestamps=false;
}
