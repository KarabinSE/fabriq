<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class I18nDefinition extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    protected $table = 'i18n_definitions';

    protected $guarded = [];

    public array $translatable = ['content'];
}
