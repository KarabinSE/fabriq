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
            ->where('model_type', $page->getMorphClass())
            ->where('model_id', $page->getKey())
            ->where('locale', app()->getLocale())
            ->with('blockType')
            ->orderBy('sortindex')
            ->get();

        $blocksByParent = $blocks->groupBy('parent_id');
        $buildTree = function (int|string|null $parentId) use (&$buildTree, $blocksByParent) {
            return $blocksByParent->get($parentId, collect())
                ->each(function (Block $block) use (&$buildTree) {
                    $block->setRelation('children', $buildTree($block->getKey()));
                })
                ->values();
        };

        $page->setRelation('blocks', $buildTree(null));
    }
}
