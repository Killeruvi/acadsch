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
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_reference')->unique();
            $table->string('payment_method')->default('Card'); // Card, UPI / NetBanking, Cash, Bank Transfer
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at')->useCurrent();
            $table->string('status')->default('successful')->index(); // successful, pending, failed
            $table->string('receipt_number')->unique();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
