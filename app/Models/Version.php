<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = [
        'id',
        'app_id',
        'version',
        'file_size',
        'url',
        'counter',
    ];
}
