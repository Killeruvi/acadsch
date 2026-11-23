<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_withdrawal_records')) {
            return;
        }

        Schema::create('student_withdrawal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date');
            $table->text('reason');
            $table->string('source', 30)->default('admin_portal');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'effective_date']);
            $table->index(['withdrawn_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_withdrawal_records');
    }
};
