<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Karabin\Fabriq\Database\Factories\RevisionTemplateFactory;

class RevisionTemplate extends Model
{
    use HasFactory;

    protected $table = 'revision_templates';

    protected $guarded = [];

    protected static function newFactory(): RevisionTemplateFactory
    {
        return RevisionTemplateFactory::new();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(RevisionTemplateField::class, 'template_id')->orderBy('sort_index');
    }
}
