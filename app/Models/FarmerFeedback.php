<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FarmerFeedback
 *
 * @property $id
 * @property $name
 * @property $email
 * @property $issue_type_id
 * @property $description
 * @property $conclusion
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class FarmerFeedback extends Model
{
  use CommonModelTraits;

  protected $table = 'farmer_feedbacks';

  public const STATE_ACTIVE = 1;
  public const STATE_COMPLETED = 2;
  public const STATE_CLOSED = 3;

  public $state_url = 'farmer-feedbacks.change-status';


  static $rules = [
    'conclusion' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['name', 'email', 'issue_type_id', 'description', 'conclusion', 'state_id', 'type_id', 'created_by', 'tech_details', 'tractor_id'];

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
      self::STATE_COMPLETED => 'Completed',
      self::STATE_CLOSED => 'Closed'
    ];
  }

  public function getStateLabel()
  {
    $list = [
      self::STATE_ACTIVE => 'Active',
      self::STATE_COMPLETED => 'Completed',
      self::STATE_CLOSED => 'Closed'
    ];
    $label = [
      self::STATE_ACTIVE => 'info',
      self::STATE_COMPLETED => 'success',
      self::STATE_CLOSED => 'danger'
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
      self::STATE_COMPLETED => 'success',
      self::STATE_CLOSED => 'danger'
    ];
    return $color[$key];
  }
  public function createdBy()
  {
    return $this->hasOne('App\Models\User', 'id', 'created_by');
  }
  public function issueType()
  {
    return $this->hasOne('App\Models\IssueType', 'id', 'issue_type_id');
  }

  public function images()
  {
    return $this->hasMany('App\Models\Image', 'model_id', 'id')->where('model_type', FarmerFeedback::class);
  }

  public function tractor()
  {
    return $this->hasOne('App\Models\Tractor', 'id', 'tractor_id');
  }

  public function createJsonResponse()
  {

    $json['id'] = $this->id;
    $json['tractor_ids'] = $this->tractor_ids;
    $json['name'] = $this->name;
    $json['email'] = $this->email;
    $json['issue_type_id'] = $this->issue_type_id;
    $json['description'] = $this->description;
    $json['conclusion'] = $this->conclusion;
    $json['state_id'] = $this->state_id;
    $json['type_id'] = $this->type_id;
    $json['created_at'] = $this->created_at;
    $json['updated_at'] = $this->updated_at;
    $json['created_by'] = $this->createdBy;
    if ($this->state_id == FarmerFeedback::STATE_ACTIVE) {
      $json['pending_states'] = [FarmerFeedback::STATE_CLOSED, FarmerFeedback::STATE_COMPLETED];
    } elseif ($this->state_id == FarmerFeedback::STATE_CLOSED) {
      $json['pending_states'] = [FarmerFeedback::STATE_ACTIVE, FarmerFeedback::STATE_COMPLETED];
    } elseif ($this->state_id == FarmerFeedback::STATE_COMPLETED) {
      $json['pending_states'] = [FarmerFeedback::STATE_ACTIVE, FarmerFeedback::STATE_CLOSED];
    }
    $json['issue_type'] = $this->issueType;
    return $json;
  }
}
