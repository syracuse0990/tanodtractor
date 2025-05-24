<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TractorGroup
 *
 * @property $id
 * @property $name
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TractorGroup extends Model
{
  use CommonModelTraits;

  public const STATE_INACTIVE = 0;
  public const STATE_ACTIVE = 1;
  public const STATE_DELETED = 2;

  static $rules = [
    'name' => 'required',
    'tractor_ids' => 'required',
    'farmer_ids' => 'required',
    'device_ids' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['name', 'farmer_ids', 'tractor_ids', 'device_ids', 'state_id', 'type_id', 'created_by'];

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

  public function tractors()
  {
    return $this->hasMany('App\Models\Tractor', 'group_id', 'id');
  }

  public function groupJsonResponse()
  {
    $tractors = Tractor::with('images')->whereIn('id', json_decode($this->tractor_ids, true))->get();
    $farmers = User::whereIn('id', json_decode($this->farmer_ids, true))->get();
    $devices = Device::whereIn('id', json_decode($this->device_ids, true))->get();

    $json['id'] = $this->id;
    $json['tractor_ids'] = $this->tractor_ids;
    $json['farmer_ids'] = $this->farmer_ids;
    $json['device_ids'] = $this->device_ids;
    $json['name'] = $this->name;
    $json['tractors'] = $tractors;
    $json['farmers'] = $farmers;
    $json['devices'] = $devices;

    return $json;
  }
  public function createJsonResponse()
  {
    $tractors = Tractor::with('images')->whereIn('id', json_decode($this->tractor_ids, true))->get();
    $farmers = User::whereIn('id', json_decode($this->farmer_ids, true))->get();
    $devices = Device::whereIn('id', json_decode($this->device_ids, true))->get();

    $json['id'] = $this->id;
    $json['name'] = $this->name;
    $json['tractor_ids'] = json_decode($this->tractor_ids, true);
    $json['farmer_ids'] = json_decode($this->farmer_ids, true);
    $json['device_ids'] = json_decode($this->device_ids, true);
    $json['state_id'] = $this->state_id;
    $json['created_by'] = $this->created_by;
    $json['updated_at'] = $this->updated_at;
    $json['created_at'] = $this->created_at;
    $json['tractors'] = $tractors;
    $json['farmers'] = $farmers;
    $json['devices'] = $devices;


    return $json;
  }

  public function getTractors()
  {
    $tractors = Tractor::whereIn('id', json_decode($this->tractor_ids))->get();
    return $tractors;
  }

  public function getDevices()
  {
    $deviceIds = $this->device_ids ? json_decode($this->device_ids) : [];
    $devices = Device::whereIn('id', $deviceIds)->get();
    return $devices;
  }

  public function subAdmin()
  {
    return $this->hasOne(AssignedGroup::class, 'group_id', 'id');
  }

  public function getUsers()
  {
    $farmerIds = json_decode($this->farmer_ids);
    return User::whereIn('id', $farmerIds)->get();
  }
}
