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
        Schema::table('bloom_assignments', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('activity_prompt');
            $table->text('rubric_guide')->nullable()->after('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bloom_assignments', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'rubric_guide']);
        });
    }
};
