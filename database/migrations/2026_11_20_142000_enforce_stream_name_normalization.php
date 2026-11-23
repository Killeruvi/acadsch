<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_streams')) {
            return;
        }
        if (! Schema::hasColumn('school_streams', 'name_key')) {
            Schema::table('school_streams', function (Blueprint $table) {
                $table->string('name_key', 50)->nullable();
            });
        }
        foreach (DB::table('school_streams')->select('id', 'name')->get() as $stream) {
            DB::table('school_streams')->where('id', $stream->id)->update(['name_key' => mb_strtoupper(trim((string) $stream->name))]);
        }
        $duplicates = DB::table('school_streams')->select('name_key', DB::raw('COUNT(*) AS total'))
            ->groupBy('name_key')->havingRaw('COUNT(*) > 1')->limit(10)->get();
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Resolve case-insensitive duplicate stream names before enforcing uniqueness: '.json_encode($duplicates));
        }
        Schema::table('school_streams', function (Blueprint $table) {
            $table->unique('name_key', 'school_streams_name_key_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('school_streams') && Schema::hasColumn('school_streams', 'name_key')) {
            Schema::table('school_streams', function (Blueprint $table) {
                $table->dropUnique('school_streams_name_key_unique');
                $table->dropColumn('name_key');
            });
        }
    }
};
