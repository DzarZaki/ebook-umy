<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->words(2, true);

        return [
            'prodi_id' => null,
            'name' => Str::title($nama),
            'slug' => Str::slug($nama),
            'created_by' => null,
        ];
    }
}
