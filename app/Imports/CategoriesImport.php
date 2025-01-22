<?php

namespace App\Imports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use App\Models\CategoryTranslations;
use App\Models\Translation;
class CategoriesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row) || !isset($row['title'])) {
            return null;
        }
        $categoryData = [
            'title' => $row['title'],
            'slug' => Str::slug($row['title']),
            'home_page' => $row['home_page'] ? 1 : 0,
            'navbar' => $row['navbar'] ? 1 : 0,
            'footer' => $row['footer'] ? 1 : 0,
            'right_column' => $row['right_column'] ? 1 : 0,
            'type' => 1,
            'sort' => Category::where('type', '1')->max('sort') + 1,
        ];

        $category = Category::updateOrCreate(
            ['id' => $row['id']],
            $categoryData
        );

        foreach (Translation::all() as $lang) {
            CategoryTranslations::create([
                'cat_id' => $category->id,
                'lang_id' => $lang->id,
                'title' => $row['title'],
            ]);
        }
        return $category;
    }
}
