<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3, false),
            'description' => fake()->paragraph(),
            'ingredients' => fake()->randomElements([
                '1 cup flour',
                '2 large eggs',
                '1/2 cup sugar',
                '1 tsp vanilla extract',
                '2 cups whole milk',
                '1 tbsp unsalted butter',
                '1 tsp salt',
                '1/2 tsp baking powder',
                '2 cloves garlic',
                '1 medium onion',
            ], fake()->numberBetween(3, 6)),
            'instructions' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
        ];
    }
}
