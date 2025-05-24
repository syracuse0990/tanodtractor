<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Page
 *
 * @property $id
 * @property $title
 * @property $description
 * @property $page_type
 * @property $state_id
 * @property $type_id
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Page extends Model
{
  use CommonModelTraits;

  public const TERMS_CONDITION = 1;
  public const PRIVACY_POLICY = 2;

  static $rules = [
    'title' => 'required',
    'page_type' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['title', 'description', 'page_type', 'state_id', 'type_id', 'created_by'];

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

  public static function pageOptions()
  {
    return [
      self::TERMS_CONDITION => 'Terms and Conditions',
      self::PRIVACY_POLICY => 'Privacy Policy',
    ];
  }

  public function getPage()
  {
    $list = self::pageOptions();
    return !empty($list[$this->page_type]) ? $list[$this->page_type] : 'Not Defined';
  }

  public function createdBy()
  {
    return $this->hasOne('App\Models\User', 'id', 'created_by');
  }

  public function createJsonResponse()
  {
    $json['id'] = $this->id;
    $json['title'] = $this->title;
    $json['description'] = $this->description;
    $json['page_type'] = $this->page_type;
    $json['created_by'] = $this->createdBy;

    return $json;
  }
}
