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
        Schema::create('virtual_classes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->string('host')->default('custom'); // zoom, google_meet, jitsi, custom, in_app
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('duration')->default(60); // in minutes
            $table->string('meeting_id')->nullable();
            $table->string('password')->nullable();
            $table->text('join_url')->nullable();
            $table->text('recording_url')->nullable();
            $table->string('status')->default('upcoming'); // upcoming, live, completed, cancelled
            $table->integer('capacity')->default(100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_classes');
    }
};
