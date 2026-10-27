<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_items', function (Blueprint $table) {
            $table->id();

            // Chi tiết thuộc phiếu mượn nào
            $table->foreignId('loan_id')
                ->constrained('loans')
                ->restrictOnDelete();

            // Đầu sách được yêu cầu mượn
            $table->foreignId('book_id')
                ->constrained('books')
                ->restrictOnDelete();

            // Bản sách vật lý được giao
            $table->foreignId('book_copy_id')
                ->nullable()
                ->constrained('book_copies')
                ->restrictOnDelete();

            // Thời điểm trả bản sách này
            $table->timestamp('returned_at')->nullable();

            $table->timestamps();

            // Mỗi đầu sách chỉ xuất hiện một lần trong một phiếu
            $table->unique(['loan_id', 'book_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_items');
    }
};
