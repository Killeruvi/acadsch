<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_holidays', function (Blueprint $table) {
            $table->index('academic_year', 'academic_holidays_academic_year_idx');
            $table->index(['is_instructional_off', 'holiday_date', 'end_date'], 'academic_holidays_instructional_range_idx');
        });
    }

    public function down(): void
    {
        Schema::table('academic_holidays', function (Blueprint $table) {
            $table->dropIndex('academic_holidays_academic_year_idx');
            $table->dropIndex('academic_holidays_instructional_range_idx');
        });
    }
};
