<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Application extends Model
{
    use Sluggable;
    use \Conner\Tagging\Taggable;

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
        'page_views',
        'package_name',
        'title',
        'description',
        'details',
        'image',
        'slug',
        'file_size',
        'license',
        'developer',
        'url',
        'buy_url',
        'type',
        'counter',
        'category',
        'platform',
        'tags',
        'screenshots',
        'up_votes',
        'down_votes',
        'featured',
        'must_have',
        'editors_choice',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class);
    }

    public function versions()
    {
        return $this->belongsToMany(Application::class, 'versions', 'app_id', 'app_id');
    }

}
