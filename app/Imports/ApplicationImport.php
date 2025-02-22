<?php

namespace App\Imports;

use App\Models\Application;
use App\Models\Version;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApplicationImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Check if row is empty or invalid
        if (empty($row) || !isset($row['title'])) {
            return null;
        }

        $client = new Client(['timeout' => 30.0]); // Set timeout to 5 seconds

        $image_name = null;
        if (!empty($row['image'])) {
            try {
                $response = $client->get($row['image']);
                if ($response->getStatusCode() == 200) {
                    $imageData = $response->getBody()->getContents();
                    $image_name = image_upload($imageData, 200, 200, '', 85, 1, 0);
                }
            } catch (RequestException $e) {
                report($e);
            }
        }

        $screenshots = [];
        if (!empty($row['screenshots'])) {
            try {
                $screenshots_links = explode(';', $row['screenshots']);
                foreach ($screenshots_links as $screenshot_link) {
                    $screenshot_link = trim($screenshot_link);
                    $response = $client->get($screenshot_link);
                    if ($response->getStatusCode() == 200) {
                        $imageData = $response->getBody()->getContents();
                        $screenshots[] = image_upload($imageData, 400, 400, '', 85, 1, 6);
                    }
                }
            } catch (RequestException $e) {
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
            'page_views' => $row['page_views'] ?? 0,
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
            Version::updateOrCreate(
                ['app_id' => $app->id, 'version' => $row['version']],
                [
                    'file_size' => $row['file_size'] ?? null,
                    'url' => $row['url'],
                    'counter' => $row['counter'] ?? 0,
                ]
            );
        }

        return $app;
    }
}