<?php

namespace Karabin\Fabriq\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\Fabriq;

trait HasPaths
{
    public function scopeWhereHash(Builder $query, string $hash): Builder
    {
        return $query->where($this->getWhereHashStatement(), $hash);
    }

    /**
     * Get the absolute path.
     *
     * @param  string|null  $path
     */
    public function getAbsolutePath($path): string
    {
        return config('fabriq.front_end_domain')
            .$this->currentLocaleString()
            .$path;
    }

    public function getPermalinkPath(): string
    {
        return config('fabriq.front_end_domain')
            .'/permalink/'
            .hash('md5', (string) $this->id)
            .$this->currentLocaleString();
    }

    public function transformPaths(): array
    {
        return $this->paths->map(function ($item) {
            if (! isset($item[App::currentLocale()])) {
                return null;
            }

            return [
                'absolute_path' => $this->getAbsolutePath($item[App::currentLocale()]['0'] ?? null),
                'permalink' => $this->getPermalinkPath(),
            ];
        })->filter()->first();
    }

    protected function currentLocaleString(): string
    {
        $enabledLocales = Fabriq::getModelClass('locale')->cachedLocales();
        if ($enabledLocales->count() > 1) {
            return '/'.App::getLocale();
        }

        return '';
    }

    protected function getWhereHashStatement(): mixed
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        return ($driver === 'pgsql') ? DB::raw('md5(id::text)') : DB::raw('md5(id)');
    }

    public function getPathsAttribute(): Collection
    {
        $slugGroups = collect([]);

        $supportedLocales = Fabriq::getModelClass('locale')->cachedLocales();
        $menuItems = $this->getMenuItemsForPaths();

        if ($menuItems->isNotEmpty()) {
            foreach ($supportedLocales->keys() as $locale) {
                $localizedPaths = $menuItems
                    ->map(fn ($menuItem) => $menuItem->getTranslation('path', (string) $locale, false) ?? '')
                    ->unique()
                    ->values();

                $slugGroups->push([$locale => $localizedPaths]);
            }

            return $slugGroups;
        }

        foreach ($supportedLocales as $locale) {
            $slugGroups->push([
                $locale->iso_code => $this->slugs->where('locale', $locale->iso_code)->pluck('slug')->map(function ($item) {
                    return '/'.$item;
                }),
            ]);
        }

        return $slugGroups;

    }

    public function getLocalizedPathsAttribute(): Collection
    {
        $slugGroups = collect([]);
        $menuItems = $this->getMenuItemsForPaths();
        $localizedPaths = $menuItems
            ->map(fn ($menuItem) => $menuItem->getTranslation('path', App::currentLocale(), false) ?? '')
            ->unique()
            ->values();
        $slugGroups->push($localizedPaths);

        // If no menu items exists, fetch the latest slug
        if (! $slugGroups[0]->count()) {
            $slugGroups[0]->push(['/'.$this->latestSlug?->slug]);
        }

        return $slugGroups;
    }

    private function getMenuItemsForPaths(): Collection
    {
        if ($this->relationLoaded('menuItems')) {
            return $this->getRelation('menuItems');
        }

        return $this->menuItems()->without('page')->get();
    }
}
