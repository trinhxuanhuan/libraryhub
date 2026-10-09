<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_book', function (Blueprint $table) {
            $table->dropForeign(['author_id']);

            $table->foreign('author_id')
                ->references('id')
                ->on('authors')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('author_book', function (Blueprint $table) {
            $table->dropForeign(['author_id']);

            $table->foreign('author_id')
                ->references('id')
                ->on('authors')
                ->cascadeOnDelete();
        });
    }
};
