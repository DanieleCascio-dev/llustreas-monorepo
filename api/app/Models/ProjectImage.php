<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'src',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function textBlock()
    {
        return $this->hasOne(TextBlock::class);
    }

    public function isTextImageBlock(): bool
    {
        return $this->type === 'text_image_block';
    }
}
