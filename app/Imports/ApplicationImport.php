<?php

namespace App\Imports;

use App\Models\Application;
use App\Models\Version;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Storage;
use Image;

class ApplicationImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Handle image upload if URL provided
        $image_name = null;
        if (!empty($row['image'])) {
            try {
                $imageData = file_get_contents($row['image']);
                if ($imageData) {
                    // Use image_upload helper to process and save image
                    $image_name = image_upload($imageData, 200, 200, '', 85, 1, 0);
                }
            } catch (\Exception $e) {
                // Handle image download/processing error
                report($e);
            }
        }
        $screenshots = [];
        if (!empty($row['screenshots'])) {
            try {
                $screenshots_links = explode(',', $row['screenshots']);
                foreach ($screenshots_links as $screenshot_link) {
                    $screenshots[] = image_upload($screenshot_link, 200, 200, '', 85, 1, 6);
                }
            } catch (\Exception $e) {
                // Handle image download/processing error
                report($e);
            }
        }
        //chuyển mảng thành chuỗi
        $screenshots = implode(',', $screenshots);
        // Create application record
        $app = Application::create([
            'id' => $row['id'],
            'title' => $row['title'], 
            'slug' => $row['slug'],
            'description' => $row['description'],
            'package_name' => $row['package_name'],
            'details' => $row['details'],
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
        ]);
        // Sync categories
        if (!empty($row['categories'])) {
            $categories = explode('.', $row['categories']);
            $app->categories()->sync($categories);
        }

        // Sync platforms 
        if (!empty($row['platforms'])) {
            $platforms = explode('.', $row['platforms']);
            $app->platforms()->sync($platforms);
        }

        // Create version record
        Version::create([
            'app_id' => $app->id,
            'version' => $row['version'],
            'file_size' => $row['file_size'],
            'url' => $row['url'],
            'counter' => $row['counter'],
        ]);

        return $app;
    }
}