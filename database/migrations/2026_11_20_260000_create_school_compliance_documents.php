<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_compliance_documents', function (Blueprint $table) {
            $table->id();
            $table->string('category', 40);
            $table->string('title', 180);
            $table->string('document_number', 120)->nullable();
            $table->string('issuing_authority', 180)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->string('file_path', 500)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['category', 'expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_compliance_documents');
    }
};
