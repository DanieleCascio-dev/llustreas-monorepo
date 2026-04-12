<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroLayerImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_layer_id',
        'mobile_src',
        'desktop_src',
        'alt',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function layer(): BelongsTo
    {
        return $this->belongsTo(HeroLayer::class, 'hero_layer_id');
    }
}
