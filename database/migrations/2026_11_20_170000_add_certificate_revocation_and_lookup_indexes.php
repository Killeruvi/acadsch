<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_certificates', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('verification_hash');
            $table->foreignId('revoked_by')->nullable()->after('revoked_at')->constrained('users')->nullOnDelete();
            $table->string('revocation_reason', 1000)->nullable()->after('revoked_by');
            $table->index(['user_id', 'revoked_at', 'issued_date'], 'user_certificates_user_status_date_idx');
        });

        Schema::table('user_badges', function (Blueprint $table) {
            $table->index(['user_id', 'awarded_at'], 'user_badges_user_awarded_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropIndex('user_badges_user_awarded_idx');
        });
        Schema::table('user_certificates', function (Blueprint $table) {
            $table->dropIndex('user_certificates_user_status_date_idx');
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn(['revoked_at', 'revocation_reason']);
        });
    }
};
