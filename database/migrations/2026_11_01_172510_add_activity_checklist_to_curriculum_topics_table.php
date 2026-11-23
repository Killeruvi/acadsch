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
        Schema::table('curriculum_topics', function (Blueprint $table) {
            $table->json('activity_checklist')->nullable()->after('subtopics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curriculum_topics', function (Blueprint $table) {
            $table->dropColumn('activity_checklist');
        });
    }
};
