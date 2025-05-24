<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AllocationDetail
 *
 * @property $id
 * @property $group_id
 * @property $user_id
 * @property $tractor_id
 * @property $device_id
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class AllocationDetail extends Model
{

  static $rules = [];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['group_id', 'user_id', 'tractor_id', 'device_id'];

  public function tractor()
  {
    return $this->hasOne(Tractor::class, 'id', 'tractor_id');
  }

  public function user()
  {
    return $this->hasOne(User::class, 'id', 'user_id');
  }

  public function device()
  {
    return $this->hasOne(Device::class, 'id', 'device_id');
  }

  public function group()
  {
    return $this->hasOne(TractorGroup::class, 'id', 'group_id');
  }
}
