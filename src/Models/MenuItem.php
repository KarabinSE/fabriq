<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\Data\ContentValueMetaData;
use Karabin\Fabriq\Database\Factories\MenuItemFactory;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Traits\HasContent;

class MenuItem extends Model
{
    use HasContent, HasFactory, NodeTrait;

    public const RELATIONSHIPS = ['page'];

    public function getContentCacheKeys(): array
    {
        return ['fabriq_menu'];
    }

    protected $with = ['page'];

    protected $appends = ['title'];

    protected $guarded = [];

    protected $touches = ['menu'];

    public array $templateSluggable = [];

    protected $casts = [
        'menu_id' => 'int',
        '_lft' => 'int',
        '_rgt' => 'int',
    ];

    protected static function newFactory(): MenuItemFactory
    {
        return MenuItemFactory::new();
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function (MenuItem $menuItem) {
            $menuItem->locale ??= app()->getLocale();
        });

        static::deleting(function ($menuItem) {
            DB::table('slugs')->where('model_id', $menuItem->id)
                ->where('model_type', 'Karabin\Fabriq\Models\MenuItem')
                ->delete();
        });
    }

    protected function getContentTemplateSlug(): ?string
    {
        return 'menu-item';
    }

    public function getContentOptions(): ContentOptions
    {
        return ContentOptions::create()
            ->registerSpecialTypes(['file'])
            ->registerGetters(['file' => 'getFiles'])
            ->registerResourceReferenceKeys(['file']);
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        /** @var BelongsTo<Page, $this> */
        return $this->belongsTo(Fabriq::getFqnModel('page'));
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id');
    }

    /**
     * Getter for files.
     *
     * @return mixed
     */
    public function getFiles(ContentValue $contentValue)
    {
        if (! $contentValue->value) {
            return new ContentValueMetaData($contentValue->id);
        }

        return FileGetter::get($contentValue);
    }

    /**
     * Get the title attribute.
     */
    public function getTitleAttribute(): string
    {
        $locale = $this->locale ?: app()->getLocale();
        $content = $this->getSimpleFieldContent($locale);

        if ($this->type === 'internal' && ! $this->has_custom_title) {
            $pageTitle = $this->page?->getSimpleFieldContent($locale)->get('page_title');

            if ($pageTitle) {
                return (string) $pageTitle;
            }

            return (string) ($this->getSlug($locale)->source_string ?? '');
        }

        return (string) ($content['title'] ?? '');
    }

    /**
     * Get string of the slug.
     *
     * @return string
     */
    public function getSlugString(string $locale = '')
    {
        return $this->getSlug($locale)->slug ?? '';
    }

    /**
     * Get the slug.
     *
     * @return mixed
     */
    public function getSlug(string $locale = '')
    {
        if (! $locale) {
            $locale = app()->getLocale();
        }
        try {
            return $this->page->slugs->where('locale', $locale)->first();
        } catch (\Throwable $th) {
            return new Slug;
        }
    }

    public function getRelativePathAttribute(): string
    {
        return (string) ($this->path ?? '');
    }

    /**
     * Skip setting title.
     *
     * @param  mixed  $value
     */
    public function setTitleAttribute($value): void {}

    /**
     * Skip setting page attribute.
     *
     * @param  mixed  $value
     */
    public function setPageAttribute($value): void {}

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        /** @var BelongsTo<Menu, $this> */
        return $this->belongsTo(Fabriq::getFqnModel('menu'));
    }

    public function scopeRootForMenu(Builder $query, int $menuId, ?string $locale = null): Builder
    {
        return $query
            ->where('menu_id', $menuId)
            ->where('locale', $locale ?? app()->getLocale())
            ->whereNull('parent_id');
    }
}
