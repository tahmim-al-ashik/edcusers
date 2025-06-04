<?php

namespace App\Models\School;

use App\Models\InternetPackageCorporate;
use App\Models\InternetUsers;
use App\Models\PanelUser;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'manager_id',
        'lot_id',
        'institution_type',
        'school_name',
        'connection_code',
        'edc_book_sl_no',
        'package_id',
        'electricity',
        'area_code',
        'dis_code',
        'emis_code',
        'head_teacher_name',
        'head_teacher_mobile',
        'head_teacher_ast_name',
        'head_teacher_ast_mobile',
        'fiber_id',
        'fiber_core',
        'db_signal',
        'start_meter',
        'end_meter',
        'fiber_length',
        'onu_mac',
        'router_username',
        'router_password',
        'router_mac',
        'router_remote_magt_port',
        'gateway',
        'subnet_mask',
        'dnsv4_primary',
        'dnsv4_secondary',
        'ipv4_ip',
        'ipv6_ip',
        'snmp_com',
        'slaac_enabled',
        'icmp_enabled',
        'router_model',
        'router_serial',
        'tj_box_quantity',
        'tj_box_remarks',
        'fiber_patch_cord_quantity',
        'fiber_patch_cord_remarks',
        'installation_cost',
        'status',
        'comments',
        'others',
        'updated_by'
    ];
    public function user(){
        return $this->belongsTo(User::class, 'uid', 'id');
    }
    public function user_profiles(){
        return $this->belongsTo(UserProfile::class, 'uid', 'uid');
    }

    public function manager(){
        return $this->belongsTo(UserProfile::class, 'manager_id', 'uid');
    }
    public function internet_users(){
        return $this->belongsTo(InternetUsers::class, 'uid', 'uid');
    }
    public function package(){
        return $this->belongsTo(InternetPackageCorporate::class, 'package_id', 'id');
    }

    // In SchoolProfile model
    public function school_lat_long() {
        return $this->hasOne(SchoolLatLong::class, 'uid', 'uid');
    }

    public function panel_lot_admin(){
        return $this->belongsTo(PanelUser::class, 'lot_id', 'id');
    }
}
