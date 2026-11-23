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
        Schema::table('homework_tasks', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('due_date');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_type', 50)->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_type');
        });

        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->text('student_notes')->nullable()->change();
            $table->string('attachment_path')->nullable()->after('student_notes');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_type', 50)->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homework_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'attachment_path',
                'attachment_name',
                'attachment_type',
                'attachment_size',
            ]);
        });

        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'attachment_path',
                'attachment_name',
                'attachment_type',
                'attachment_size',
            ]);
        });
    }
};
