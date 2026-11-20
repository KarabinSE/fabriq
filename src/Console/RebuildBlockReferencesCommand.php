<?php

namespace Karabin\Fabriq\Console;

use Illuminate\Console\Command;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\I18nDefinition;
use Karabin\Fabriq\Services\BlockReferenceIndex;
use Karabin\Fabriq\Services\DefinitionReferenceIndex;

class RebuildBlockReferencesCommand extends Command
{
    protected $signature = 'fabriq:rebuild-block-references';

    protected $description = 'Rebuild block and content resource references';

    public function handle(BlockReferenceIndex $index, DefinitionReferenceIndex $definitionIndex): int
    {
        $count = 0;
        $blockClass = Fabriq::getFqnModel('block');

        $blockClass::query()->chunkById(100, function ($blocks) use ($index, &$count) {
            foreach ($blocks as $block) {
                $index->sync($block);
                $count++;
            }
        });

        $definitions = 0;
        I18nDefinition::query()->whereNotNull('field_key')->with('model')->chunkById(100, function ($items) use ($definitionIndex, &$definitions) {
            foreach ($items as $definition) {
                $definitionIndex->sync($definition);
                $definitions++;
            }
        });

        $this->info("Indexed {$count} blocks and {$definitions} content definitions.");

        return self::SUCCESS;
    }
}
