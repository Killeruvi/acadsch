<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_exit_certificates')) {
            return;
        }
        Schema::create('student_exit_certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('withdrawal_record_id')->unique()->constrained('student_withdrawal_records')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('certificate_number', 40)->unique();
            $table->date('date_of_issue');
            $table->string('last_class', 100)->nullable();
            $table->string('last_academic_year', 20)->nullable();
            $table->string('conduct', 100)->nullable();
            $table->string('next_institution', 190)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'date_of_issue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_exit_certificates');
    }
};
