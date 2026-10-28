<?php

namespace Karabin\Fabriq\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\Page;

class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'template_id' => ContentTemplate::factory()->create()->id,
        ];
    }
}
