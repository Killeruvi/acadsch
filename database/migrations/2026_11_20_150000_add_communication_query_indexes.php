<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read', 'created_at'], 'notifications_user_read_created_idx');
            $table->index(['user_id', 'type', 'created_at'], 'notifications_user_type_created_idx');
        });

        Schema::table('academy_notices', function (Blueprint $table) {
            $table->index(['target_audience', 'published_at', 'is_pinned'], 'notices_audience_publish_pin_idx');
            $table->index(['priority', 'published_at'], 'notices_priority_publish_idx');
        });
    }

    public function down(): void
    {
        Schema::table('academy_notices', function (Blueprint $table) {
            $table->dropIndex('notices_audience_publish_pin_idx');
            $table->dropIndex('notices_priority_publish_idx');
        });

        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_created_idx');
            $table->dropIndex('notifications_user_type_created_idx');
        });
    }
};
