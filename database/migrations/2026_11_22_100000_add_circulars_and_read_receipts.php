<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add circular-specific columns to academy_notices
        Schema::table('academy_notices', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_notices', 'type')) {
                $table->string('type', 20)->default('notice')->after('id')
                    ->comment('notice | circular');
            }
            if (!Schema::hasColumn('academy_notices', 'status')) {
                $table->string('status', 20)->default('draft')->after('type')
                    ->comment('draft | pending_approval | published | archived');
            }
            if (!Schema::hasColumn('academy_notices', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('author_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('academy_notices', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('academy_notices', 'circular_number')) {
                $table->string('circular_number', 60)->nullable()->unique()->after('approved_at');
            }
            if (!Schema::hasColumn('academy_notices', 'requires_acknowledgement')) {
                $table->boolean('requires_acknowledgement')->default(false)->after('circular_number');
            }
            if (!Schema::hasColumn('academy_notices', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('requires_acknowledgement');
            }
        });

        // Read receipts table for circulars
        if (!Schema::hasTable('circular_read_receipts')) {
            Schema::create('circular_read_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notice_id')->constrained('academy_notices')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('acknowledged')->default(false);
                $table->timestamp('read_at');
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();
                $table->unique(['notice_id', 'user_id'], 'circular_read_user_unique');
                $table->index(['notice_id', 'read_at'], 'circular_reads_notice_time_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('circular_read_receipts');
        Schema::table('academy_notices', function (Blueprint $table) {
            foreach (['type', 'status', 'approved_by', 'approved_at', 'circular_number', 'requires_acknowledgement', 'expires_at'] as $col) {
                if (Schema::hasColumn('academy_notices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
