<?php

namespace Karabin\Fabriq\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Karabin\Fabriq\Models\GlobalSection;

class AddGlobalFieldCommand extends Command
{
    protected $signature = 'fabriq:add-global-field
        {handle?} {name?} {key?} {type?}
        {--group= : Tab name (defaults to Default)}
        {--translated= : Whether the field is translated: 1 or 0}
        {--order= : Field order}';

    protected $description = 'Add a field to a global section';

    public function handle(): int
    {
        $interactive = $this->input->isInteractive();
        $handle = $this->argument('handle') ?? ($interactive ? $this->ask('Section handle') : null);
        $name = $this->argument('name') ?? ($interactive ? $this->ask('Field name') : null);
        $key = $this->argument('key') ?? ($interactive ? $this->ask('Field key') : null);
        $type = $this->argument('type') ?? ($interactive ? $this->choice('Field type', ['text', 'textarea', 'image']) : null);
        $group = $this->option('group') ?? ($interactive ? $this->ask('Tab name', 'Default') : 'Default');
        $translated = $this->option('translated') ?? ($interactive ? ($this->confirm('Translate this field?') ? '1' : '0') : '0');
        $order = $this->option('order');

        $validator = Validator::make(compact('handle', 'name', 'key', 'type', 'group', 'translated', 'order'), [
            'handle' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
            'type' => ['required', Rule::in(['text', 'textarea', 'image'])],
            'group' => ['required', 'string', 'max:255'],
            'translated' => ['required', Rule::in(['0', '1'])],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        return DB::transaction(function () use ($handle, $name, $key, $type, $group, $translated, $order) {
            $section = GlobalSection::where('handle', $handle)->lockForUpdate()->first();
            if (! $section) {
                $this->error('Global section not found.');

                return self::FAILURE;
            }

            $attributes = [
                'name' => $name,
                'type' => $type,
                'group' => $group,
                'translated' => $translated === '1',
                'repeater' => false,
            ];
            $field = $section->template->fields()->where('key', $key)->first();
            if ($field) {
                foreach ($attributes as $attribute => $value) {
                    if ($field->{$attribute} !== $value) {
                        $this->error('A field with this key already exists with a different definition.');

                        return self::FAILURE;
                    }
                }
                if ($order !== null && (int) $field->sort_index !== (int) $order) {
                    $this->error('A field with this key already exists with a different definition.');

                    return self::FAILURE;
                }
                $this->info('Global field already exists.');

                return self::SUCCESS;
            }

            $section->template->fields()->create([
                'key' => $key,
                ...$attributes,
                'sort_index' => $order === null ? ((int) $section->template->fields()->max('sort_index') + 10) : (int) $order,
            ]);
            $this->info('Global field created.');

            return self::SUCCESS;
        });
    }
}
