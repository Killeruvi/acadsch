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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('General');
            $table->unsignedInteger('credits')->default(3);
            $table->unsignedInteger('capacity')->default(30);
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active')->index(); // 'active', 'draft', 'archived'
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
