<?php

namespace Karabin\Fabriq\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\GlobalSection;

class CreateGlobalSectionCommand extends Command
{
    protected $signature = 'fabriq:create-global {name?} {handle?}';

    protected $description = 'Create a global section';

    public function handle(): int
    {
        $name = $this->argument('name') ?? ($this->input->isInteractive() ? $this->ask('Section name') : null);
        $handle = $this->argument('handle') ?? ($this->input->isInteractive() ? $this->ask('Section handle') : null);

        $validator = Validator::make(compact('name', 'handle'), [
            'name' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:248', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $existing = GlobalSection::where('handle', $handle)->first();
        if ($existing) {
            if ($existing->name !== $name) {
                $this->error('A section with this handle already exists with a different name.');

                return self::FAILURE;
            }

            $this->info('Global section already exists.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($name, $handle) {
            $template = ContentTemplate::create([
                'name' => $name,
                'slug' => 'global-'.$handle,
                'type' => 'global',
            ]);
            GlobalSection::create(['name' => $name, 'handle' => $handle, 'template_id' => $template->id]);
        });

        $this->info('Global section created.');

        return self::SUCCESS;
    }
}
