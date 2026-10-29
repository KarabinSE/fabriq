<?php

namespace Karabin\Fabriq\Repositories;

use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Block;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Repositories\Interfaces\PageRepositoryInterface;

class EloquentPageRepository implements PageRepositoryInterface
{
    /**
     * Page model.
     *
     * @var Page
     */
    private $model;

    public function __construct(Slug $slugModel)
    {
        $this->model = Fabriq::getModelClass('page');
        $this->slugModel = $slugModel;
    }

    /**
     * Find by slug.
     *
     * @return mixed
     */
    public function findBySlug(string $slug)
    {
        $model = $this->model->query()
            ->whereHas('slugs', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->where('published', true)
            ->firstOrFail();

        $this->loadLiveBlocks($model);
        $model->slug = $slug;

        return $model;
    }

    public function findPreviewBySlug(string $slug)
    {
        $model = $this->model->whereHas('slugs', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->firstOrFail();

        $this->loadLiveBlocks($model);
        $model->slug = $slug;

        return $model;
    }

    public function findByIds(array $ids)
    {
        $models = $this->model->whereIn('id', $ids)
            ->orderBy('sortindex')
            ->get()->transform(function ($model) {
                $model->content = $model->getSimpleFieldContent();

                return $model;
            });

        return $models;
    }

    private function loadLiveBlocks(Page $page): void
    {
        $blocks = Block::query()
            ->whereMorphedTo('model', $page)
            ->where('locale', app()->getLocale())
            ->whereHidden(false)
            ->whereNull('parent_id')
            ->with('blockType')
            ->with('children', function ($query) {
                return $query->whereHidden(false);
            })
            ->orderBy('sortindex')
            ->get();

        $page->setRelation('blocks', $blocks);
    }
}
