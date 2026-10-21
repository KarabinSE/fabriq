<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Karabin\Fabriq\ContentGetters\ButtonGetter;
use Karabin\Fabriq\ContentGetters\ButtonsGetter;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\ContentGetters\VideoGetter;
use Karabin\Fabriq\Database\Factories\SmartBlockFactory;
use Karabin\Fabriq\Traits\HasContent;

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

    public function getContentOptions(): ContentOptions
    {
        return ContentOptions::create()
            ->registerSpecialTypes(['image', 'button', 'buttons', 'file', 'video'])
            ->registerGetters([
                'image' => 'getImages',
                'button' => 'getButton',
                'buttons' => 'getButtons',
                'file' => 'getFiles',
                'video' => 'getVideos',
            ])
            ->registerResourceReferenceKeys(['image', 'file', 'video']);
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
    public function getImages(ContentValue $contentValue)
    {
        return ImageGetter::get($contentValue);
    }

    /**
     * @return mixed
     */
    public function getFiles(ContentValue $contentValue)
    {
        return FileGetter::get($contentValue);
    }

    /**
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
     * Search for smart blocks.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereLike(['name'], $search);
    }
}
