<?php

namespace Karabin\Fabriq\Models;

use Karabin\Fabriq\ContentGetters\ButtonGetter;
use Karabin\Fabriq\ContentGetters\ButtonsGetter;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\ContentGetters\VideoGetter;
use Karabin\Fabriq\Database\Factories\SmartBlockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Karabin\TranslatableRevisions\Models\RevisionMeta;
use Karabin\TranslatableRevisions\Traits\HasTranslatedRevisions;
use Karabin\TranslatableRevisions\Traits\RevisionOptions;

class SmartBlock extends Model
{
    use HasFactory, HasTranslatedRevisions;

    public const RELATIONSHIPS = [];

    protected static function newFactory(): SmartBlockFactory
    {
        return SmartBlockFactory::new();
    }

    /**
     * Get the options for the revisions.
     */
    public function getRevisionOptions(): RevisionOptions
    {
        return RevisionOptions::create()
            ->registerSpecialTypes(['image', 'video', 'file', 'buttons', 'button'])
            ->registerGetters([
                'image' => 'getImages',
                'repeater' => 'getRepeater',
                'button' => 'getButton',
                'buttons' => 'getButtons',
                'file' => 'getFiles',
                'video' => 'getVideos',
            ])
            ->registerDefaultTemplate('smart_block')
            ->registerCacheKeysToFlush(['fabriq_pages', 'fabriq_smart_blocks']);
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
    public function getImages(RevisionMeta $meta)
    {
        return ImageGetter::get($meta, $this->isPublishing);
    }

    /**
     * @return mixed
     */
    public function getFiles(RevisionMeta $meta)
    {
        return FileGetter::get($meta, $this->isPublishing);
    }

    /**
     * @return mixed
     */
    public function getVideos(RevisionMeta $meta)
    {
        return VideoGetter::get($meta, $this->isPublishing);
    }

    /**
     * Getter for button.
     *
     * @return mixed
     */
    public function getButton(RevisionMeta $meta)
    {
        return ButtonGetter::get($meta, $this->isPublishing);
    }

    /**
     * Getter for buttons.
     *
     * @return mixed
     */
    public function getButtons(RevisionMeta $meta)
    {
        return ButtonsGetter::get($meta, $this->isPublishing);
    }

    /**
     * Search for smart blocks.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereLike(['name'], $search);
    }
}
