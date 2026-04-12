<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeroLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'z_index',
        'parallax_speed',
        'breakpoint',
        'css_config',
        'order',
    ];

    protected $casts = [
        'z_index'        => 'integer',
        'parallax_speed' => 'float',
        'breakpoint'     => 'integer',
        'css_config'     => 'array',
        'order'          => 'integer',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(HeroLayerImage::class)->orderBy('order');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
