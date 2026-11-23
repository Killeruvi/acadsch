<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitories', function (Blueprint $table) {
            $table->string('warden_name', 150)->nullable();
            $table->string('warden_phone', 30)->nullable();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::table('dormitory_rooms', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
        });

        $duplicates = DB::table('dormitory_rooms')
            ->select('dormitory_id', 'room_number')->selectRaw('COUNT(*) as aggregate')
            ->groupBy('dormitory_id', 'room_number')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicates) {
            throw new RuntimeException('Duplicate room numbers exist within a dormitory. Resolve them before applying dormitory integrity constraints.');
        }

        Schema::table('dormitory_rooms', function (Blueprint $table) {
            $table->unique(['dormitory_id', 'room_number'], 'dormitory_room_number_unique');
        });

        Schema::create('dormitory_bed_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dormitory_room_id')->constrained('dormitory_rooms')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('bed_number', 30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('reserved');
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
            $table->index(['dormitory_room_id', 'status', 'start_date', 'end_date'], 'dorm_bed_occupancy_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_bed_assignments');
        Schema::table('dormitory_rooms', function (Blueprint $table) {
            $table->dropUnique('dormitory_room_number_unique');
            $table->dropColumn('is_active');
        });
        Schema::table('dormitories', function (Blueprint $table) {
            $table->dropColumn(['warden_name', 'warden_phone', 'is_active']);
        });
    }
};
