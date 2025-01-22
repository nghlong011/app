<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Platform extends Model
{
    use Sluggable;

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
    protected $fillable = [
        'id',
        'title',
        'fa_icon',
        'slug',
        'navbar',
        'footer',
        'right_column',
        'sort',
    ];

    public function applications()
    {
         return $this->belongsToMany(Application::class);
    }
    
}
