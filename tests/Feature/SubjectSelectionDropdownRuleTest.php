<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\SchoolExamTerm;
use App\Models\SubjectMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectSelectionDropdownRuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected AcademicClass $scienceClass;
    protected AcademicClass $commerceClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@primeos.edu',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'prof.einstein@primeos.edu',
        ]);

        $this->scienceClass = AcademicClass::create([
            'name' => 'Class 12 - Science Alpha',
            'grade_level' => 12,
            'section' => 'A',
            'stream' => 'Science',
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        $this->commerceClass = AcademicClass::create([
            'name' => 'Class 12 - Commerce Alpha',
            'grade_level' => 12,
            'section' => 'A',
            'stream' => 'Commerce',
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        // Ensure SubjectMaster has base test entries
        SubjectMaster::updateOrInsert(
            ['name' => 'Physics', 'stream' => 'Science'],
            ['code' => 'PHY', 'category' => 'Core', 'default_periods' => 7, 'is_active' => true]
        );

        SubjectMaster::updateOrInsert(
            ['name' => 'Accountancy & Financial Accounting', 'stream' => 'Commerce'],
            ['code' => 'ACC', 'category' => 'Core', 'default_periods' => 7, 'is_active' => true]
        );

        SubjectMaster::updateOrInsert(
            ['name' => 'English Language & Literature', 'stream' => 'All'],
            ['code' => 'ENG', 'category' => 'Language', 'default_periods' => 6, 'is_active' => true]
        );
    }

    /**
     * Test 1: Subject master loads and filters by stream/class.
     */
    public function test_subject_master_loads_and_filters_by_class_stream(): void
    {
        $scienceMasters = SubjectMaster::getAvailableForClass($this->scienceClass);
        $scienceNames = $scienceMasters->pluck('name')->toArray();

        $this->assertContains('Physics', $scienceNames);
        $this->assertContains('English Language & Literature', $scienceNames);
        $this->assertNotContains('Accountancy & Financial Accounting', $scienceNames);

        $commerceMasters = SubjectMaster::getAvailableForClass($this->commerceClass);
        $commerceNames = $commerceMasters->pluck('name')->toArray();

        $this->assertContains('Accountancy & Financial Accounting', $commerceNames);
        $this->assertContains('English Language & Literature', $commerceNames);
        $this->assertNotContains('Physics', $commerceNames);
    }

    /**
     * Test 2: Master options API returns available subjects for dependent dropdowns.
     */
    public function test_master_options_api_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('school.subjects.master-options', [
            'class_id' => $this->scienceClass->id,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('stream', 'Science')
            ->assertJsonPath('class_id', $this->scienceClass->id);

        $masters = collect($response->json('masters'));
        $this->assertTrue($masters->contains('name', 'Physics'));
    }

    /**
     * Test 3: Adding a valid subject from Subject Master succeeds.
     */
    public function test_adding_valid_subject_from_master_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.classes.subjects.store', $this->scienceClass->id), [
            'name' => 'Physics',
            'code' => 'PHY-12',
            'teacher_id' => $this->teacher->id,
            'weekly_periods' => 7,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_subjects', [
            'class_id' => $this->scienceClass->id,
            'name' => 'Physics',
            'code' => 'PHY-12',
            'teacher_id' => $this->teacher->id,
        ]);
    }

    /**
     * Test 4: Duplicate subject addition in same class is prevented.
     */
    public function test_duplicate_subject_in_same_class_is_rejected(): void
    {
        // First creation
        AcademicSubject::create([
            'class_id' => $this->scienceClass->id,
            'name' => 'Physics',
            'code' => 'PHY-12',
            'weekly_periods' => 7,
        ]);

        // Second creation attempt with same name
        $response = $this->actingAs($this->admin)->post(route('school.classes.subjects.store', $this->scienceClass->id), [
            'name' => 'Physics',
            'code' => 'PHY-12',
            'weekly_periods' => 7,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, AcademicSubject::where('class_id', $this->scienceClass->id)->where('name', 'Physics')->count());
    }

    /**
     * Test 5: Invalid subject not in Subject Master catalogue is rejected.
     */
    public function test_invalid_subject_not_in_master_catalogue_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.classes.subjects.store', $this->scienceClass->id), [
            'name' => 'Fake Alien Studies 101',
            'code' => 'ALIEN-99',
            'weekly_periods' => 5,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('academic_subjects', [
            'name' => 'Fake Alien Studies 101',
        ]);
    }

    /**
     * Test 6: Class show view renders dropdowns instead of manual text inputs.
     */
    public function test_class_show_view_renders_dropdowns_for_name_and_code(): void
    {
        $response = $this->actingAs($this->admin)->get(route('school.classes.show', $this->scienceClass->id));

        $response->assertStatus(200);
        // Subject Name dropdown exists
        $response->assertSee('<select name="name"', false);
        // Subject Code dropdown exists
        $response->assertSee('<select name="code"', false);
        // No manual text input for subject name or code in add-subject-modal
        $response->assertDontSee('<input type="text" name="name" required placeholder="e.g. Mathematics or Physics"', false);
        $response->assertDontSee('<input type="text" name="code" required placeholder="e.g. MTH-10"', false);
    }

    /**
     * Test 7: Timetable period rejects subject belonging to a different class.
     */
    public function test_timetable_period_rejects_subject_from_different_class(): void
    {
        $scienceSubject = AcademicSubject::create([
            'class_id' => $this->scienceClass->id,
            'name' => 'Physics',
            'code' => 'PHY-12',
            'weekly_periods' => 7,
        ]);

        // Attempt to assign $scienceSubject to $commerceClass in timetable
        $response = $this->actingAs($this->admin)->post(route('school.timetable.store'), [
            'class_id' => $this->commerceClass->id,
            'subject_id' => $scienceSubject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30',
            'end_time' => '09:15',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('school_period_timetables', [
            'class_id' => $this->commerceClass->id,
            'subject_id' => $scienceSubject->id,
        ]);
    }

    /**
     * Test 8: Exam paper schedule rejects subject belonging to a different class.
     */
    public function test_exam_schedule_rejects_subject_from_different_class(): void
    {
        $term = SchoolExamTerm::create([
            'name' => 'Term 1 Midterm',
            'academic_year' => '2026-2027',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'status' => 'scheduled',
        ]);

        $scienceSubject = AcademicSubject::create([
            'class_id' => $this->scienceClass->id,
            'name' => 'Physics',
            'code' => 'PHY-12',
            'weekly_periods' => 7,
        ]);

        // Attempt to schedule $scienceSubject for $commerceClass
        $response = $this->actingAs($this->admin)->post(route('school.exams.schedules.store'), [
            'exam_term_id' => $term->id,
            'class_id' => $this->commerceClass->id,
            'subject_id' => $scienceSubject->id,
            'exam_date' => now()->addDays(6)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'max_marks' => 100,
            'pass_marks' => 35,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('school_exam_schedules', [
            'exam_term_id' => $term->id,
            'class_id' => $this->commerceClass->id,
            'subject_id' => $scienceSubject->id,
        ]);
    }
}
