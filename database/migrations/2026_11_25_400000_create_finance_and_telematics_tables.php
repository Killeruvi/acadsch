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
        // 1. Online Payment Orders & Webhook Tracking
        if (! Schema::hasTable('payment_gateway_orders')) {
            Schema::create('payment_gateway_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_reference', 64)->unique();
                $table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->enum('gateway', ['razorpay', 'stripe', 'upi_intent', 'manual'])->default('razorpay');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('INR');
                $table->enum('status', ['created', 'attempted', 'paid', 'failed', 'refunded'])->default('created')->index();
                $table->string('gateway_order_id', 100)->nullable()->index();
                $table->string('gateway_payment_id', 100)->nullable()->index();
                $table->string('gateway_signature', 255)->nullable();
                $table->string('receipt_number', 60)->nullable();
                $table->timestamp('webhook_received_at')->nullable();
                $table->timestamps();

                $table->index(['invoice_id', 'status']);
            });
        }

        // 2. Transport Vehicle GPS Telemetry Stream
        if (! Schema::hasTable('transport_vehicle_telemetry')) {
            Schema::create('transport_vehicle_telemetry', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('transport_vehicles')->cascadeOnDelete();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('speed_kmh', 5, 2)->default(0);
                $table->unsignedSmallInteger('heading_degrees')->nullable();
                $table->boolean('ignition')->default(true);
                $table->unsignedTinyInteger('battery_percentage')->nullable();
                $table->dateTime('recorded_at');
                $table->timestamps();

                $table->index(['vehicle_id', 'recorded_at']);
            });
        }

        // 3. Add live telemetry cache columns to transport_vehicles
        Schema::table('transport_vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('transport_vehicles', 'current_latitude')) {
                $table->decimal('current_latitude', 10, 7)->nullable()->after('status');
            }
            if (! Schema::hasColumn('transport_vehicles', 'current_longitude')) {
                $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
            }
            if (! Schema::hasColumn('transport_vehicles', 'last_telemetry_at')) {
                $table->dateTime('last_telemetry_at')->nullable()->after('current_longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_vehicle_telemetry');
        Schema::dropIfExists('payment_gateway_orders');

        Schema::table('transport_vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('transport_vehicles', 'current_latitude')) {
                $table->dropColumn(['current_latitude', 'current_longitude', 'last_telemetry_at']);
            }
        });
    }
};
