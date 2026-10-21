<?php

namespace Karabin\Fabriq\Models;

use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\Database\Factories\ArticleFactory;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Traits\HasContent;

class Article extends Model
{
    use BroadcastsEvents, HasContent, HasFactory;

    public const RELATIONSHIPS = ['template', 'template.fields', 'slugs'];

    public function getContentCacheKeys(): array
    {
        return ['fabriq_articles', 'fabriq_articles|slug'];
    }

    protected $guarded = ['content'];

    /**
     * @var array
     */
    protected $dates = ['publishes_at', 'unpublishes_at'];

    protected $casts = [
        'publishes_at' => 'datetime',
        'unpublishes_at' => 'datetime',
    ];

    public array $templateSluggable = ['title'];

    /**
     * Create a new factory.
     */
    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    protected function getContentTemplateSlug(): ?string
    {
        return 'article';
    }

    public function getContentOptions(): ContentOptions
    {
        return ContentOptions::create()
            ->registerSpecialTypes(['image'])
            ->registerGetters(['image' => 'getImages'])
            ->registerResourceReferenceKeys(['image']);
    }

    /**
     * Relation for slugs.
     */
    public function slugs(): MorphMany
    {
        return $this->morphMany(Fabriq::getFqnModel('slug'), 'model');
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

    public function getIsPublishedAttribute(): bool
    {
        if (! isset($this->attributes['publishes_at'])) {
            return false;
        }

        if ($this->attributes['has_unpublished_time'] && now()->isAfter(Carbon::parse($this->attributes['unpublishes_at']))) {
            return false;
        }

        if (now()->isAfter(Carbon::parse($this->attributes['publishes_at']))) {
            return true;
        }

        return false;
    }

    protected function getTimeZone(): string
    {
        $header = request()->header('X-TIMEZONE', 'Europe/Stockholm');
        // if (is_array($header)) {
        //     return 'Europe/Stockholm';
        // }

        return $header;
    }

    /**
     * Set publishes at attribute.
     *
     * @param  string|null  $value
     */
    public function setPublishesAtAttribute($value): void
    {
        ($value) ? $this->attributes['publishes_at'] = Carbon::parse($value, $this->getTimeZone())->shiftTimezone('UTC')->toDateTimeString() : $this->attributes['publishes_at'] = null;
    }

    /**
     * Set publishes at attribute.
     *
     * @param  string|null  $value
     */
    public function setUnPublishesAtAttribute($value): void
    {
        ($value) ? $this->attributes['unpublishes_at'] = Carbon::parse($value, $this->getTimeZone())->shiftTimezone('UTC')->toDateTimeString() : $this->attributes['unpublishes_at'] = null;
    }

    /**
     * Search for articles.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereLike(['name', 'publishes_at'], $search);
    }

    /**
     * Scope for published articles.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publishes_at', '<=', now())
            ->where(function (Builder $query) {
                $query->where('unpublishes_at', '>=', now())
                    ->orWhereNull('unpublishes_at');
            })
            ->orderBy('publishes_at', 'DESC');
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param  string  $event
     * @return Channel|array
     */
    public function broadcastOn($event)
    {
        $prefix = config('broadcasting.connections.pusher.key');

        return [new Channel($prefix.'-article'), new Channel('article.'.$this->id)];
    }
}
