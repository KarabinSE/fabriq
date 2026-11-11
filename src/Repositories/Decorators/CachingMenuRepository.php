<?php

namespace Karabin\Fabriq\Repositories\Decorators;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;
use Karabin\Fabriq\Repositories\Interfaces\MenuRepositoryInterface;

class CachingMenuRepository implements MenuRepositoryInterface
{
    /**
     * Menu repository interface.
     *
     * @var MenuRepositoryInterface
     */
    protected $repository;

    /**
     * Cache repository.
     *
     * @var Cache
     */
    protected $cache;

    public function __construct(MenuRepositoryInterface $repository, Cache $cache)
    {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Find by slug.
     *
     * @return mixed
     */
    public function findBySlug(string $slug)
    {
        $locale = app()->getLocale();
        $menu = $this->cache->tags(['fabriq_menu'])
            ->rememberForever('fabriq_menu_'.$slug.'_'.$locale, function () use ($slug, $locale) {
                Log::info('Caching menu', ['cache_key' => 'fabriq_menu_'.$slug.'_'.$locale, 'fabriq_menu']);

                return $this->repository->findBySlug($slug);
            });

        return $menu;
    }
}
