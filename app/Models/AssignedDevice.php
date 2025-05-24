<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AssignedDevice
 *
 * @property $id
 * @property $user_id
 * @property $device_id
 * @property $type_id
 * @property $state_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class AssignedDevice extends Model
{
  use CommonModelTraits;

  public const STATE_ACTIVE = 1;
  public const STATE_INACTIVE = 2;
  public const STATE_DELETED = 3;

  static $rules = [];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['user_id', 'device_id', 'type_id', 'state_id', 'created_by', 'group_id'];

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
}
