<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('isbn', 20)->unique();
            $table->string('title', 255)->index();

            $table->text('description')->nullable();
            $table->string('publisher', 150)->nullable();
            $table->unsignedSmallInteger('published_year');
            $table->string('cover_path')->nullable();

            $table->unsignedInteger('total_copies');
            $table->unsignedInteger('available_copies');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
