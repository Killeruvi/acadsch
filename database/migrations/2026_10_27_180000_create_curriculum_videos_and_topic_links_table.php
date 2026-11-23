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
        // 1. Structured Video Lectures for Curriculum Topics (Chapters)
        Schema::create('curriculum_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('curriculum_topics')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->string('title');
            $table->string('video_url');
            $table->string('video_provider', 30)->default('youtube'); // youtube, vimeo, direct, mp4
            $table->unsignedInteger('duration_seconds')->default(1800); // 30 minutes default
            $table->unsignedSmallInteger('order_index')->default(1);
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('lecture_notes_url')->nullable();
            $table->boolean('is_free_preview')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // 2. Student Video Watching Progress
        Schema::create('user_video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('curriculum_videos')->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
        });

        // 3. Link Homework Tasks directly to Curriculum Topics (Chapters)
        if (Schema::hasTable('homework_tasks') && ! Schema::hasColumn('homework_tasks', 'topic_id')) {
            Schema::table('homework_tasks', function (Blueprint $table) {
                $table->foreignId('topic_id')->nullable()->after('subject_id')->constrained('curriculum_topics')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('homework_tasks') && Schema::hasColumn('homework_tasks', 'topic_id')) {
            Schema::table('homework_tasks', function (Blueprint $table) {
                $table->dropConstrainedForeignId('topic_id');
            });
        }

        Schema::dropIfExists('user_video_progress');
        Schema::dropIfExists('curriculum_videos');
    }
};
