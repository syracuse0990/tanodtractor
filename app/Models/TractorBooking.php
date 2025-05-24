<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TractorBooking
 *
 * @property $id
 * @property $tractor_id
 * @property $device_id
 * @property $slot_id
 * @property $purpose
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TractorBooking extends Model
{
  use CommonModelTraits;

  public const STATE_INACTIVE = 0;
  public const STATE_ACTIVE = 1;
  public const STATE_DELETED = 2;
  public const STATE_ACCEPTED = 3;
  public const STATE_REJECTED = 4;

  public $state_url  = "tractor-bookings.change-status";
  static $rules = [
    'state_id' => 'required',
    'type_id' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['tractor_id', 'device_id', 'slot_id', 'purpose', 'state_id', 'type_id', 'created_by', 'date', 'reason', 'kilometer'];

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
      self::STATE_ACTIVE => 'Active',
      self::STATE_ACCEPTED => 'Accepted',
      self::STATE_REJECTED => 'Rejected'
    ];
  }

  public function getStateLabel()
  {
    $list = [
      self::STATE_ACTIVE => 'Active',
      self::STATE_ACCEPTED => 'Accepted',
      self::STATE_REJECTED => 'Rejected'
    ];
    $label = [
      self::STATE_ACTIVE => 'info',
      self::STATE_ACCEPTED => 'success',
      self::STATE_REJECTED => 'danger'
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

  public function getColor($key)
  {
    $color = [
      self::STATE_ACTIVE => 'success',
      self::STATE_ACCEPTED => 'success',
      self::STATE_REJECTED => 'danger'
    ];
    return $color[$key];
  }

  public function createdBy()
  {
    return $this->hasOne('App\Models\User', 'id', 'created_by');
  }
  public function slot()
  {
    return $this->hasOne('App\Models\Slot', 'id', 'slot_id');
  }
  public function tractor()
  {
    return $this->hasOne('App\Models\Tractor', 'id', 'tractor_id');
  }

  public function device()
  {
    return $this->hasOne('App\Models\Device', 'id', 'device_id');
  }

  public function bookingJsonResponse()
  {
    $json['id'] = $this->id;
    $json['tractor_id'] = json_decode($this->tractor_id, true);
    $json['device_id'] = json_decode($this->device_id, true);
    $json['purpose'] = $this->purpose;
    $json['date'] = $this->date;
    $json['state_id'] = $this->state_id;
    $json['reason'] = $this->reason;
    $json['created_by'] = $this->createdBy;
    $json['tractor'] = $this->tractor;
    $json['device'] = $this->device;

    return $json;
  }
}
