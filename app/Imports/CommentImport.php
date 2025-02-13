<?php

namespace App\Imports;

use App\Models\Comment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use App\Models\CategoryTranslations;
use App\Models\Translation;
use App\Models\Application;
class CommentImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row) || !isset($row['name'])) {
            return null;
        }
        $commentData = [
            'content_id' => $row['content_id'],
            'name' => $row['name'],
            'title' => $row['title'],
            'email' => $row['email'],
            'comment' => $row['comment'],
            'rating' => $row['rating'],
            'type' => 1,
            'approval' => 1,
            'ip' => $row['ip'] ?? '172.16.10.1',
        ];
        $comment = Comment::updateOrCreate(
            ['id' => $row['id']],
            $commentData
        );
        return $comment;
    }
}
