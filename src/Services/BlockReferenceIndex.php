<?php

namespace Karabin\Fabriq\Services;

use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\Models\Block;

class BlockReferenceIndex
{
    public function sync(Block $block): void
    {
        $references = array_map(
            fn (array $reference) => ['block_id' => $block->getKey(), ...$reference],
            app(ContentReferenceExtractor::class)->extract($block->content),
        );

        DB::table('block_references')->where('block_id', $block->getKey())->delete();
        if ($references !== []) {
            DB::table('block_references')->insert(array_values($references));
        }
    }
}
