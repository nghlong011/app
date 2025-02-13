<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Comment extends Model
{
    protected $fillable = [
        'id',
        'content_id',
        'title',
        'name',
        'email',
        'comment',
        'rating',
        'type',
        'approval',
        'ip'
    ];
}
