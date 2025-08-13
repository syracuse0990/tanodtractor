<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaggingHistory extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function group(){
       return $this->belongsTo(TractorGroup::class, 'group_id')->selectRaw("id, name");
    }
    public function user(){
       return $this->belongsTo(User::class, 'user_id')->selectRaw("id, name");
    }
    public function device(){
       return $this->belongsTo(Device::class, 'device_id')->selectRaw("id, device_name, imei_no");
    }
    public function tractor(){
       return $this->belongsTo(Tractor::class, 'tractor_id')->selectRaw("id, imei, no_plate");
    }
}
