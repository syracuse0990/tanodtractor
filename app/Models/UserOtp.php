<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;

    public const STATE_ACTIVE = 1;
    public const STATE_INACTIVE = 2;

    protected $fillable = [
        'user_id',
        'phone_country',
        'phone',
        'otp',
        'sent_at',
        'created_at',
        'updated_at',
    ];
}
