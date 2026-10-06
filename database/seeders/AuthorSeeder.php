<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $authors = [
            'Nam Cao',
            'Tô Hoài',
            'Ngô Tất Tố',
            'Nguyễn Nhật Ánh',
            'Robert C. Martin',
            'Martin Fowler',
            'Joshua Bloch',
            'Paulo Coelho',
            'Dale Carnegie',
            'Stephen Hawking',
        ];

        foreach ($authors as $name) {
            $exists = DB::table('authors')
                ->where('name', $name)
                ->exists();

            if (! $exists) {
                DB::table('authors')->insert([
                    'name' => $name,
                    'bio' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
