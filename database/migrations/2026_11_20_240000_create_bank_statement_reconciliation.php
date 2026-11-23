<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255);
            $table->string('account_label', 80);
            $table->char('file_sha256', 64)->unique();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->string('status', 20)->default('imported');
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('staff_bank_statement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('staff_bank_statement_imports')->restrictOnDelete();
            $table->date('transaction_date');
            $table->string('description', 500)->nullable();
            $table->string('bank_reference', 160)->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->char('transaction_fingerprint', 64)->unique();
            $table->string('match_type', 20)->nullable();
            $table->unsignedBigInteger('matched_entity_id')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();
            $table->index(['transaction_date', 'debit']);
            $table->unique(['match_type', 'matched_entity_id'], 'staff_bank_match_entity_unique');
            $table->index(['match_type', 'matched_entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_bank_statement_transactions');
        Schema::dropIfExists('staff_bank_statement_imports');
    }
};
