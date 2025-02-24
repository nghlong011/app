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
        try {
            // Check if row is empty or invalid
            if (empty($row) || !isset($row['title'])) {
                return null;
            }

            $client = new Client(['timeout' => 30.0]);
            $image_name = null;

            if (!empty($row['image'])) {
                try {
                    $response = $client->get($row['image']);
                    if ($response->getStatusCode() == 200) {
                        $imageData = $response->getBody()->getContents();
                        
                        // Kiểm tra xem dữ liệu hình ảnh có hợp lệ không
                        try {
                            $img = Image::make($imageData);
                            // Nếu có thể tạo được đối tượng Image, tức là hình ảnh hợp lệ
                            $image_name = image_upload($imageData, 200, 200, '', 85, 1, 0);
                        } catch (\Exception $e) {
                            throw new \Exception("Hình ảnh không hợp lệ hoặc bị hỏng: " . $row['image']);
                        }
                    }
                } catch (RequestException $e) {
                    throw new \Exception("Không thể tải hình ảnh từ URL: " . $row['image']);
                }
            }

            $screenshots = [];
            if (!empty($row['screenshots'])) {
                try {
                    $screenshots_links = explode(';', $row['screenshots']);
                    foreach ($screenshots_links as $screenshot_link) {
                        $screenshot_link = trim($screenshot_link);
                        try {
                            $response = $client->get($screenshot_link);
                            if ($response->getStatusCode() == 200) {
                                $imageData = $response->getBody()->getContents();
                                
                                // Kiểm tra tính hợp lệ của screenshot
                                try {
                                    $img = Image::make($imageData);
                                    $screenshots[] = image_upload($imageData, 400, 400, '', 85, 1, 6);
                                } catch (\Exception $e) {
                                    throw new \Exception("Screenshot không hợp lệ hoặc bị hỏng: " . $screenshot_link);
                                }
                            }
                        } catch (RequestException $e) {
                            throw new \Exception("Không thể tải screenshot từ URL: " . $screenshot_link);
                        }
                    }
                } catch (\Exception $e) {
                    throw new \Exception("Lỗi xử lý screenshots: " . $e->getMessage());
                }
            }
            $screenshots = implode(',', $screenshots);

            // Validate required fields
            if (!isset($row['id']) || !isset($row['title'])) {
                throw new \Exception("Thiếu trường bắt buộc (ID hoặc Title)");
            }

            //slug tạo từ title
            $row['slug'] = Str::slug($row['title']);
            
            // Kiểm tra slug đã tồn tại chưa
            $existingApp = Application::where('slug', $row['slug'])
                                    ->where('id', '!=', $row['id'])
                                    ->first();
            if ($existingApp) {
                throw new \Exception("Slug '{$row['slug']}' đã tồn tại cho ứng dụng khác");
            }
            
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
                try {
                    $categories = explode(';', $row['categories']);
                    $app->categories()->sync($categories);
                } catch (\Exception $e) {
                    throw new \Exception("Lỗi khi xử lý categories: " . $e->getMessage());
                }
            }

            if (!empty($row['platforms'])) {
                try {
                    $platforms = explode(';', $row['platforms']);
                    $app->platforms()->sync($platforms);
                } catch (\Exception $e) {
                    throw new \Exception("Lỗi khi xử lý platforms: " . $e->getMessage());
                }
            }

            // Validate version data before creating
            if (isset($row['version']) && isset($row['url'])) {
                try {
                    Version::updateOrCreate(
                        ['app_id' => $app->id, 'version' => $row['version']],
                        [
                            'file_size' => $row['file_size'] ?? null,
                            'url' => $row['url'],
                            'counter' => $row['counter'] ?? 0,
                        ]
                    );
                } catch (\Exception $e) {
                    throw new \Exception("Lỗi khi tạo version: " . $e->getMessage());
                }
            }

            return $app;
            
        } catch (\Exception $e) {
            // Ghi log lỗi chi tiết
            \Log::error("Lỗi import dòng ID {$row['id']}: " . $e->getMessage());
            throw $e;
        }
    }
}