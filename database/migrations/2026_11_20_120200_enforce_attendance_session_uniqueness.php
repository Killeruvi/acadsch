<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('attendance_sessions')
            ->select('class_id', 'session_date')
            ->whereNotNull('class_id')
            ->groupBy('class_id', 'session_date')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Attendance migration stopped: duplicate class/date sessions exist. Reconcile these records before applying the unique constraint.');
        }

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->unique(['class_id', 'session_date'], 'attendance_sessions_class_date_unique');
        });
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index(['student_id', 'session_id'], 'attendance_records_student_session_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('attendance_records_student_session_index');
        });
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropUnique('attendance_sessions_class_date_unique');
        });
    }
};
