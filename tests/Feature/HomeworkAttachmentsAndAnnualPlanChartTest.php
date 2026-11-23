<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\LessonPlan;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeworkAttachmentsAndAnnualPlanChartTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student1;

    protected User $student2;

    protected AcademicClass $academicClass;

    protected AcademicSubject $physics;

    protected AcademicSubject $chemistry;

    protected AcademicSubject $biology;

    protected AcademicSubject $maths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher.hw@primearc.edu.in',
            'name' => 'Prof. H. C. Verma',
        ]);

        $this->student1 = User::factory()->create([
            'role' => 'student',
            'email' => 'student1.hw@primearc.edu.in',
            'name' => 'Aditya Sharma',
            'student_id_number' => 'STU-9901',
        ]);

        $this->student2 = User::factory()->create([
            'role' => 'student',
            'email' => 'student2.hw@primearc.edu.in',
            'name' => 'Sneha Kulkarni',
            'student_id_number' => 'STU-9902',
        ]);

        $this->academicClass = AcademicClass::create([
            'name' => 'Class 9',
            'grade_level' => 9,
            'section' => 'A',
            'academic_year' => '2026-2027',
        ]);

        $this->physics = AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Physics',
            'code' => 'PHY-901',
        ]);

        $this->chemistry = AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Chemistry',
            'code' => 'CHM-901',
        ]);

        $this->biology = AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Biology',
            'code' => 'BIO-901',
        ]);

        $this->maths = AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Mathematics',
            'code' => 'MTH-901',
        ]);

        // Seed sample curriculum topics
        CurriculumTopic::create([
            'subject_id' => $this->physics->id,
            'title' => 'Motion & Kinematics',
            'chapter_number' => 1,
            'subtopics' => ['Uniform Acceleration', 'Equations of Motion', 'Circular Motion'],
        ]);

        CurriculumTopic::create([
            'subject_id' => $this->chemistry->id,
            'title' => 'Matter in Our Surroundings',
            'chapter_number' => 1,
            'subtopics' => ['States of Matter', 'Latent Heat & Phase Transition', 'Evaporation & Cooling'],
        ]);

        CurriculumTopic::create([
            'subject_id' => $this->biology->id,
            'title' => 'Fundamental Unit of Life',
            'chapter_number' => 1,
            'subtopics' => ['Cell Wall & Membrane', 'Nucleus & Chromosomes', 'Mitochondria & ATP'],
        ]);

        CurriculumTopic::create([
            'subject_id' => $this->maths->id,
            'title' => 'Number Systems',
            'chapter_number' => 1,
            'subtopics' => ['Irrational Numbers', 'Real Numbers on Number Line', 'Laws of Exponents'],
        ]);

        AcademicCalendarService::syncOfficialHolidays(2026);
    }

    public function test_teacher_can_assign_homework_with_attachment_file(): void
    {
        Storage::fake('public');

        $docFile = UploadedFile::fake()->create('kinematics_practice_problems.pdf', 1200, 'application/pdf');

        $response = $this->actingAs($this->teacher)->post(route('homework.store'), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->physics->id,
            'title' => 'Kinematics Problem Set 1',
            'instructions' => 'Solve all numericals in the attached sheet and show steps.',
            'assigned_date' => '2026-11-02',
            'due_date' => '2026-11-04',
            'attachment' => $docFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $task = HomeworkTask::where('title', 'Kinematics Problem Set 1')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->hasAttachment());
        $this->assertEquals('kinematics_practice_problems.pdf', $task->attachment_name);
        $this->assertEquals('pdf', $task->attachment_type);
        $this->assertEquals('📄', $task->attachment_icon);

        Storage::disk('public')->assertExists($task->attachment_path);
    }

    public function test_student_can_submit_homework_with_presentation_slides_or_document(): void
    {
        Storage::fake('public');

        $task = HomeworkTask::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->physics->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Newton Laws Presentation',
            'instructions' => 'Prepare slide presentation on real-life applications of 3rd law.',
            'assigned_date' => '2026-11-02',
            'due_date' => '2026-11-05',
        ]);

        $slidesFile = UploadedFile::fake()->create(
            'newton_third_law_slides.pptx',
            3500,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        );

        $response = $this->actingAs($this->student1)->post(route('homework.submit', $task->id), [
            'student_notes' => 'Here is my complete slide presentation on rocket propulsion and action-reaction pairs.',
            'attachment' => $slidesFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $sub = HomeworkSubmission::where('homework_id', $task->id)->where('student_id', $this->student1->id)->first();
        $this->assertNotNull($sub);
        $this->assertTrue($sub->hasAttachment());
        $this->assertEquals('newton_third_law_slides.pptx', $sub->attachment_name);
        $this->assertEquals('pptx', $sub->attachment_type);
        $this->assertEquals('📊', $sub->attachment_icon);

        Storage::disk('public')->assertExists($sub->attachment_path);
    }

    public function test_student_and_teacher_can_download_homework_task_and_submission_attachments(): void
    {
        Storage::fake('public');

        $taskDoc = UploadedFile::fake()->create('assignment_instructions.docx', 900, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($this->teacher)->post(route('homework.store'), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->chemistry->id,
            'title' => 'States of Matter Research Notes',
            'instructions' => 'Complete the table in attached document.',
            'assigned_date' => '2026-11-02',
            'due_date' => '2026-11-05',
            'attachment' => $taskDoc,
        ]);

        $task = HomeworkTask::where('title', 'States of Matter Research Notes')->first();
        $this->assertNotNull($task);

        // Student 1 downloads teacher's task reference document
        $taskDownload = $this->actingAs($this->student1)->get(
            route('homework.download-attachment', ['id' => $task->id, 'type' => 'task'])
        );
        $taskDownload->assertStatus(200);

        // Student 1 submits solved PDF
        $solutionPdf = UploadedFile::fake()->create('solved_homework.pdf', 1800, 'application/pdf');
        $this->actingAs($this->student1)->post(route('homework.submit', $task->id), [
            'student_notes' => 'Solved all exercises.',
            'attachment' => $solutionPdf,
        ]);

        $sub = HomeworkSubmission::where('homework_id', $task->id)->where('student_id', $this->student1->id)->first();

        // Student 1 downloads own submission
        $ownDownload = $this->actingAs($this->student1)->get(
            route('homework.download-attachment', ['id' => $sub->id, 'type' => 'submission'])
        );
        $ownDownload->assertStatus(200);

        // Teacher downloads student submission
        $teacherDownload = $this->actingAs($this->teacher)->get(
            route('homework.download-attachment', ['id' => $sub->id, 'type' => 'submission'])
        );
        $teacherDownload->assertStatus(200);

        // Student 2 (unauthorized student) is blocked from downloading Student 1's submission
        $forbiddenDownload = $this->actingAs($this->student2)->get(
            route('homework.download-attachment', ['id' => $sub->id, 'type' => 'submission'])
        );
        $forbiddenDownload->assertStatus(403);
    }

    public function test_resubmission_cleans_up_old_homework_attachment(): void
    {
        Storage::fake('public');

        $task = HomeworkTask::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->biology->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Cell Organelle Diagrams',
            'instructions' => 'Draw and label plant and animal cell diagrams.',
            'assigned_date' => '2026-11-02',
            'due_date' => '2026-11-06',
        ]);

        $draftFile = UploadedFile::fake()->create('cell_diagrams_draft.png', 1100, 'image/png');

        $this->actingAs($this->student1)->post(route('homework.submit', $task->id), [
            'student_notes' => 'First draft drawing.',
            'attachment' => $draftFile,
        ]);

        $sub = HomeworkSubmission::where('homework_id', $task->id)->where('student_id', $this->student1->id)->first();
        $firstPath = $sub->attachment_path;
        Storage::disk('public')->assertExists($firstPath);

        // Student resubmits with final version PDF
        $finalFile = UploadedFile::fake()->create('cell_diagrams_final.pdf', 2200, 'application/pdf');
        $this->actingAs($this->student1)->post(route('homework.submit', $task->id), [
            'student_notes' => 'Updated high-resolution diagrams with organelles.',
            'attachment' => $finalFile,
        ]);

        $sub->refresh();
        $this->assertEquals('cell_diagrams_final.pdf', $sub->attachment_name);
        Storage::disk('public')->assertExists($sub->attachment_path);
        // Old draft file was purged from disk
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_annual_chart_view_loads_successfully_with_synchronized_daily_subjects(): void
    {
        $response = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('1-Year Academic Plan (Subjectwise Daily Matrix)', false);
        $response->assertSee('1 Day All Subjects Taught', false);
        $response->assertSee('Physics');
        $response->assertSee('Chemistry');
        $response->assertSee('Biology');
        $response->assertSee('Mathematics');
        $response->assertSee('Day 1');
    }

    public function test_annual_chart_displays_teaching_days_and_holiday_observances(): void
    {
        // Generate synchronized plan starting 2026-06-15
        AcademicCalendarService::generateSynchronizedOneYearPlan(
            $this->academicClass,
            Carbon::create(2026, 6, 15),
            $this->teacher->id,
            60
        );

        $response = $this->actingAs($this->teacher)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'per_page' => 999,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Day 1');
        $response->assertSee('Day 2');
        $response->assertSee('Independence Day of India');
        $response->assertSee('Instructional Off');
    }

    public function test_can_export_annual_chart_csv_with_all_subject_columns(): void
    {
        AcademicCalendarService::generateSynchronizedOneYearPlan(
            $this->academicClass,
            Carbon::create(2026, 6, 15),
            $this->teacher->id,
            30
        );

        $response = $this->actingAs($this->teacher)->get(route('lesson-planner.chart.export-csv', [
            'class_id' => $this->academicClass->id,
        ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Day #', $content);
        $this->assertStringContainsString('Physics (PHY-901)', $content);
        $this->assertStringContainsString('Chemistry (CHM-901)', $content);
        $this->assertStringContainsString('Biology (BIO-901)', $content);
        $this->assertStringContainsString('Mathematics (MTH-901)', $content);
        $this->assertStringContainsString('Day 1', $content);
        $this->assertStringContainsString('Teaching Day (All Subjects Taught)', $content);
    }

    public function test_faculty_can_trigger_synchronized_one_year_master_plan_generation(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('lesson-planner.chart.generate'), [
            'class_id' => $this->academicClass->id,
            'start_date' => '2026-06-15',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify lesson plans exist on Day 1 for all 4 subjects
        $day1Plans = LessonPlan::where('class_id', $this->academicClass->id)
            ->whereDate('planned_date', '2026-06-15')
            ->get();

        $this->assertCount(4, $day1Plans);
        $this->assertEquals(
            ['BIO-901', 'CHM-901', 'MTH-901', 'PHY-901'],
            $day1Plans->pluck('subject.code')->sort()->values()->toArray()
        );
    }

    public function test_single_double_triple_subject_pacing_generation_and_chart_view(): void
    {
        // 1. Generate Single Subject Pacing (1 subject per instructional day)
        $resSingle = AcademicCalendarService::generateSynchronizedOneYearPlan(
            $this->academicClass,
            Carbon::create(2026, 6, 15),
            $this->teacher->id,
            40,
            'single'
        );

        $this->assertEquals(40, $resSingle['days']);
        $this->assertEquals(40, $resSingle['sessions']); // 1 session per day

        // Verify Day 1 has exactly 1 subject
        $day1Plans = LessonPlan::where('class_id', $this->academicClass->id)
            ->whereDate('planned_date', '2026-06-15')
            ->get();
        $this->assertCount(1, $day1Plans);

        // Verify accessing single density view
        $responseSingle = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'density' => 'single',
        ]));
        $responseSingle->assertStatus(200);
        $responseSingle->assertSee('Single (1/Day)');
        $responseSingle->assertSee('Scheduled Subject');

        // Verify accessing double and triple density views
        $responseDouble = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'density' => 'double',
        ]));
        $responseDouble->assertStatus(200);
        $responseDouble->assertSee('Subject Focus 1');
        $responseDouble->assertSee('Subject Focus 2');

        $responseTriple = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'density' => 'triple',
        ]));
        $responseTriple->assertStatus(200);
        $responseTriple->assertSee('Subject Focus 1');
        $responseTriple->assertSee('Subject Focus 2');
        $responseTriple->assertSee('Subject Focus 3');
    }

    public function test_chapter_mapped_competitions_projects_and_debates_days(): void
    {
        AcademicCalendarService::generateSynchronizedOneYearPlan(
            $this->academicClass,
            Carbon::create(2026, 6, 15),
            $this->teacher->id,
            50,
            'all'
        );

        // Verify presence of competition, project, debate, and symposium days
        $competitions = LessonPlan::where('class_id', $this->academicClass->id)
            ->where('activity_type', 'competition')
            ->get();
        $projects = LessonPlan::where('class_id', $this->academicClass->id)
            ->where('activity_type', 'project')
            ->get();
        $debates = LessonPlan::where('class_id', $this->academicClass->id)
            ->where('activity_type', 'debate')
            ->get();

        $this->assertNotEmpty($competitions);
        $this->assertNotEmpty($projects);
        $this->assertNotEmpty($debates);

        // Verify helper methods on model
        $compPlan = $competitions->first();
        $this->assertTrue($compPlan->isCompetition());
        $this->assertFalse($compPlan->isProject());
        $this->assertEquals('🏆', $compPlan->activity_icon);

        $projPlan = $projects->first();
        $this->assertTrue($projPlan->isProject());
        $this->assertEquals('🚀', $projPlan->activity_icon);

        $debPlan = $debates->first();
        $this->assertTrue($debPlan->isDebate());
        $this->assertEquals('🗣️', $debPlan->activity_icon);

        // Verify chart view filters by activity type
        $compResponse = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'activity' => 'competition',
        ]));
        $compResponse->assertStatus(200);
        $compResponse->assertSee('🏆 Competitions');

        $projResponse = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'activity' => 'project',
        ]));
        $projResponse->assertStatus(200);
        $projResponse->assertSee('🚀 Projects');

        $debResponse = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'activity' => 'debate',
        ]));
        $debResponse->assertStatus(200);
        $debResponse->assertSee('🗣️ Debates');
    }

    public function test_faculty_can_resync_with_density_mode_and_reset_existing(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('lesson-planner.chart.generate'), [
            'class_id' => $this->academicClass->id,
            'start_date' => '2026-06-15',
            'density_mode' => 'single',
            'reset_existing' => '1',
        ]);

        $response->assertRedirect(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'density' => 'single',
        ]));
        $response->assertSessionHas('success');

        // Confirm Day 1 only has 1 plan
        $day1Plans = LessonPlan::where('class_id', $this->academicClass->id)
            ->whereDate('planned_date', '2026-06-15')
            ->get();
        $this->assertCount(1, $day1Plans);
    }

    public function test_english_subject_and_curriculum_topics_available_for_college_and_all_classes(): void
    {
        $collegeClass = AcademicClass::firstOrCreate(
            ['grade_level' => 13],
            ['name' => 'College', 'section' => 'A', 'academic_year' => '2026-2027']
        );

        $english = AcademicSubject::firstOrCreate(
            ['class_id' => $collegeClass->id, 'name' => 'Technical English & Research Communication'],
            ['code' => 'ENG-UG105']
        );

        CurriculumTopic::firstOrCreate(
            ['subject_id' => $english->id, 'chapter_number' => 1],
            [
                'title' => 'Scientific Literature Review & Manuscript Drafting',
                'description' => 'IMRaD structure and academic peer review protocols.',
                'subtopics' => ['Literature Synthesis', 'IMRaD Drafting', 'Citation Integrity'],
                'status' => 'planned',
            ]
        );

        $this->assertNotNull($english);
        $this->assertTrue($collegeClass->subjects()->where('name', 'like', '%English%')->exists());
        $this->assertGreaterThan(0, $english->topics()->count());
    }

    public function test_dual_sided_whole_syllabus_tracker_renders_chapter_and_activities(): void
    {
        $topic = CurriculumTopic::where('subject_id', $this->physics->id)->first();
        $topic->toggleActivity('lab', true, '2026-11-04', 'Compound microscope lab');
        $topic->toggleActivity('competition', true, '2026-11-10', 'Olympiad speed problem sprint');

        $response = $this->actingAs($this->student1)->get(route('syllabus.tracker', [
            'grade' => $this->academicClass->grade_level,
            'subject_id' => $this->physics->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Dual-Sided Whole Syllabus Tracker');
        $response->assertSee('Chapter Theory &amp; Concepts Tracker', false);
        $response->assertSee('Chapter Activities Checklist', false);
        $response->assertSee('🧪 Labs Organized');
        $response->assertSee('🏆 Competitions');
        $response->assertSee('Motion &amp; Kinematics', false);
        $response->assertSee('Organized');
    }

    public function test_faculty_can_toggle_chapter_activity_organized_checklist(): void
    {
        $topic = CurriculumTopic::where('subject_id', $this->physics->id)->first();
        $this->assertFalse($topic->isActivityOrganized('debate'));

        // Toggle debate to organized via API/JSON
        $response = $this->actingAs($this->teacher)->postJson(route('syllabus.topics.toggle-activity', $topic->id), [
            'activity_type' => 'debate',
            'organized' => true,
            'notes' => 'Debate on Special Relativity vs Classical Mechanics',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'topic_id' => $topic->id,
            'activity_type' => 'debate',
            'organized' => true,
        ]);

        $this->assertTrue($topic->fresh()->isActivityOrganized('debate'));

        // Toggle back to pending
        $response2 = $this->actingAs($this->teacher)->postJson(route('syllabus.topics.toggle-activity', $topic->id), [
            'activity_type' => 'debate',
            'organized' => false,
        ]);

        $response2->assertStatus(200);
        $this->assertFalse($topic->fresh()->isActivityOrganized('debate'));
    }

    public function test_lesson_plan_revision_days_are_generated_and_filterable_in_chart(): void
    {
        AcademicCalendarService::generateSynchronizedOneYearPlan(
            $this->academicClass,
            Carbon::create(2026, 6, 15),
            $this->teacher->id,
            60,
            'all'
        );

        $revisions = LessonPlan::where('class_id', $this->academicClass->id)
            ->where('activity_type', 'revision')
            ->get();

        $this->assertGreaterThan(0, $revisions->count());

        $firstRev = $revisions->first();
        $this->assertTrue($firstRev->isRevision());
        $this->assertFalse($firstRev->isTest());
        $this->assertEquals('🔄', $firstRev->activity_icon);
        $this->assertStringContainsString('emerald', $firstRev->activity_badge_class);

        // Access Annual Chart with Revision filter
        $response = $this->actingAs($this->student1)->get(route('lesson-planner.chart', [
            'class_id' => $this->academicClass->id,
            'activity' => 'revision',
        ]));

        $response->assertStatus(200);
        $response->assertSee('🔄 Revision Days');
        $response->assertSee('🔄 Revisions (');
    }

    public function test_faculty_can_toggle_chapter_revision_checklist_item(): void
    {
        $topic = CurriculumTopic::where('subject_id', $this->physics->id)->first();
        $this->assertFalse($topic->isActivityOrganized('revision'));

        // Toggle revision to organized
        $response = $this->actingAs($this->teacher)->postJson(route('syllabus.topics.toggle-activity', $topic->id), [
            'activity_type' => 'revision',
            'organized' => true,
            'notes' => 'Formula sheet sprint and mind-map drill conducted',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'topic_id' => $topic->id,
            'activity_type' => 'revision',
            'organized' => true,
        ]);

        $this->assertTrue($topic->fresh()->isActivityOrganized('revision'));
    }
}
