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
            Schema::create('school_streams', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('school_sections')) {
            $existing = DB::table('school_sections')->whereNotNull('stream')->distinct()->pluck('stream');
            foreach ($existing as $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    DB::table('school_streams')->updateOrInsert(
                        ['name' => $name],
                        ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        }

        // Preserve the legacy stream options as master records during migration.
        foreach (['General', 'Science', 'Commerce', 'Arts', 'Vocational'] as $name) {
            DB::table('school_streams')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_streams');
    }
};
