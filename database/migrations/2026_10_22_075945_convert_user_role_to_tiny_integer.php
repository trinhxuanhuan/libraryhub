<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kiểm tra dữ liệu Role cũ.
        if (DB::table('users')
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhereNotIn('role', [
                        'admin',
                        'librarian',
                        'member',
                    ]);
            })
            ->exists()) {
            throw new RuntimeException(
                'Có role không hợp lệ trong bảng users.'
            );
        }

        // Chỉ tạo cột khi chưa tồn tại.
        if (! Schema::hasColumn('users', 'role_new')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('role_new')
                    ->default(3);
            });
        }

        // Định nghĩa các giá trị cần chuyển đổi.
        $roles = [
            'admin' => 1,
            'librarian' => 2,
            'member' => 3,
        ];

        // Cập nhật dữ liệu Role.
        foreach ($roles as $name => $value) {
            DB::table('users')
                ->where('role', $name)
                ->update(['role_new' => $value]);
        }

        // Kiểm tra kết quả sau khi cập nhật.
        foreach ($roles as $name => $value) {
            $hasInvalidConversion = DB::table('users')
                ->where('role', $name)
                ->where(function ($query) use ($value) {
                    $query->whereNull('role_new')
                        ->orWhere('role_new', '!=', $value);
                })
                ->exists();

            if ($hasInvalidConversion) {
                throw new RuntimeException(
                    "Không thể chuyển đổi role {$name}."
                );
            }
        }

        // Xóa cột Role cũ sau khi kiểm tra thành công.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Đổi tên cột mới thành Role.
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });
    }

    public function down(): void
    {
        if (DB::table('users')
            ->whereNotIn('role', [1, 2, 3])
            ->exists()) {
            throw new RuntimeException(
                'Có role không hợp lệ để rollback.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role_old', 20)
                ->default('member');
        });

        $roles = [
            1 => 'admin',
            2 => 'librarian',
            3 => 'member',
        ];
        // Chuyển đổi dữ liệu từ String sang Integer
        foreach ($roles as $name => $value) {
            DB::table('users')
                ->where('role', $name)
                ->update(['role_new' => $value]);
        }

        foreach ($roles as $value => $name) {
            DB::table('users')
                ->where('role', $value)
                ->update(['role_old' => $name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_old', 'role');
        });
    }
};
