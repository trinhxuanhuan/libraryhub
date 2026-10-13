<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $totalCopies = fake()->numberBetween(1, 20);

        return [
            'isbn' => fake()->unique()->isbn13(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'publisher' => fake()->company(),
            'published_year' => fake()->numberBetween(1950, (int) date('Y')),
            'cover_path' => null,
            'total_copies' => $totalCopies,
            'available_copies' => fake()->numberBetween(0, $totalCopies),
        ];
    }
}
