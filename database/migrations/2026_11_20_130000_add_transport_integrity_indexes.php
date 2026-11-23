<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Refuse to add uniqueness where existing records already violate the intended invariant.
        if (Schema::hasTable('transport_route_stops')) {
            $duplicates = DB::table('transport_route_stops')
                ->select('route_id', 'stop_sequence')->groupBy('route_id', 'stop_sequence')->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicates) {
                throw new RuntimeException('Transport migration blocked: duplicate stop_sequence values exist within a route. Resolve duplicates before migrating.');
            }
            Schema::table('transport_route_stops', function (Blueprint $table) {
                $table->unique(['route_id', 'stop_sequence'], 'transport_stops_route_sequence_unique');
            });
        }

        if (Schema::hasTable('student_transport_assignments')) {
            Schema::table('student_transport_assignments', function (Blueprint $table) {
                $table->index(['student_id', 'status'], 'transport_assignments_student_status_idx');
                $table->index(['vehicle_id', 'status'], 'transport_assignments_vehicle_status_idx');
                $table->index(['route_id', 'status'], 'transport_assignments_route_status_idx');
            });
        }

        if (Schema::hasTable('transport_trips')) {
            Schema::table('transport_trips', function (Blueprint $table) {
                $table->index(['trip_date', 'vehicle_id', 'status'], 'transport_trips_vehicle_date_status_idx');
                $table->index(['trip_date', 'driver_id', 'status'], 'transport_trips_driver_date_status_idx');
            });
        }

        if (Schema::hasTable('transport_trip_passengers')) {
            $duplicates = DB::table('transport_trip_passengers')->select('trip_id', 'student_id')
                ->groupBy('trip_id', 'student_id')->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicates) {
                throw new RuntimeException('Transport migration blocked: duplicate passengers exist within a trip. Resolve duplicates before migrating.');
            }
            Schema::table('transport_trip_passengers', function (Blueprint $table) {
                $table->unique(['trip_id', 'student_id'], 'transport_trip_passenger_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transport_trip_passengers')) {
            Schema::table('transport_trip_passengers', fn (Blueprint $table) => $table->dropUnique('transport_trip_passenger_unique'));
        }
        if (Schema::hasTable('transport_trips')) {
            Schema::table('transport_trips', function (Blueprint $table) {
                $table->dropIndex('transport_trips_vehicle_date_status_idx');
                $table->dropIndex('transport_trips_driver_date_status_idx');
            });
        }
        if (Schema::hasTable('student_transport_assignments')) {
            Schema::table('student_transport_assignments', function (Blueprint $table) {
                $table->dropIndex('transport_assignments_student_status_idx');
                $table->dropIndex('transport_assignments_vehicle_status_idx');
                $table->dropIndex('transport_assignments_route_status_idx');
            });
        }
        if (Schema::hasTable('transport_route_stops')) {
            Schema::table('transport_route_stops', fn (Blueprint $table) => $table->dropUnique('transport_stops_route_sequence_unique'));
        }
    }
};
