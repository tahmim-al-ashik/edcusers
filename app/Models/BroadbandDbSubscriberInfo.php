<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadbandDbSubscriberInfo extends Model
{
    use HasFactory;
    protected $connection = 'mikrotikDb';
    protected $table = 'subscriber_info';
    protected $fillable = [
        'serial',
        'popId',
        'date',
        'zone_name',
        'm_name',
        'm_mobile',
        'numAsId',
        'packageId',
        'customerName',
        'gender',
        'instcost',
        'home',
        'village',
        'post_office',
        'police_station',
        'district',
        'division',
        'billingAddress',
        'nid',
        'numOne',
        'email',
        'UserType',
        'connectionMedia',
        'acativation_date'
    ];
    public $timestamps = false;
}
