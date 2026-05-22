<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Karabin\Fabriq\Database\Factories\BlockTypeFactory;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BlockType extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected $with = ['media'];

    /**
     * Morph class.
     *
     * @var string
     */
    public $morphClass = 'block_type';

    /**
     * Create a new factory.
     */
    protected static function newFactory(): BlockTypeFactory
    {
        return BlockTypeFactory::new();
    }

    protected $casts = [
        'has_children' => 'boolean',
        'options' => 'array',
    ];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit(Fit::Max, 480, 320)
            ->format(config('fabriq.enable_webp') ? 'webp' : 'jpg')
            ->quality(95);

        $this->addMediaConversion('preview')
            ->format(config('fabriq.enable_webp') ? 'webp' : 'jpg')
            ->fit(Fit::Max, 1200)
            ->quality(85);
    }
}
