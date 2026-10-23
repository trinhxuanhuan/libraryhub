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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // Member tạo phiếu mượn
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Trạng thái phiếu mượn
            $table->string('status', 20)
                ->default('pending');

            // Nhân viên thư viện xác nhận giao sách
            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Ngày giao sách thực tế
            $table->timestamp('borrowed_at')->nullable();

            // Hạn phải trả
            $table->timestamp('due_at')->nullable();

            // Ngày hoàn tất trả toàn bộ sách
            $table->timestamp('returned_at')->nullable();

            $table->timestamps();

            // Hỗ trợ tra cứu phiếu theo Member và trạng thái
            $table->index(['user_id', 'status']);

            // Hỗ trợ tìm phiếu theo trạng thái
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
