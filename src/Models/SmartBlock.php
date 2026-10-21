<?php

namespace Karabin\Fabriq\Models;

use Karabin\Fabriq\ContentGetters\ButtonGetter;
use Karabin\Fabriq\ContentGetters\ButtonsGetter;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\ContentGetters\VideoGetter;
use Karabin\Fabriq\Database\Factories\SmartBlockFactory;
use Karabin\Fabriq\Traits\HasContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartBlock extends Model
{
    use HasContent, HasFactory;

    public const RELATIONSHIPS = [];

    public function getContentCacheKeys(): array
    {
        return ['fabriq_pages', 'fabriq_smart_blocks'];
    }

    protected static function newFactory(): SmartBlockFactory
    {
        return SmartBlockFactory::new();
    }

    protected function getContentTemplateSlug(): ?string
    {
        return 'smart_block';
    }

    protected function getContentGetters(): array
    {
        return [
            'image' => 'getImages',
            'button' => 'getButton',
            'buttons' => 'getButtons',
            'file' => 'getFiles',
            'video' => 'getVideos',
        ];
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
     * @return mixed
     */
    public function getImages(ContentValue $meta)
    {
        return ImageGetter::get($meta);
    }

    /**
     * @return mixed
     */
    public function getFiles(ContentValue $meta)
    {
        return FileGetter::get($meta);
    }

    /**
     * @return mixed
     */
    public function getVideos(ContentValue $meta)
    {
        return VideoGetter::get($meta);
    }

    /**
     * Getter for button.
     *
     * @return mixed
     */
    public function getButton(ContentValue $meta)
    {
        return ButtonGetter::get($meta);
    }

    /**
     * Getter for buttons.
     *
     * @return mixed
     */
    public function getButtons(ContentValue $meta)
    {
        return ButtonsGetter::get($meta);
    }

    /**
     * Search for smart blocks.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereLike(['name'], $search);
    }
}
