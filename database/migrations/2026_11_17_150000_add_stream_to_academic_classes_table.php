<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_classes', 'stream')) {
                $table->string('stream', 50)->nullable()->after('grade_level');
            }
        });

        // Set initial stream values for secondary / higher-secondary classes
        DB::table('academic_classes')->where('section', 'Science')->update(['stream' => 'Science']);
        DB::table('academic_classes')->where('section', 'Commerce')->update(['stream' => 'Commerce']);
        DB::table('academic_classes')->where('section', 'Arts')->update(['stream' => 'Arts']);

        // For classes with grade_level 11 or 12 and section 'A', designate as Arts stream
        DB::table('academic_classes')
            ->whereIn('grade_level', [11, 12])
            ->where('section', 'A')
            ->update([
                'stream' => 'Arts',
                'section' => 'Arts',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_classes', function (Blueprint $table) {
            if (Schema::hasColumn('academic_classes', 'stream')) {
                $table->dropColumn('stream');
            }
        });
    }
};
