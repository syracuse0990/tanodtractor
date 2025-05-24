<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Maintenance
 *
 * @property $id
 * @property $tractor_ids
 * @property $maintenance_date
 * @property $tech_name
 * @property $tech_email
 * @property $tech_number
 * @property $farmer_name
 * @property $farmer_email
 * @property $farmer_number
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Maintenance extends Model
{

  use CommonModelTraits;

  public const STATE_DOCUMENTATION = 1;
  public const STATE_FILLED = 2;
  public const STATE_INPROGRESS = 3;
  public const STATE_COMPLETED = 4;
  public const STATE_CANCELLED = 5;

  public  $state_url = 'maintenances.state';

  static $rules = [
    'tractor_ids' => 'required',
    'maintenance_date' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['tractor_ids', 'maintenance_date', 'tech_name', 'tech_email', 'tech_number', 'conclusion', 'farmer_name', 'farmer_email', 'farmer_number', 'state_id', 'type_id', 'created_by', 'tech_iso_code', 'tech_phone_code', 'farmer_iso_code', 'farmer_phone_code'];

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
      self::STATE_DOCUMENTATION => 'Documentation',
      self::STATE_FILLED => 'Filled',
      self::STATE_INPROGRESS => 'In Progress',
      self::STATE_COMPLETED => 'Completed',
      self::STATE_CANCELLED => 'Cancelled'
    ];
  }

  public function getStateLabel()
  {
    $list = [
      self::STATE_DOCUMENTATION => 'Documentation',
      self::STATE_FILLED => 'Filled',
      self::STATE_INPROGRESS => 'In Progress',
      self::STATE_COMPLETED => 'Completed',
      self::STATE_CANCELLED => 'Cancelled'
    ];
    $label = [
      self::STATE_DOCUMENTATION => 'secondary',
      self::STATE_FILLED => 'primary',
      self::STATE_INPROGRESS => 'warning',
      self::STATE_COMPLETED => 'success',
      self::STATE_CANCELLED => 'danger'
    ];
    if (!empty($list[$this->state_id])) {
      return '<span class="badge bg-' . $label[$this->state_id] . '">' . $list[$this->state_id] . '</span>';
    }
    return '';
  }

  public function getColor($key)
  {
    $color = [
      self::STATE_DOCUMENTATION => 'secondary',
      self::STATE_FILLED => 'primary',
      self::STATE_INPROGRESS => 'warning',
      self::STATE_COMPLETED => 'success',
      self::STATE_CANCELLED => 'danger'
    ];
    return $color[$key];
  }

  public function getState()
  {
    $list = self::stateOptions();
    return !empty($list[$this->state_id]) ? $list[$this->state_id] : 'Not Defined';
  }

  public function getStateName($id)
  {
    $list = self::stateOptions();
    return !empty($list[$id]) ? $list[$id] : 'Not Defined';
  }

  public function createdBy()
  {
    return $this->hasOne('App\Models\User', 'id', 'created_by');
  }
  public function tractor()
  {
    return $this->hasOne('App\Models\Tractor', 'id', 'tractor_ids');
  }

  public function createJsonResponse()
  {
    $json['id'] = $this->id;
    $json['tractor_ids'] = $this->tractor_ids;
    $json['maintenance_date'] = $this->maintenance_date;
    $json['tech_name'] = $this->tech_name;
    $json['tech_email'] = $this->tech_email;
    $json['tech_number'] = $this->tech_number;
    $json['conclusion'] = $this->conclusion;
    $json['state_id'] = $this->state_id;
    $json['type_id'] = $this->type_id;
    $json['created_at'] = $this->created_at;
    $json['updated_at'] = $this->updated_at;
    $json['created_by'] = $this->createdBy;
    $json['tractor'] = $this->tractor;
    return $json;
  }
}
