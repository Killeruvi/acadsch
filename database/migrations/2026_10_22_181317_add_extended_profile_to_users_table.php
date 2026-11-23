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
            $table->string('student_id_number')->nullable()->unique();
            $table->string('avatar_url')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->date('card_valid_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'student_id_number',
                'avatar_url',
                'date_of_birth',
                'address',
                'guardian_name',
                'guardian_phone',
                'guardian_email',
                'blood_group',
                'card_valid_until',
            ]);
        });
    }
};
