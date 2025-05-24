<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FarmAsset
 *
 * @property $id
 * @property $number_plate
 * @property $mileage
 * @property $condition
 * @property $type_id
 * @property $state_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class FarmAsset extends Model
{
  use CommonModelTraits;

  public const TYPE_HARVESTOR = 1;
  public const TYPE_MOTOR = 2;
  public const TYPE_VEHICLE = 3;
  public const TYPE_OTHER = 4;

  static $rules = [];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['number_plate', 'mileage', 'condition', 'type_id', 'state_id', 'created_by'];

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

  public static function typeOptions()
  {
    return [
      self::TYPE_HARVESTOR => "Harvestor",
      self::TYPE_MOTOR => "Motor",
      self::TYPE_VEHICLE => "Vehicle",
      self::TYPE_OTHER => "Other",
    ];
  }

  public function getType()
  {
    $list = self::typeOptions();
    return isset($list[$this->type_id]) ? $list[$this->type_id] : 'N/A';
  }

  public function getCondition()
  {
    return $this->condition == 2 ? 'New' : ($this->condition == 1 ? 'Old' : '');
  }

  public function createdBy()
  {
    return $this->hasOne(User::class, 'id', 'created_by');
  }

  public function jsonResponse()
  {
    $json['id'] = $this->id;
    $json['number_plate'] = $this->number_plate;
    $json['mileage'] = $this->mileage;
    $json['type_id'] = $this->getType();
    $json['condition'] = $this->getCondition();
    $json['created_by'] = $this->createdBy?->name;
    $json['updated_at'] = $this->updated_at;
    $json['created_at'] = $this->created_at;
    return $json;
  }
}
