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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('streak_count')->default(1);
            $table->date('last_active_date')->nullable();
            $table->unsignedInteger('xp_points')->default(150);
            $table->string('academic_tier', 50)->default('Distinguished Scholar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['streak_count', 'last_active_date', 'xp_points', 'academic_tier']);
        });
    }
};
