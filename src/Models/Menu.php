<?php

namespace Karabin\Fabriq\Models;

use Karabin\Fabriq\Actions\BustCacheWithWebhook;
use Karabin\Fabriq\Database\Factories\MenuFactory;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Services\CacheBuster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    public const RELATIONSHIPS = [];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($menu) {
            $tagsToFlush = (new CacheBuster)->getCacheKeys($menu, ['fabriq_menu_'.$menu->slug]);
            (new BustCacheWithWebhook)->handle($tagsToFlush->toArray());
        });

        static::deleted(function ($menu) {
            $tagsToFlush = (new CacheBuster)->getCacheKeys($menu, ['fabriq_menu_'.$menu->slug]);
            (new BustCacheWithWebhook)->handle($tagsToFlush->toArray());
        });
    }

    protected static function newFactory(): MenuFactory
    {
        return MenuFactory::new();
    }

    public function items(): HasMany
    {
        return $this->hasMany(Fabriq::getFqnModel('menuItem'));
    }
}
