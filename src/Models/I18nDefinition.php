<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class I18nDefinition extends Model
{
    use HasTranslations;

    protected $table = 'i18n_definitions';

    protected $guarded = [];

    public array $translatable = ['content'];
}
