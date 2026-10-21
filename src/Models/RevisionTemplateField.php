<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karabin\Fabriq\Database\Factories\RevisionTemplateFieldFactory;

class RevisionTemplateField extends Model
{
    use HasFactory;

    protected $table = 'revision_template_fields';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'repeater' => 'boolean',
        'translated' => 'boolean',
    ];

    protected static function newFactory(): RevisionTemplateFieldFactory
    {
        return RevisionTemplateFieldFactory::new();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(RevisionTemplate::class, 'template_id');
    }
}
