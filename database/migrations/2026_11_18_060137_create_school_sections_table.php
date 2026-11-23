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
        if (!Schema::hasTable('school_sections')) {
            Schema::create('school_sections', function (Blueprint $table) {
                $table->id();
                $table->string('name', 30); // e.g. "A", "B", "Medical"
                $table->string('stream', 50)->default('General'); // e.g. "General", "Science", "Commerce", "Arts"
                $table->string('room_number', 50)->nullable();
                $table->unsignedSmallInteger('capacity')->nullable();
                $table->string('description', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['stream', 'name']);
            });
        }

        if (Schema::hasTable('academic_classes') && !Schema::hasColumn('academic_classes', 'section_id')) {
            Schema::table('academic_classes', function (Blueprint $table) {
                $table->foreignId('section_id')->nullable()->after('stream')->constrained('school_sections')->nullOnDelete();
            });
        }

        // Populate reusable sections from existing classes or defaults (single letters only: A, B, C...)
        $defaultStreams = [
            'General' => ['A', 'B', 'C'],
            'Science' => ['A', 'B', 'C'],
            'Commerce' => ['A', 'B', 'C'],
            'Arts' => ['A', 'B', 'C'],
        ];

        foreach ($defaultStreams as $stream => $sections) {
            foreach ($sections as $sectionName) {
                DB::table('school_sections')->updateOrInsert(
                    ['stream' => $stream, 'name' => $sectionName],
                    ['capacity' => 40, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Migrate any existing academic_classes records to link to their respective school_sections
        if (Schema::hasTable('academic_classes')) {
            $classes = DB::table('academic_classes')->get();
            foreach ($classes as $c) {
                $stream = $c->stream ?: 'General';
                $secName = $c->section ?: 'A';

                // Find or create the reusable section
                $existingSec = DB::table('school_sections')
                    ->where('stream', $stream)
                    ->where('name', $secName)
                    ->first();

                if (!$existingSec) {
                    $secId = DB::table('school_sections')->insertGetId([
                        'name' => $secName,
                        'stream' => $stream,
                        'capacity' => $c->capacity ?? 40,
                        'room_number' => $c->room_number,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $secId = $existingSec->id;
                }

                DB::table('academic_classes')
                    ->where('id', $c->id)
                    ->update([
                        'stream' => $stream,
                        'section_id' => $secId,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('academic_classes') && Schema::hasColumn('academic_classes', 'section_id')) {
            Schema::table('academic_classes', function (Blueprint $table) {
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            });
        }

        Schema::dropIfExists('school_sections');
    }
};
