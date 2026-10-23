<?php

namespace Karabin\Fabriq\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Karabin\Fabriq\Models\Block;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Block>
     */
    protected $model = Block::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
