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
        // 1. Add biometric_pin to users if not present
        if (! Schema::hasColumn('users', 'biometric_pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('biometric_pin', 50)->nullable()->after('admission_number')->index();
            });
        }

        // 2. Biometric Devices (Turnstiles, Fingerprint/Face Recognition punch clocks)
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name', 100);
            $table->string('device_serial', 100)->unique();
            $table->string('device_model', 100)->nullable();
            $table->string('device_ip', 45)->nullable();
            $table->string('device_location', 191)->nullable();
            $table->string('auth_token', 64)->nullable()->index();
            $table->timestamp('last_heartbeat')->nullable();
            $table->enum('status', ['online', 'offline', 'disabled'])->default('offline');
            $table->timestamps();
        });

        // 3. Biometric Raw Punch Logs
        Schema::create('biometric_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->string('biometric_pin', 50);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('punch_time');
            $table->enum('punch_type', ['in', 'out', 'auto'])->default('auto');
            $table->enum('status', ['pending', 'processed', 'ignored', 'failed'])->default('pending');
            $table->string('error_message', 500)->nullable();
            $table->timestamps();

            $table->index(['punch_time', 'status']);
            $table->index('biometric_pin');
        });

        // 4. Absentee Alert Dispatch History
        Schema::create('attendance_alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('attendance_sessions')->nullOnDelete();
            $table->date('attendance_date');
            $table->string('channel', 50)->default('in_app'); // in_app, sms, whatsapp, telegram
            $table->string('recipient', 191);
            $table->enum('status', ['sent', 'delivered', 'failed'])->default('sent');
            $table->text('message_payload')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'attendance_date', 'channel'], 'student_date_channel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_alert_logs');
        Schema::dropIfExists('biometric_logs');
        Schema::dropIfExists('biometric_devices');

        if (Schema::hasColumn('users', 'biometric_pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('biometric_pin');
            });
        }
    }
};
