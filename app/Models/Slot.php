<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Slot
 *
 * @property $id
 * @property $tractor_id
 * @property $date
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Slot extends Model
{

  use CommonModelTraits;

  public const STATE_INACTIVE = 0;
  public const STATE_ACTIVE = 1;
  public const STATE_DELETED = 2;
  public const STATE_BOOKED = 3;

  static $rules = [
    'tractor_id' => 'required',
    'date' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['tractor_id', 'date', 'state_id', 'type_id', 'created_by'];


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
      self::STATE_DELETED => 'Deleted',
      self::STATE_BOOKED => 'BOOKED'
    ];
  }

  public function getStateLabel()
  {
    $list = [
      self::STATE_INACTIVE => 'Inactive',
      self::STATE_ACTIVE => 'Active',
      self::STATE_DELETED => 'Deleted',
      self::STATE_BOOKED => 'BOOKED'

    ];
    $label = [
      self::STATE_INACTIVE => 'secondary',
      self::STATE_ACTIVE => 'success',
      self::STATE_DELETED => 'danger',
      self::STATE_BOOKED => 'danger'
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

  public function tractor()
  {
    return $this->hasOne('App\Models\Tractor', 'id', 'tractor_id');
  }
}
