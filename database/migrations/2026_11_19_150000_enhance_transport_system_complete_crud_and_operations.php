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
        // 1. Enhance transport_routes
        Schema::table('transport_routes', function (Blueprint $table) {
            if (!Schema::hasColumn('transport_routes', 'distance_km')) {
                $table->decimal('distance_km', 6, 2)->nullable()->after('end_point');
            }
            if (!Schema::hasColumn('transport_routes', 'morning_timing')) {
                $table->string('morning_timing', 50)->nullable()->after('distance_km');
            }
            if (!Schema::hasColumn('transport_routes', 'evening_timing')) {
                $table->string('evening_timing', 50)->nullable()->after('morning_timing');
            }
            if (!Schema::hasColumn('transport_routes', 'status')) {
                $table->string('status', 20)->default('active')->after('evening_timing');
            }
        });

        // 2. Enhance transport_vehicles
        Schema::table('transport_vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('transport_vehicles', 'vehicle_type')) {
                $table->string('vehicle_type', 50)->default('Bus')->after('vehicle_number');
            }
            if (!Schema::hasColumn('transport_vehicles', 'capacity')) {
                $table->integer('capacity')->default(40)->after('vehicle_type');
            }
            if (!Schema::hasColumn('transport_vehicles', 'insurance_renewal_date')) {
                $table->date('insurance_renewal_date')->nullable()->after('year_made');
            }
            if (!Schema::hasColumn('transport_vehicles', 'fitness_certificate_expiry')) {
                $table->date('fitness_certificate_expiry')->nullable()->after('insurance_renewal_date');
            }
            if (!Schema::hasColumn('transport_vehicles', 'status')) {
                $table->string('status', 30)->default('active')->after('fitness_certificate_expiry'); // active, inactive, maintenance
            }
            if (!Schema::hasColumn('transport_vehicles', 'driver_id')) {
                $table->unsignedBigInteger('driver_id')->nullable()->after('driver_phone');
            }
        });

        // 3. Transport Staff (Drivers / Attendants / Conductors)
        if (!Schema::hasTable('transport_staff')) {
            Schema::create('transport_staff', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('role', 30)->default('driver'); // driver, attendant, conductor
                $table->string('contact_number', 30);
                $table->string('license_number', 100)->nullable();
                $table->date('license_expiry')->nullable();
                $table->unsignedBigInteger('assigned_vehicle_id')->nullable();
                $table->unsignedBigInteger('assigned_route_id')->nullable();
                $table->string('status', 30)->default('active'); // active, on_leave, inactive
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        // 4. Transport Route Stops
        if (!Schema::hasTable('transport_route_stops')) {
            Schema::create('transport_route_stops', function (Blueprint $table) {
                $table->id();
                $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->string('stop_name');
                $table->integer('stop_sequence')->default(1);
                $table->string('pickup_time', 30)->nullable();
                $table->string('drop_time', 30)->nullable();
                $table->decimal('fare', 10, 2)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('landmark')->nullable();
                $table->timestamps();
            });
        }

        // 5. Student Transport Assignment
        if (!Schema::hasTable('student_transport_assignments')) {
            Schema::create('student_transport_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->foreignId('stop_id')->nullable()->constrained('transport_route_stops')->nullOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
                $table->string('status', 30)->default('active'); // active, opted_out, suspended
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('monthly_fare', 10, 2)->default(0.00);
                $table->decimal('waiver_percentage', 5, 2)->default(0.00);
                $table->decimal('discount_amount', 10, 2)->default(0.00);
                $table->string('opt_out_reason')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // 6. Transport Trips (Daily Fleet Dispatch)
        if (!Schema::hasTable('transport_trips')) {
            Schema::create('transport_trips', function (Blueprint $table) {
                $table->id();
                $table->date('trip_date');
                $table->string('trip_type', 20)->default('pickup'); // pickup, drop
                $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
                $table->foreignId('driver_id')->nullable()->constrained('transport_staff')->nullOnDelete();
                $table->string('status', 30)->default('scheduled'); // scheduled, in_transit, completed, cancelled
                $table->dateTime('started_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 7. Transport Trip Passengers (Boarding Confirmation)
        if (!Schema::hasTable('transport_trip_passengers')) {
            Schema::create('transport_trip_passengers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained('transport_trips')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('stop_id')->nullable()->constrained('transport_route_stops')->nullOnDelete();
                $table->string('status', 30)->default('pending'); // pending, boarded, dropped, absent
                $table->dateTime('confirmed_at')->nullable();
                $table->timestamps();
            });
        }

        // 8. Transport Audit Logs
        if (!Schema::hasTable('transport_audit_logs')) {
            Schema::create('transport_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50); // created, updated, deleted, reassigned, trip_started, trip_ended, status_changed, opted_out, bulk_assigned, fee_generated
                $table->string('entity_type', 50); // vehicle, route, stop, staff, student_assignment, trip, fee
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->text('details')->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_audit_logs');
        Schema::dropIfExists('transport_trip_passengers');
        Schema::dropIfExists('transport_trips');
        Schema::dropIfExists('student_transport_assignments');
        Schema::dropIfExists('transport_route_stops');
        Schema::dropIfExists('transport_staff');
    }
};
