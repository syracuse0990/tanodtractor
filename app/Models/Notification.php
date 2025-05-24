<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Notification
 *
 * @property $id
 * @property $user_id
 * @property $title
 * @property $message
 * @property $tractor_id
 * @property $is_read
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 * @property $booking_id
 * @property $device_id
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Notification extends Model
{

    public const IS_NOT_READ = 0;
    public const IS_READ = 1;

    public const TYPE_MAINTENANCE = 1;
    public const TYPE_ENTER_GEOFENCE = 2;
    public const TYPE_EXIT_GEOFENCE = 3;
    public const TYPE_INACTIVE = 4;
    public const TYPE_TICKET = 5;

    public const STATE_NOT_ALERTED = 0;
    public const STATE_ALERTED = 1;

    public const IS_NOT_CLOSED = 0;
    public const IS_CLOSED = 1;

    static $rules = [];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'title', 'message', 'tractor_id', 'is_read', 'type_id', 'created_by', 'booking_id', 'device_id', 'state_id', 'is_closed', 'geofence_id', 'exit_id', 'ticket_id'];

    public function getStateLabel()
    {
        $list = [
            self::IS_READ => 'Read',
            self::IS_NOT_READ => 'Unread',
        ];
        $label = [
            self::IS_READ => 'secondary',
            self::IS_NOT_READ => 'info',
        ];
        if (!empty($list[$this->is_read])) {
            return '<span class="badge bg-' . $label[$this->is_read] . '">' . $list[$this->is_read] . '</span>';
        }
        return '';
    }

    public function tractor()
    {
        return $this->hasOne(Tractor::class, 'id', 'tractor_id');
    }

    public function booking()
    {
        return $this->hasOne(TractorBooking::class, 'id', 'booking_id');
    }

    public function device()
    {
        return $this->hasOne(Device::class, 'id', 'device_id');
    }

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
