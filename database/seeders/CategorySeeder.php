<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Lập trình',
            'Kinh tế',
            'Văn học',
            'Khoa học',
            'Lịch sử',
            'Kỹ năng sống',
            'Thiếu nhi',
            'Ngoại ngữ',
        ];

        foreach ($categories as $name) {
            $exists = DB::table('categories')
                ->where('name', $name)
                ->exists();

            if (! $exists) {
                DB::table('categories')->insert([
                    'name' => $name,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
