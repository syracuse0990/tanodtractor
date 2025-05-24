<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use CommonModelTraits;
    use HasFactory;

    public const TYPE_TRACTOR = 1;
    public const TYPE_FEEDBACK = 2;
    public const TYPE_DEVICE = 3;
    public const TYPE_OVERVIEW = 4;
    public const TYPE_FARMER = 5;
    public const TYPE_REPORT_PDF = 6;
    public const TYPE_TRACTOR_IMPORT = 7;
    public const TYPE_ASSET_IMPORT = 8;
    public const TYPE_MAINTENANCE_IMPORT = 9;
    public const TYPE_REPORT_CSV = 10;


    protected $fillable = ['file_name', 'type_id', 'created_by', 'progress'];

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
}
