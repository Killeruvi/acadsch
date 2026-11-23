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
        Schema::table('users', function (Blueprint $table) {
            $table->string('previous_school_name', 150)->nullable()->after('address');
            $table->string('previous_school_board', 60)->nullable()->after('previous_school_name');
            $table->string('previous_school_year', 30)->nullable()->after('previous_school_board');
            $table->string('previous_school_tc_number', 60)->nullable()->after('previous_school_year');
            $table->text('previous_school_remarks')->nullable()->after('previous_school_tc_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'previous_school_name',
                'previous_school_board',
                'previous_school_year',
                'previous_school_tc_number',
                'previous_school_remarks',
            ]);
        });
    }
};
