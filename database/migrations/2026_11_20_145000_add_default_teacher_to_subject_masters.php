<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subject_masters', 'default_teacher_id')) {
            Schema::table('subject_masters', function (Blueprint $table) {
                $table->foreignId('default_teacher_id')->nullable()->after('max_grade')
                    ->constrained('users')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subject_masters', 'default_teacher_id')) {
            Schema::table('subject_masters', function (Blueprint $table) {
                $table->dropForeign(['default_teacher_id']);
                $table->dropColumn('default_teacher_id');
            });
        }
    }
};
