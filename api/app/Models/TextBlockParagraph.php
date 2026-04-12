<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextBlockParagraph extends Model
{
    use HasFactory;

    protected $fillable = [
        'text_block_id',
        'title',
        'text',
        'text_html',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function textBlock()
    {
        return $this->belongsTo(TextBlock::class);
    }
}
