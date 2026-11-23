<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'class_id')) {
                $table->foreignId('class_id')->nullable()->after('course_id')->constrained('academic_classes')->nullOnDelete();
            }
            if (!Schema::hasColumn('schedules', 'batch_id')) {
                $table->foreignId('batch_id')->nullable()->after('class_id')->constrained('batches')->nullOnDelete();
            }
            if (!Schema::hasColumn('schedules', 'delivery_mode')) {
                $table->string('delivery_mode', 20)->default('offline')->after('title'); // 'offline', 'online'
            }
        });

        // Allow course_id to be nullable so schedules can be created directly for academic classes / batches
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'batch_id')) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            }
            if (Schema::hasColumn('schedules', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            }
            if (Schema::hasColumn('schedules', 'delivery_mode')) {
                $table->dropColumn('delivery_mode');
            }
        });
    }
};
