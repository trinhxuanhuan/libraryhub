<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('loans_status_index');

            $table->index(
                ['status', 'due_at'],
                'loans_status_due_at_index'
            );

            $table->index(
                'borrowed_at',
                'loans_borrowed_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('loans_status_due_at_index');
            $table->dropIndex('loans_borrowed_at_index');

            $table->index('status', 'loans_status_index');
        });
    }
};
