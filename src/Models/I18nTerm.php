<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class I18nTerm extends Model
{
    protected $table = 'i18n_terms';

    protected $guarded = [];

    /** @return HasOne<I18nDefinition, $this> */
    public function definition(): HasOne
    {
        return $this->hasOne(I18nDefinition::class, 'term_id');
    }
}
