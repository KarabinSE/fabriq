<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Karabin\Fabriq\Database\Factories\ContentTemplateFactory;

class ContentTemplate extends Model
{
    use HasFactory;

    protected $table = 'content_templates';

    protected $guarded = [];

    protected static function newFactory(): ContentTemplateFactory
    {
        return ContentTemplateFactory::new();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ContentTemplateField::class, 'template_id')->orderBy('sort_index');
    }
}
