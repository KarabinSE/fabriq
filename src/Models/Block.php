<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Karabin\Fabriq\ContentGetters\ButtonGetter;
use Karabin\Fabriq\ContentGetters\ButtonsGetter;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\ContentGetters\ContentReferenceTransformer;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\ContentGetters\VideoGetter;
use Karabin\Fabriq\Database\Factories\BlockFactory;
use Karabin\Fabriq\Fabriq;
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
            'content' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public static function updateOrCreateBlock(array $block, Model $model, ?Block $parent = null): Block
    {
        $newBlock = Fabriq::getFqnModel('block')::updateOrCreate([
            'uuid' => $block['uuid'] ?? null,
        ], [
            'name' => $block['name'],
            'content' => (object) ($block['content'] ?? []),
            'locale' => $parent['locale'] ?? $block['locale'],
            'block_type_id' => $parent['block_type_id'] ?? $block['block_type_id'],

            'parent_id' => $parent->id ?? null,

            'model_type' => $model->getMorphClass(),
            'model_id' => $model->id,
        ]);

        $children = $block['children'] ?? [];

        $newChildUuids = [];
        foreach ($children as $child) {
            $newChild = self::updateOrCreateBlock($child, $model, $newBlock);
            $newChildUuids[] = $newChild->uuid;
        }

        Block::setNewOrder($newChildUuids, primaryKeyColumn: 'uuid');

        return $newBlock;
    }

    public function setContentAttribute(mixed $value): void
    {
        $content = ContentReferenceTransformer::normalize($value, self::contentOptions());
        $this->attributes['content'] = $content === null
            ? null
            : json_encode($content, JSON_THROW_ON_ERROR);
    }

    public static function contentOptions(): ContentOptions
    {
        return ContentOptions::create()
            ->registerSpecialTypes(['image', 'file', 'video', 'button', 'buttons'])
            ->registerGetters([
                'image' => ImageGetter::class,
                'file' => FileGetter::class,
                'video' => VideoGetter::class,
                'button' => ButtonGetter::class,
                'buttons' => ButtonsGetter::class,
            ])
            ->registerResourceReferenceKeys(['image', 'file', 'video']);
    }

    public function buildSortQuery(): Builder
    {
        return static::query()->where('locale', $this->locale)
            ->where('model_id', $this->model_id)
            ->where('model_type', $this->model_type)
            ->where('parent_id', $this->parent_id);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Block::class, 'parent_id')
            ->orderBy('sortindex')
            ->with('children');
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
