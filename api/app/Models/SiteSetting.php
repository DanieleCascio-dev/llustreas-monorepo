<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, mixed $value): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
