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
        if (! Schema::hasTable('subject_masters')) {
            Schema::create('subject_masters', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('code', 30);
                $table->string('stream', 50)->default('All'); // Stream identity is populated from master data
                $table->string('category', 50)->nullable();
                $table->unsignedTinyInteger('default_periods')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['name', 'stream']);
                $table->index(['stream', 'is_active']);
                $table->index('code');
            });
        }

        // Pre-populate standard Subject Master catalogue
        $standardSubjects = [
            // General / All Stream Foundation
            ['name' => 'English Language & Literature', 'code' => 'ENG', 'stream' => 'All', 'category' => 'Language', 'default_periods' => 6],
            ['name' => 'English Core', 'code' => 'ENG-CORE', 'stream' => 'All', 'category' => 'Language', 'default_periods' => 5],
            ['name' => 'Mathematics Standard', 'code' => 'MATH', 'stream' => 'General', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'General Science (Physics, Chem, Bio)', 'code' => 'SCI', 'stream' => 'General', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Social Science (Hist, Civ, Geo)', 'code' => 'SST', 'stream' => 'General', 'category' => 'Core', 'default_periods' => 6],
            ['name' => 'Second Language (Hindi/Regional)', 'code' => 'HIN', 'stream' => 'All', 'category' => 'Language', 'default_periods' => 5],
            ['name' => 'Environmental Studies (EVS)', 'code' => 'EVS', 'stream' => 'General', 'category' => 'Core', 'default_periods' => 5],
            ['name' => 'Computer Basics & Coding', 'code' => 'CS', 'stream' => 'All', 'category' => 'Skill', 'default_periods' => 4],
            ['name' => 'Information Technology (IT/AI)', 'code' => 'IT', 'stream' => 'All', 'category' => 'Skill', 'default_periods' => 4],
            ['name' => 'Physical Education & Yoga', 'code' => 'PE', 'stream' => 'All', 'category' => 'Elective', 'default_periods' => 4],
            ['name' => 'Art & Aesthetic Education', 'code' => 'ART', 'stream' => 'General', 'category' => 'Elective', 'default_periods' => 3],
            ['name' => 'Moral Science & Life Skills', 'code' => 'MSL', 'stream' => 'General', 'category' => 'Elective', 'default_periods' => 2],

            // Science Stream
            ['name' => 'Physics', 'code' => 'PHY', 'stream' => 'Science', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Chemistry', 'code' => 'CHM', 'stream' => 'Science', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Biology', 'code' => 'BIO', 'stream' => 'Science', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Pure Mathematics & Calculus', 'code' => 'MTH', 'stream' => 'Science', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Computer Science & Python', 'code' => 'CS-SCI', 'stream' => 'Science', 'category' => 'Elective', 'default_periods' => 6],
            ['name' => 'Biotechnology', 'code' => 'BIOTECH', 'stream' => 'Science', 'category' => 'Elective', 'default_periods' => 5],

            // Commerce Stream
            ['name' => 'Accountancy & Financial Accounting', 'code' => 'ACC', 'stream' => 'Commerce', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Business Studies & Management', 'code' => 'BST', 'stream' => 'Commerce', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Economics & Statistics', 'code' => 'ECO', 'stream' => 'Commerce', 'category' => 'Core', 'default_periods' => 7],
            ['name' => 'Applied Mathematics', 'code' => 'APMTH', 'stream' => 'Commerce', 'category' => 'Elective', 'default_periods' => 6],
            ['name' => 'Informatics Practices (IP)', 'code' => 'IP', 'stream' => 'Commerce', 'category' => 'Elective', 'default_periods' => 6],
            ['name' => 'Entrepreneurship', 'code' => 'ENT', 'stream' => 'Commerce', 'category' => 'Elective', 'default_periods' => 5],

            // Arts & Humanities Stream
            ['name' => 'History & World Civilizations', 'code' => 'HIST', 'stream' => 'Arts', 'category' => 'Core', 'default_periods' => 6],
            ['name' => 'Political Science & Civics', 'code' => 'POL', 'stream' => 'Arts', 'category' => 'Core', 'default_periods' => 6],
            ['name' => 'Geography & Cartography', 'code' => 'GEO', 'stream' => 'Arts', 'category' => 'Core', 'default_periods' => 6],
            ['name' => 'Psychology & Behavioral Science', 'code' => 'PSY', 'stream' => 'Arts', 'category' => 'Elective', 'default_periods' => 5],
            ['name' => 'Sociology & Social Institutions', 'code' => 'SOC', 'stream' => 'Arts', 'category' => 'Elective', 'default_periods' => 5],
            ['name' => 'Legal Studies', 'code' => 'LEG', 'stream' => 'Arts', 'category' => 'Elective', 'default_periods' => 5],
        ];

        foreach ($standardSubjects as $s) {
            DB::table('subject_masters')->updateOrInsert(
                ['name' => $s['name'], 'stream' => $s['stream']],
                [
                    'code' => $s['code'],
                    'category' => $s['category'],
                    'default_periods' => $s['default_periods'],
                    'description' => "Standard {$s['stream']} curriculum subject {$s['name']}",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Import existing class subjects using their actual class stream and configured weekly load.
        // Unknown category/load values remain blank for an administrator to configure; no guessed values are inserted.
        if (Schema::hasTable('academic_subjects')) {
            $existing = DB::table('academic_subjects')
                ->leftJoin('academic_classes', 'academic_classes.id', '=', 'academic_subjects.class_id')
                ->select('academic_subjects.name', 'academic_subjects.code', 'academic_subjects.weekly_periods', 'academic_classes.stream')
                ->distinct()
                ->get();

            foreach ($existing as $ex) {
                $stream = trim((string) ($ex->stream ?? '')) ?: 'All';
                $cleanCode = preg_replace('/-\d+$/', '', (string) $ex->code);
                DB::table('subject_masters')->updateOrInsert(
                    ['name' => $ex->name, 'stream' => $stream],
                    [
                        'code' => $cleanCode ?: $ex->code,
                        'category' => null,
                        'default_periods' => $ex->weekly_periods && (int) $ex->weekly_periods > 0 ? (int) $ex->weekly_periods : null,
                        'description' => 'Imported from an existing class subject; review category and grade applicability.',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_masters');
    }
};
