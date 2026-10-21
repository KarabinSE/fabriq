<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Karabin\Fabriq\Database\Factories\BlockFactory;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class Block extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    /**
     * Create a new factory.
     */
    protected static function newFactory(): BlockFactory
    {
        return BlockFactory::new();
    }

    public array $sortable = [
        'order_column_name' => 'sortindex',
        'sort_when_creating' => true,
    ];

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Block $block) {
            $block->uuid = Str::uuid();
        });
    }

    public function casts(): array
    {
        return [
            'content' => 'object',
        ];
    }

    public function buildSortQuery(): Builder
    {
        return static::query()->where('locale', $this->locale);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Block::class, 'parent_id');
    }

    public function blockType(): BelongsTo
    {
        return $this->belongsTo(BlockType::class);
    }

    public static function buildQuery(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedIncludes('blockType', 'children')
            ->allowedFilters(
                AllowedFilter::exact('locale'),
            );
    }
}
