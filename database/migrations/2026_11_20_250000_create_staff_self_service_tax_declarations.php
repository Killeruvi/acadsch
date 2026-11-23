<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_tax_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('financial_year', 7);
            $table->enum('tax_regime', ['old', 'new']);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->text('declaration_note')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'financial_year']);
            $table->index(['financial_year', 'status']);
        });
        Schema::create('staff_tax_declaration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('staff_tax_declarations')->cascadeOnDelete();
            $table->string('section_code', 30);
            $table->string('description', 180);
            $table->decimal('declared_amount', 14, 2);
            $table->timestamps();
            $table->index(['declaration_id', 'section_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_tax_declaration_items');
        Schema::dropIfExists('staff_tax_declarations');
    }
};
