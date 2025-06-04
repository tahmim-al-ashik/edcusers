<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransPopOutputDeviceInfo extends Model
{
    use HasFactory;
    protected $fillable = [
        'trans_id',
        'module_type',
        'output_device_type',
        'output_device_port_type',
        'output_device_port_number',
        'output_device_brand_name',
        'output_device_connection_capacity',
        'output_device_serial_no',
        'output_device_id',
        'output_device_power_consumption',
    ];
}
