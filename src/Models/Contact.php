<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\Database\Factories\ContactFactory;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Traits\HasContent;
use Spatie\Tags\HasTags;

class Contact extends Model
{
    use BroadcastsEvents, HasContent, HasFactory, HasTags;

    public const RELATIONSHIPS = ['images', 'tags'];

    public function getContentCacheKeys(): array
    {
        return ['fabriq_contacts'];
    }

    protected $guarded = ['content', 'localizedContent'];

    /**
     * Create a new factory.
     */
    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    protected function getContentTemplateSlug(): ?string
    {
        return 'contact';
    }

    protected function getContentGetters(): array
    {
        return ['image' => 'getImages'];
    }

    public static function getTagClassName(): string
    {
        return Fabriq::getFqnModel('tag');
    }

    /**
     * Getter for images.
     *
     * @param  mixed  $meta
     * @return mixed
     */
    public function getImages($meta)
    {
        return ImageGetter::get($meta);
    }

    /**
     * Get a collection of all images the model has.
     */
    public function images(): MorphToMany
    {
        return $this->morphToMany(Fabriq::getFqnModel('image'), 'imageable')
            ->withPivot('id', 'sortindex')
            ->orderBy('imageables.sortindex');
    }

    /**
     * Search for contacts.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereLike(['name', 'email', 'phone', 'tags.plain_name'], $search);
    }

    /**
     * Set tags.
     *
     * @param  array  $value
     * @return void
     */
    public function setContactTagsAttribute($value)
    {
        if ($value) {
            $this->syncTagsWithType($value, 'contacts');
        } else {
            $this->syncTagsWithType([], 'contacts');
        }
    }

    /**
     * Set localized content.
     *
     * @param  array  $value
     * @return void
     */
    public function setLocalizedContentAttribute($value)
    {
        foreach ($value as $key => $localeContent) {
            $this->updateContent($localeContent, (string) $key);
        }
    }

    /**
     * Get the title for the event.
     *
     * @return string|null
     */
    public function getImageAttribute()
    {
        return $this->getFieldContent()->get('image');
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param  string  $event
     * @return Channel|array
     */
    public function broadcastOn($event)
    {
        $prefix = config('fabriq.ws_prefix');

        return [new Channel($prefix.'-contact'), new Channel('contact.'.$this->id)];
    }

    public function scopeWithTags(Builder $query, mixed ...$tags): Builder
    {
        return self::scopeWithAnyTags($query, $tags, 'contacts');
    }
}
