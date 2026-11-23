<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_admission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->string('original_filename', 255);
            $table->string('file_path', 500)->unique();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_admission_documents');
    }
};
