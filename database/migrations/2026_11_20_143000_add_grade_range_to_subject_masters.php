<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subject_masters', 'min_grade')) {
            Schema::table('subject_masters', function (Blueprint $table) {
                $table->unsignedTinyInteger('min_grade')->default(1)->after('default_periods');
            });
        }
        if (!Schema::hasColumn('subject_masters', 'max_grade')) {
            Schema::table('subject_masters', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_grade')->default(12)->after('min_grade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subject_masters', 'max_grade')) {
            Schema::table('subject_masters', fn (Blueprint $table) => $table->dropColumn('max_grade'));
        }
        if (Schema::hasColumn('subject_masters', 'min_grade')) {
            Schema::table('subject_masters', fn (Blueprint $table) => $table->dropColumn('min_grade'));
        }
    }
};
