<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_image_id',
        'subtitle',
        'title',
        'text_color',
        'background_color',
        'layout',
        'image_position',
    ];

    public function projectImage()
    {
        return $this->belongsTo(ProjectImage::class);
    }

    public function paragraphs()
    {
        return $this->hasMany(TextBlockParagraph::class)->orderBy('order');
    }
}
