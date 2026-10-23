<?php

namespace Karabin\Fabriq\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Karabin\Fabriq\Models\ContentTemplateField;

class ContentTemplateFieldFactory extends Factory
{
    protected $model = ContentTemplateField::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->randomElement([
                'page_header',
                'page_title',
                'page_thing',
                'page_aroo',
                'widget_title',
                'widget_body',
                'page_main_content',
            ]),
            'name' => $this->faker->domainName,
            'type' => $this->faker->randomElement(['text', 'html']),
        ];
    }
}
