<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use CommonModelTraits;
    use HasFactory;

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
