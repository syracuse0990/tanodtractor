<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Ticket
 *
 * @property $id
 * @property $title
 * @property $description
 * @property $conclusion
 * @property $type_id
 * @property $state_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Ticket extends Model
{
  use CommonModelTraits;

  public const STATE_ACTIVE = 1;
  public const STATE_INPROGRESS = 2;
  public const STATE_COMPLETED = 3;
  public const STATE_REJECTED = 4;
  public const STATE_DELETED = 5;

  static $rules = [
    'type_id' => 'required',
    'state_id' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['title', 'description', 'conclusion', 'type_id', 'state_id', 'created_by'];

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
      self::STATE_INPROGRESS => 'In Progress',
      self::STATE_COMPLETED => 'Completed',
      self::STATE_REJECTED => 'Rejected',
      self::STATE_DELETED => 'Deleted',
    ];
  }

  public function getStateLabel()
  {
    $list = [
      self::STATE_ACTIVE => 'Active',
      self::STATE_INPROGRESS => 'In Progress',
      self::STATE_COMPLETED => 'Completed',
      self::STATE_REJECTED => 'Rejected',
      self::STATE_DELETED => 'Deleted',
    ];
    $label = [
      self::STATE_ACTIVE => 'info',
      self::STATE_INPROGRESS => 'secondary',
      self::STATE_COMPLETED => 'success',
      self::STATE_REJECTED => 'danger',
      self::STATE_DELETED => 'danger',
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
      self::STATE_ACTIVE => 'info',
      self::STATE_INPROGRESS => 'secondary',
      self::STATE_COMPLETED => 'success',
      self::STATE_REJECTED => 'danger',
      self::STATE_DELETED => 'danger',
    ];
    return $color[$key];
  }

  public function createdBy()
  {
    return $this->hasOne('App\Models\User', 'id', 'created_by');
  }
}
