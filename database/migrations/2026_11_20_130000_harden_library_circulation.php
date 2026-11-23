<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('book_issues', 'renewal_count')) {
            Schema::table('book_issues', function (Blueprint $table) {
                $table->unsignedTinyInteger('renewal_count')->default(0)->after('fine_amount');
            });
        }
        try {
            Schema::table('book_issues', function (Blueprint $table) {
                $table->index(['member_id', 'status', 'due_date'], 'book_issues_member_status_due_idx');
            });
        } catch (Throwable $_) {
        }
        try {
            Schema::table('book_issues', function (Blueprint $table) {
                $table->index(['book_id', 'status'], 'book_issues_book_status_idx');
            });
        } catch (Throwable $_) {
        }

        if (! Schema::hasTable('book_reservations')) {
            Schema::create('book_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->constrained('library_books')->restrictOnDelete();
                $table->foreignId('member_id')->constrained('users')->restrictOnDelete();
                $table->string('status', 20)->default('pending');
                $table->timestamp('reserved_at');
                $table->timestamp('fulfilled_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->index(['book_id', 'status', 'reserved_at'], 'book_reservations_queue_idx');
                $table->index(['member_id', 'status'], 'book_reservations_member_status_idx');
            });
        }

        // Preserve circulation history if a member or title is offboarded.
        try {
            Schema::table('book_issues', function (Blueprint $table) {
                try {
                    $table->dropForeign(['book_issues_book_id_foreign']);
                } catch (Throwable $_) {
                }
                try {
                    $table->dropForeign(['book_issues_member_id_foreign']);
                } catch (Throwable $_) {
                }
                $table->foreign('book_id')->references('id')->on('library_books')->restrictOnDelete();
                $table->foreign('member_id')->references('id')->on('users')->restrictOnDelete();
            });
        } catch (Throwable $_) {
        }
    }

    public function down(): void
    {
        Schema::table('book_issues', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropForeign(['member_id']);
            $table->foreign('book_id')->references('id')->on('library_books')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropIndex('book_issues_member_status_due_idx');
            $table->dropIndex('book_issues_book_status_idx');
        });
        if (Schema::hasColumn('book_issues', 'renewal_count')) {
            Schema::table('book_issues', function (Blueprint $table) {
                $table->dropColumn('renewal_count');
            });
        }
        Schema::dropIfExists('book_reservations');
    }
};
