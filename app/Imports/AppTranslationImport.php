<?php

namespace App\Imports;

use App\Models\AppTranslation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AppTranslationImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Chỉ nhập dữ liệu vào các cột app_id, lang_id, title, description, details
        if (empty($row) || !isset($row['app_id'])) {
            return null;
        }
        $appTranslationData=[
            'title' => $row['title'],
            'description' => $row['description'] ?? '',
            'details' => $row['details'] ?? '',
            'custom_title' => null,
            'custom_description' => null,
            'custom_h1' => null,
        ];
        return AppTranslation::updateOrCreate(
            ['app_id' => $row['app_id'], 'lang_id' => $row['lang_id']],
            $appTranslationData
        );
    }
}

