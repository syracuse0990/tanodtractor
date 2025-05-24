<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Device
 *
 * @property $id
 * @property $imei_no
 * @property $device_modal
 * @property $device_name
 * @property $sales_time
 * @property $subscription_expiration
 * @property $expiration_date
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Device extends Model
{
  use CommonModelTraits;

  public const STATE_INACTIVE = 0;
  public const STATE_ACTIVE = 1;
  public const STATE_DELETED = 2;

  public const ALL_DEVICES = 1;
  public const ONLINE_DEVICES = 2;
  public const OFFLINE_DEVICES = 3;
  public const INACTIVE_DEVICES = 4;

  public const MOVING_DEVICES = 1;
  public const IDLE_DEVICES = 2;

  static $rules = [
    'imei_no' => 'required',
    'device_modal' => 'required',
    'device_name' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['imei_no', 'device_modal', 'device_name', 'sales_time', 'subscription_expiration', 'expiration_date', 'state_id', 'type_id', 'created_by', 'mc_type', 'mc_type_use_scope', 'sim', 'activation_time', 'remark', 'is_check', 'sim_iccid', 'sim_registration_code', 'mobile_data_load'];

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

  public static function stateOptions()
  {
    return [
      self::STATE_INACTIVE => 'Inactive',
      self::STATE_ACTIVE => 'Active',
      self::STATE_DELETED => 'Deleted'
    ];
  }

  public function getStateLabel()
  {
    $list = [
      self::STATE_INACTIVE => 'Inactive',
      self::STATE_ACTIVE => 'Active',
      self::STATE_DELETED => 'Deleted'
    ];
    $label = [
      self::STATE_INACTIVE => 'secondary',
      self::STATE_ACTIVE => 'success',
      self::STATE_DELETED => 'danger'
    ];
    if (!empty($list[$this->state_id])) {
      return '<span class="badge bg-' . $label[$this->state_id] . '">' . $list[$this->state_id] . '</span>';
    }
    return '';
  }

  public function getState()
  {
    $list = self::stateOptions();
    return !empty($list[$this->state_id]) ? $list[$this->state_id] : 'Not Defined';
  }

  public function createdBy()
  {
    return $this->hasOne('App\Models\User', 'id', 'created_by');
  }

  public function bookings()
  {
    return $this->hasMany('App\Models\TractorBooking', 'device_id', 'id');
  }
}
