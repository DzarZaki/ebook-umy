<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->unique()->sentence(4);

        return [
            'title' => $judul,
            'slug' => Str::slug($judul),
            'author' => fake()->name(),
            'description' => fake()->paragraph(),
            'prodi_id' => null,
            'category_id' => null,
            'uploaded_by' => User::factory()->admin(),
            'file_path' => 'books/contoh.pdf',
            'file_size' => 1048576,
            'page_count' => 50,
            'access_mode' => Book::AKSES_BACA_SAJA,
            'watermark_enabled' => true,
            'is_published' => true,
        ];
    }
}
