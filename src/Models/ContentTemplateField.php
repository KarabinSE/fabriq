<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karabin\Fabriq\Database\Factories\ContentTemplateFieldFactory;

class ContentTemplateField extends Model
{
    use HasFactory;

    protected $table = 'content_template_fields';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'repeater' => 'boolean',
        'translated' => 'boolean',
    ];

    protected static function newFactory(): ContentTemplateFieldFactory
    {
        return ContentTemplateFieldFactory::new();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContentTemplate::class, 'template_id');
    }
}
