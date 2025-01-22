<?php

namespace App\Imports;

use App\Models\Application;
use App\Models\Version;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;

class ApplicationImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Check if row is empty or invalid
        if (empty($row) || !isset($row['title'])) {
            return null;
        }

        $image_name = null;
        if (!empty($row['image'])) {
            try {
                $imageData = file_get_contents($row['image']);
                if ($imageData) {
                    $image_name = image_upload($imageData, 200, 200, '', 85, 1, 0);
                }
            } catch (\Exception $e) {
                report($e);
            }
        }

        $screenshots = [];
        if (!empty($row['screenshots'])) {
            try {
                $screenshots_links = explode(';', $row['screenshots']);
                foreach ($screenshots_links as $screenshot_link) {
                    $screenshot_link = trim($screenshot_link);
                    $imageData = file_get_contents($screenshot_link);
                    if ($imageData) {
                        $screenshots[] = image_upload($imageData, 200, 200, '', 85, 1, 6);
                    }
                }
            } catch (\Exception $e) {
                report($e);
            }
        }
        $screenshots = implode(',', $screenshots);

        // Validate required fields
        if (!isset($row['id']) || !isset($row['title'])) {
            return null;
        }
        //slug tạo từ title
        $row['slug'] = Str::slug($row['title']);
        
        $appData = [
            'title' => $row['title'], 
            'slug' => $row['slug'],
            'description' => $row['description'] ?? null,
            'package_name' => $row['package_name'] ?? null,
            'details' => $row['details'] ?? null,
            'image' => $image_name,
            'license' => $row['license'] ?? null,
            'developer' => $row['developer'] ?? null,
            'buy_url' => $row['buy_url'] ?? null,
            'type' => $row['type'] ?? 0,
            'up_votes' => $row['up_votes'] ?? 0,
            'down_votes' => $row['down_votes'] ?? 0,
            'featured' => $row['featured'] ?? 0,
            'must_have' => $row['must_have'] ?? 0,
            'editors_choice' => $row['editors_choice'] ?? 0,
            'screenshots' => $screenshots ?? null,
        ];

        $app = Application::updateOrCreate(
            ['id' => $row['id']],
            $appData
        );

        if (!empty($row['categories'])) {
            $categories = explode(';', $row['categories']);
            $app->categories()->sync($categories);
        }

        if (!empty($row['platforms'])) {
            $platforms = explode(';', $row['platforms']);
            $app->platforms()->sync($platforms);
        }

        // Validate version data before creating
        if (isset($row['version']) && isset($row['url'])) {
            Version::create([
                'app_id' => $app->id,
                'version' => $row['version'],
                'file_size' => $row['file_size'] ?? null,
                'url' => $row['url'],
                'counter' => $row['counter'] ?? 0,
            ]);
        }

        return $app;
    }
}