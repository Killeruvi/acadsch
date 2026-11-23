<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Backfill any existing users without a phone number
        DB::table('users')->whereNull('phone')->orWhere('phone', '')->update([
            'phone' => '+91 98200 00000',
        ]);

        // 2. Make phone permanent (NOT NULL with a default)
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->default('+91 98200 00000')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });
    }
};
