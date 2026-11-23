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
        Schema::create('attendance_rejection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->default('Unknown');
            $table->string('user_role')->default('guest');
            $table->unsignedBigInteger('class_id')->nullable()->index();
            $table->string('action')->default('store');
            $table->text('reason');
            $table->string('ip_address', 45)->default('127.0.0.1');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('session_date')->nullable();
            $table->timestamp('attempted_at');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_rejection_logs');
    }
};
