<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->index(['class_id', 'type', 'assigned_date'], 'daily_dpps_class_type_date_idx');
        });

        Schema::table('dpp_questions', function (Blueprint $table) {
            $table->index(['dpp_id', 'question_number'], 'dpp_questions_order_idx');
        });

        Schema::table('question_bank', function (Blueprint $table) {
            $table->index(['class_id', 'subject_id', 'created_at'], 'question_bank_class_subject_created_idx');
        });

        Schema::table('live_quizzes', function (Blueprint $table) {
            $table->index(['status', 'class_id', 'created_at'], 'live_quizzes_status_class_created_idx');
        });

        Schema::table('live_quiz_questions', function (Blueprint $table) {
            $table->index(['quiz_id', 'id'], 'live_quiz_questions_order_idx');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['quiz_id', 'score', 'created_at'], 'quiz_attempts_leaderboard_idx');
            $table->index(['student_id', 'created_at'], 'quiz_attempts_student_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_leaderboard_idx');
            $table->dropIndex('quiz_attempts_student_created_idx');
        });
        Schema::table('live_quiz_questions', function (Blueprint $table) {
            $table->dropIndex('live_quiz_questions_order_idx');
        });
        Schema::table('live_quizzes', function (Blueprint $table) {
            $table->dropIndex('live_quizzes_status_class_created_idx');
        });
        Schema::table('question_bank', function (Blueprint $table) {
            $table->dropIndex('question_bank_class_subject_created_idx');
        });
        Schema::table('dpp_questions', function (Blueprint $table) {
            $table->dropIndex('dpp_questions_order_idx');
        });
        Schema::table('daily_dpps', function (Blueprint $table) {
            $table->dropIndex('daily_dpps_class_type_date_idx');
        });
    }
};
