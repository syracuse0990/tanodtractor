<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    public const ACC_OFF = 1001;
    public const ACC_ON = 1002;
    public const GEOZONE_IN = 1006;
    public const GEOZONE_OUT = 1007;

    protected $hidden = [
        'details',
    ];

    protected $perPage = 20;

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function deviceDetail()
    {
        return $this->hasOne(Device::class, 'imei_no', 'imei');
    }

    public static function alertOptions()
    {
        return [
            self::ACC_ON => 'ACC ON',
            self::ACC_OFF => 'ACC OFF',
            self::GEOZONE_IN => 'Geofence In',
            self::GEOZONE_OUT => 'Geofence Out',
        ];
    }
}
