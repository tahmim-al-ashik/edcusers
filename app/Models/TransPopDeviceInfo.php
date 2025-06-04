<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransPopDeviceInfo extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'sfp_brand_name',
        'sfp_type',
        'sfp_capacity',
        'input_device_port_type',
        'port_capacity',
        'incoming_fiber_connected_port_number',
        'mk_brand_name',
        'mk_capacity',
        'mk_port_number',
        'mk_serial_no',
        'mk_device_id',
        'mk_power_consumption',
        'mk_mac_address',
        'rak_brand_name',
        'rak_capacity'
    ];
}
