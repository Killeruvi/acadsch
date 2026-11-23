<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_sessions', 'period_number')) {
                $table->unsignedSmallInteger('period_number')->nullable()->after('session_date');
            }
            if (! Schema::hasColumn('attendance_sessions', 'session_type')) {
                $table->string('session_type', 40)->nullable()->after('period_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'session_type')) {
                $table->dropColumn('session_type');
            }
            if (Schema::hasColumn('attendance_sessions', 'period_number')) {
                $table->dropColumn('period_number');
            }
        });
    }
};
