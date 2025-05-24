<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class IssueType
 *
 * @property $id
 * @property $title
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class IssueType extends Model
{

  use CommonModelTraits;

  public const STATE_INACTIVE = 0;
  public const STATE_ACTIVE = 1;
  public const STATE_DELETED = 2;

  public $state_url = 'issue-types.change-type-status';

  static $rules = [
    'title' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['title', 'state_id', 'type_id', 'created_by'];

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
      // self::STATE_DELETED => 'Deleted'
    ];
  }

  public static function stateOptionsChanged()
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

  public function getColor($key)
  {
    $color = [
      self::STATE_INACTIVE => 'secondary',
      self::STATE_ACTIVE => 'success',
      self::STATE_DELETED => 'danger'
    ];
    return $color[$key];
  }

  public function createdBy()
  {
    return $this->hasOne('App\Models\User', 'id', 'created_by');
  }

  public function createJsonResponse()
  {

    $json['id'] = $this->id;
    $json['title'] = $this->title;
    $json['state_id'] = $this->state_id;
    $json['type_id'] = $this->type_id;
    $json['created_at'] = $this->created_at;
    $json['updated_at'] = $this->updated_at;
    $json['created_by'] = $this->createdBy;

    return $json;
  }
}
