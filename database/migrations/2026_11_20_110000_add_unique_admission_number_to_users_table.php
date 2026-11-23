<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'admission_number')) {
            throw new \RuntimeException('Cannot enforce admission-number uniqueness: users.admission_number does not exist. Run the school-management migrations first.');
        }

        $duplicate = DB::table('users')
            ->select('admission_number')
            ->whereNotNull('admission_number')
            ->where('admission_number', '<>', '')
            ->groupBy('admission_number')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new \RuntimeException('Cannot add the unique admission-number index because duplicate admission numbers already exist. Resolve duplicate users.admission_number values before retrying this migration.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('admission_number', 'users_admission_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_admission_number_unique');
        });
    }
};
