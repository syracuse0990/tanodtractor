<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AutoReport
 *
 * @property $id
 * @property $report_name
 * @property $device_ids
 * @property $frequency
 * @property $email_addresses
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class AutoReport extends Model
{
    use CommonModelTraits;

    public const FREQUENCY_MONTHLY = 1;
    public const FREQUENCY_WEEKLY = 2;
    public const FREQUENCY_DAILY = 3;

    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESSDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const SUNDAY = 7;



    static $rules = [];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['report_name', 'device_ids', 'frequency', 'email_addresses', 'from_day', 'from_time', 'to_day', 'to_time', 'execution_day', 'execution_time', 'created_by'];

    /**
     * Summary of boot
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        self::bootMyModelTrait();
        self::bootLogsActivity();
    }

    public static function dayOptions()
    {
        return [
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESSDAY => 'Wednessday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
            self::SUNDAY => 'Sunday',
        ];
    }

    public function getFrequency()
    {
        if ($this->frequency == self::FREQUENCY_MONTHLY) {
            $frequency = 'Monthly';
        } elseif ($this->frequency == self::FREQUENCY_WEEKLY) {
            $frequency = 'Weekly';
        } elseif ($this->frequency == self::FREQUENCY_DAILY) {
            $frequency = 'Daily';
        } else {
            $frequency = 'N/A';
        }
        return $frequency;
    }

    public function getFromDay()
    {
        if ($this->frequency == self::FREQUENCY_MONTHLY) {
            $day = $this->from_day;
        } elseif ($this->frequency == self::FREQUENCY_WEEKLY) {
            $list = self::dayOptions();
            $day = isset($list[$this->from_day]) ? $list[$this->from_day] : 'N/A';
        } else {
            $day = 'N/A';
        }
        return $day;
    }

    public function getToDay()
    {
        if ($this->frequency == self::FREQUENCY_MONTHLY) {
            $day = $this->to_day;
        } elseif ($this->frequency == self::FREQUENCY_WEEKLY) {
            $list = self::dayOptions();
            $day = isset($list[$this->to_day]) ? $list[$this->to_day] : 'N/A';
        } else {
            $day = 'N/A';
        }
        return $day;
    }

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
}
