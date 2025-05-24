<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Tractor
 *
 * @property $id
 * @property $driver_id
 * @property $no_plate
 * @property $id_no
 * @property $engine_no
 * @property $fuel_consumption
 * @property $brand
 * @property $model
 * @property $manufacture_date
 * @property $installation_time
 * @property $installation_address
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Tractor extends Model
{
  use CommonModelTraits;

  public const STATE_INACTIVE = 0;
  public const STATE_ACTIVE = 1;
  public const STATE_DELETED = 2;

  static $rules = [];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['driver_id', 'device_id', 'group_id', 'no_plate', 'id_no', 'engine_no', 'fuel_consumption', 'brand', 'model', 'manufacture_date', 'installation_time', 'installation_address', 'state_id', 'type_id', 'created_by', 'max_speed', 'maintenance_kilometer', 'chasis_no', 'insurance_effect_date', 'insurance_expire_date', 'total_distance', 'running_km', 'first_alert', 'last_alert_hours', 'first_maintenance_hr', 'imei', 'dr_date', 'actual_delivery_date', 'dr_no', 'front_loader_sn', 'rotary_tiller_sn', 'rotating_disc_plow_sn'];

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
  public function driver()
  {
    return $this->hasOne('App\Models\User', 'id', 'driver_id');
  }
  public function device()
  {
    return $this->hasOne('App\Models\Device', 'id', 'device_id');
  }
  public function group()
  {
    return $this->hasOne('App\Models\TractorGroup', 'id', 'group_id');
  }
  public function images()
  {
    return $this->hasMany('App\Models\Image', 'model_id', 'id');
  }

  public function bookings()
  {
    return $this->hasMany('App\Models\TractorBooking', 'tractor_id', 'id');
  }

  public function tractorJsonResponse()
  {
    $tractorBookings = TractorBooking::with('tractor', 'tractor.images', 'device', 'createdBy')->where('tractor_id', $this->id)->orderBy('state_id', 'ASC')->orderBy('id', 'DESC')->get();

    $json['no_plate'] = $this->no_plate;
    $json['id_no'] = $this->id_no;
    $json['engine_no'] = $this->engine_no;
    $json['fuel_consumption'] = $this->fuel_consumption;
    $json['brand'] = $this->brand;
    $json['model'] = $this->model;
    $json['manufacture_date'] = $this->manufacture_date;
    $json['installation_time'] = $this->installation_time;
    $json['installation_address'] = $this->installation_address;
    $json['maintenance_kilometer'] = $this->maintenance_kilometer;
    $json['state_id'] = $this->state_id;
    $json['images'] = $this->images;
    $json['bookings'] = $tractorBookings;

    return $json;
  }

  public static function download($filePath)
  {
    return response()->download($filePath);
  }
}
