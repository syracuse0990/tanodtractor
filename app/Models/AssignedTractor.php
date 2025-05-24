<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AssignedTractor
 *
 * @property $id
 * @property $user_id
 * @property $tractor_id
 * @property $type_id
 * @property $state_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class AssignedTractor extends Model
{
    
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
    protected $fillable = ['user_id','tractor_id','type_id','state_id','created_by','group_id'];



}
