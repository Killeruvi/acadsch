<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('student_name', 120);
            $table->string('applying_for_class', 40);
            $table->date('date_of_birth')->nullable();
            $table->string('guardian_name', 120);
            $table->string('guardian_phone', 25)->index();
            $table->string('guardian_email', 150)->nullable()->index();
            $table->string('preferred_contact_time', 80)->nullable();
            $table->string('previous_school', 150)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_enquiries');
    }
};
