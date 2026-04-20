<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'thumbnail',
        'media',
        'order',
        'active',
    ];

    protected $casts = [
        'media' => 'array',
        'order' => 'integer',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
