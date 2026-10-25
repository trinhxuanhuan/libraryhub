
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $table->string('return_condition', 20)->nullable();

            $table->timestamp('resolved_at')->nullable();

            $table->unsignedInteger('overdue_fine_amount')
                ->default(0);

            $table->unsignedInteger('compensation_amount')
                ->default(0);

            $table->timestamp('fine_paid_at')->nullable();

            $table->text('return_note')->nullable();
        });

        // Giữ nhất quán dữ liệu của những sách đã trả trước đây.
        DB::table('loan_items')
            ->whereNotNull('returned_at')
            ->update([
                'resolved_at' => DB::raw('returned_at'),
                'return_condition' => 'normal',
            ]);
    }

    public function down(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropColumn([
                'return_condition',
                'resolved_at',
                'overdue_fine_amount',
                'compensation_amount',
                'fine_paid_at',
                'return_note',
            ]);
        });
    }
};
