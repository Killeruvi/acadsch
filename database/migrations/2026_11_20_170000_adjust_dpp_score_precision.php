<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Negative marking supports fractional deductions, so the stored score must retain decimals.
        Schema::table('dpp_submissions', function (Blueprint $table) {
            $table->decimal('score', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('dpp_submissions', function (Blueprint $table) {
            $table->integer('score')->default(0)->change();
        });
    }
};
