<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Karabin\Fabriq\Concerns\BroadcastsModelEvents;
use Karabin\Fabriq\Concerns\HasPaths;
use Karabin\Fabriq\ContentGetters\ButtonGetter;
use Karabin\Fabriq\ContentGetters\ButtonsGetter;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\ContentGetters\MediaGetter;
use Karabin\Fabriq\ContentGetters\SmartBlockGetter;
use Karabin\Fabriq\ContentGetters\VideoGetter;
use Karabin\Fabriq\Database\Factories\PageFactory;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Traits\Commentable;
use Karabin\Fabriq\Traits\HasContent;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use BroadcastsModelEvents, Commentable, HasContent, HasFactory, HasPaths, InteractsWithMedia, NodeTrait;

    public const RELATIONSHIPS = ['template', 'template.fields'];

    public function getContentCacheKeys(): array
    {
        return ['fabriq_menu', 'fabriq_pages|slug'];
    }

    protected $fillable = ['id', 'sortindex'];

    public array $templateSluggable = ['page_title'];

    protected $casts = [
        '_lft' => 'int',
        '_rgt' => 'int',
        'parent_id' => 'int',
    ];

    /**
     * Create a new factory.
     */
    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function ($page) {
            MenuItem::where('page_id', $page->id)->get()->each(function ($item) {
                $item->delete();
            });

            $modelTypeByMorph = config('fabriq.morph_map.'.Fabriq::getFqnModel('page')) ?? Fabriq::getFqnModel('page');

            DB::table('slugs')->where('model_id', $page->id)
                ->where('model_type', $modelTypeByMorph)
                ->delete();

            DB::table('search_terms')->where('model_id', $page->id)
                ->where(function ($query) use ($modelTypeByMorph) {
                    return $query->where('model_type', $modelTypeByMorph)
                        ->orWhere('model_type', Fabriq::getFqnModel('page'));
                })
                ->delete();
        });

        static::created(function ($page) {
            $content = [
                'page_title' => $page->name,
            ];
            $localPage = Fabriq::getModelClass('page')->find($page->id);
            $supportedLocales = Fabriq::getModelClass('locale')->cachedLocales();
            $supportedLocales->each(function ($locale, $key) use ($content, $localPage) {
                $localPage->updateContent($content, (string) $key);
            });
        });
    }

    public function getContentOptions(): ContentOptions
    {
        return ContentOptions::create()
            ->registerSpecialTypes([
                'image', 'main_image', 'meta_og_image', 'logo_image', 'file',
                'video', 'button', 'buttons', 'smartBlock', 'media',
            ])
            ->registerGetters([
                'image' => 'getImages',
                'main_image' => 'getImages',
                'meta_og_image' => 'getImages',
                'logo_image' => 'getImages',
                'file' => 'getFiles',
                'video' => 'getVideos',
                'button' => 'getButton',
                'buttons' => 'getButtons',
                'smartBlock' => 'getSmartBlock',
                'media' => 'getMediaViaType',
            ])
            ->registerResourceReferenceKeys([
                'image', 'main_image', 'meta_og_image', 'logo_image', 'file', 'video', 'smartBlock',
            ])
            ->setIndexable(
                (bool) $this->published,
                ['page_title', 'page_header', 'header'],
                'page_title',
            );
    }

    /**
     * Getter for images.
     *
     * @return mixed
     */
    public function getImages(ContentValue $contentValue)
    {
        return ImageGetter::get($contentValue);
    }

    /**
     * Getter for files.
     *
     * @return mixed
     */
    public function getFiles(ContentValue $contentValue)
    {
        return FileGetter::get($contentValue);
    }

    /**
     * Getter for videos.
     *
     * @return mixed
     */
    public function getVideos(ContentValue $contentValue)
    {
        return VideoGetter::get($contentValue);
    }

    /**
     * Getter for button.
     *
     * @return mixed
     */
    public function getButton(ContentValue $contentValue)
    {
        return ButtonGetter::get($contentValue);
    }

    /**
     * Getter for buttons.
     *
     * @return mixed
     */
    public function getButtons(ContentValue $contentValue)
    {
        return ButtonsGetter::get($contentValue);
    }

    /**
     * Getter for buttons.
     *
     * @return mixed
     */
    public function getSmartBlock(ContentValue $contentValue)
    {
        return SmartBlockGetter::get($contentValue);
    }

    public function getMediaViaType(ContentValue $contentValue)
    {
        return MediaGetter::get($contentValue);
    }

    public function blocks(): MorphMany
    {
        return $this->morphMany(Fabriq::getFqnModel('block'), 'model')
            ->where('model_type', 'fabriq_page')
            ->orderBy('blocks.sortindex');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(Fabriq::getFqnModel('menuItem'));
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(Fabriq::getFqnModel('user'), 'updated_by', 'id');
    }

    /**
     * Relation for slugs.
     */
    public function slugs(): MorphMany
    {
        return $this->morphMany(Fabriq::getFqnModel('slug'), 'model');
    }

    public function latestSlug(): MorphOne
    {
        return $this->morphOne(Fabriq::getFqnModel('slug'), 'model')->ofMany([], function ($query) {
            $query->where('locale', app()->getLocale());
        });
    }

    /**
     * Scope query to find by Slug.
     */
    public function scopeWhereSlug(Builder $query, string $slug): Builder
    {
        return $query->whereHas('slugs', function (Builder $query) use ($slug) {
            $query->where('slug', $slug);
        });
    }

    /**
     * Search for pages.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereLike(['name', 'template.name'], $search);
    }

    /**
     * Set localized content.
     *
     * @param  array  $value
     */
    public function setLocalizedContentAttribute($value): void
    {
        // Sort by locale order, so that primary locale overwrites other locales when
        // saving non localized meta data
        $locales = Fabriq::getModelClass('locale')->cachedLocales();
        $value = collect($value)->sortBy(function ($item, $key) use ($locales) {
            return data_get($locales->firstWhere('iso_code', $key), 'sort_index', PHP_INT_MAX);
        })->reverse()->toArray();

        foreach ($value as $locale => $localeContent) {
            $this->updateContent($localeContent, (string) $locale);
        }
    }
}
