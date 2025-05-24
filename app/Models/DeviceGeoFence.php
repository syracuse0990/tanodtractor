<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DeviceGeoFence
 *
 * @property $id
 * @property $imei
 * @property $geo_fence_id
 * @property $latitude
 * @property $longitude
 * @property $radius
 * @property $fence_name
 * @property $zoom_level
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
class DeviceGeoFence extends Model
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
    protected $fillable = ['imei', 'geo_fence_id', 'latitude', 'longitude', 'radius', 'fence_name', 'zoom_level', 'date', 'state_id', 'type_id', 'created_by'];

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

    public function createJsonResponse()
    {
        $json['id'] = $this->id;
        $json['geo_fence_id'] = $this->geo_fence_id ? explode(',', $this->geo_fence_id) : [];
        $json['imei'] = $this->imei ? explode(',', $this->imei) : [];
        $json['latitude'] = $this->latitude;
        $json['longitude'] = $this->longitude;
        $json['radius'] = $this->radius;
        $json['fence_name'] = $this->fence_name;
        $json['zoom_level'] = $this->zoom_level;
        $json['date'] = $this->date;
        $json['state_id'] = $this->state_id;
        $json['type_id'] = $this->type_id;
        $json['created_at'] = $this->created_at;
        $json['updated_at'] = $this->updated_at;
        $json['created_by'] = $this->createdBy;

        return $json;
    }

    public function createdBy()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_by');
    }
}
