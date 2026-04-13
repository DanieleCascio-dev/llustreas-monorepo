<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'src',
        'title',
        'column_index',
        'order',
        'is_preview',
        'layout',
    ];

    protected $casts = [
        'column_index' => 'integer',
        'order' => 'integer',
        'is_preview' => 'boolean',
    ];

    public function scopePreview($query)
    {
        return $query->where('is_preview', true);
    }

    public function scopeByColumn($query, int $column)
    {
        return $query->where('column_index', $column);
    }

    public function scopeByLayout($query, string $layout)
    {
        return $query->where('layout', $layout);
    }
}
