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
        if (!Schema::hasTable('batches')) {
            Schema::create('batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('academic_year')->default('2026-2027');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('users', 'batch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('batch_id')->nullable()->after('role')->constrained('batches')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'batch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            });
        }

        Schema::dropIfExists('batches');
    }
};
