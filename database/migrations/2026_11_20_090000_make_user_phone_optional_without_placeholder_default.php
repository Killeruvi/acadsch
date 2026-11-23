<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // A missing phone number is valid profile data. Do not manufacture
            // a shared placeholder number for users who did not provide one.
            $table->string('phone')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // This rollback intentionally does not fabricate replacement phone
        // numbers. If null phone values exist, resolve them before rolling back.
        if (\Illuminate\Support\Facades\DB::table('users')->whereNull('phone')->exists()) {
            throw new RuntimeException(
                'Cannot make users.phone required while null values exist. Resolve missing phone numbers before rollback.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->default('+91 98200 00000')->change();
        });
    }
};
