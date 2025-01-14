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
        return new AppTranslation([
            'app_id' => $row['app_id'], // app_id
            'lang_id' => $row['lang_id'], // lang_id
            'title' => $row['title'], // title
            'description' => $row['description'] ?? '', // description, nếu trống thì để là ''
            'details' => $row['details'] ?? '', // details, nếu trống thì để là ''
            // Các cột custom_title, custom_description, custom_h1 sẽ không được nhập
            // Nếu bạn muốn tự động thêm giá trị mặc định cho các cột này, bạn có thể gán giá trị mặc định
            'custom_title' => null,
            'custom_description' => null,
            'custom_h1' => null,
        ]);
    }
}

