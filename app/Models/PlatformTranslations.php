<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PlatformTranslations extends Model
{
    use HasFactory;
    protected $table = 'platform_translations';
    protected $fillable = ['platform_id', 'lang_id', 'title'];
}
