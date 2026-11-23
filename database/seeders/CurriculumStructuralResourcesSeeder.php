<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\CurriculumVideo;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\HomeworkTask;
use App\Models\User;
use Illuminate\Database\Seeder;

class CurriculumStructuralResourcesSeeder extends Seeder
{
    /**
     * Seed structural Videos, DPPs, and Homework linked directly to Curriculum Topics (Chapters).
     */
    public function run(): void
    {
        // Default lectures, videos, and dummy notes removed. Real video lectures and authentic PDF notes are now managed directly by faculty.
    }
}
