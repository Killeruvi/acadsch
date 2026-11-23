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
        if (!Schema::hasTable('payment_webhook_logs')) {
            Schema::create('payment_webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('gateway', 50)->default('ownpay');
                $table->string('event', 100)->nullable();
                $table->string('transaction_reference', 150)->nullable()->index();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('status', 50)->default('pending');
                $table->boolean('signature_verified')->default(false);
                $table->string('ip_address', 45)->nullable();
                $table->json('headers')->nullable();
                $table->json('payload')->nullable();
                $table->unsignedSmallInteger('response_status')->nullable();
                $table->string('response_message', 500)->nullable();
                $table->timestamps();

                $table->foreign('invoice_id')->references('id')->on('fee_invoices')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
