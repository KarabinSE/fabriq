<?php

namespace Karabin\Fabriq\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Karabin\Fabriq\Models\RevisionTemplate;

class RevisionTemplateFactory extends Factory
{
    protected $model = RevisionTemplate::class;

    public function definition(): array
    {
        $name = $this->faker->name;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
