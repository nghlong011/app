<?php
namespace App\Imports;

use App\Models\Platform;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use App\Models\Translation;
use App\Models\PlatformTranslations;

class PlatformsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row) || !isset($row['title'])) {
            return null;
        }
        $platformData = [
            'title' => $row['title'],
            'slug' => Str::slug($row['title']),
            'navbar' => $row['navbar'] ? 1 : 0,
            'footer' => $row['footer'] ? 1 : 0,
            'right_column' => $row['right_column'] ? 1 : 0,
            'sort' => Platform::max('sort') + 1,
        ];
        $platform = Platform::updateOrCreate(
            ['id' => $row['id']],
            $platformData
        );
        foreach (Translation::all() as $lang) {
            PlatformTranslations::create([
                'platform_id' => $platform->id,
                'lang_id' => $lang->id,
                'title' => $row['title'],
            ]);
        }
        return $platform;
    }
}

