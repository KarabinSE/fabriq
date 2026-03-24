<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Locale extends Model
{
    protected $table = 'i18n_locales';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('locales'));
        static::deleted(fn () => Cache::forget('locales'));
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', 1);
    }

    public function cachedLocales(): Collection
    {
        return Cache::rememberForever('locales', function () {
            return self::enabled()->orderBy('sort_index')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->iso_code => $item];
                });
        });
    }
}
