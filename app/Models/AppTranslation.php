<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppTranslation extends Model
{
    use HasFactory;

    // Đặt tên bảng nếu không phải là bảng theo chuẩn
    protected $table = 'app_translations'; 

    // Chỉ định các cột được phép mass assign
    protected $fillable = [
        'app_id',
        'lang_id',
        'title',
        'description',
        'details',
        'custom_title',
        'custom_description',
        'custom_h1',
    ];
    public function translation()
    {
        return $this->belongsTo(Translation::class, 'lang_id');
    }
    // Hoặc sử dụng guarded để bảo vệ các cột không được mass assign
    // protected $guarded = ['id', 'created_at', 'updated_at'];
}
