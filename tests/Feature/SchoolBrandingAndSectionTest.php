<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\SchoolPeriodTimetable;
use App\Models\SchoolSection;
use App\Models\User;
use App\Services\SchoolBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolBrandingAndSectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@primeos.edu',
        ]);
    }

    /**
     * Test: Sections are created individually first under a stream and stored as reusable records.
     */
    public function test_sections_are_created_under_stream_as_reusable_records(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.sections.store'), [
            'stream' => 'Science',
            'name' => 'D',
            'capacity' => 45,
            'room_number' => 'Lab 3',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('school_sections', [
            'stream' => 'Science',
            'name' => 'D',
            'capacity' => 45,
            'room_number' => 'Lab 3',
        ]);
    }

    /**
     * Test: Duplicate sections under the same stream are prevented.
     */
    public function test_duplicate_sections_under_same_stream_are_prevented(): void
    {
        // 'Science' - 'A' already seeded by migration
        $this->assertDatabaseHas('school_sections', [
            'stream' => 'Science',
            'name' => 'A',
        ]);

        // Attempting to create duplicate 'Science' - 'A' must be rejected
        $response = $this->actingAs($this->admin)->post(route('school.sections.store'), [
            'stream' => 'Science',
            'name' => 'A',
            'capacity' => 40,
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Class creation requires selecting from existing sections (no free text).
     */
    public function test_class_creation_requires_existing_sections(): void
    {
        // Attempting to create without section_ids fails validation
        $response = $this->actingAs($this->admin)->post(route('school.classes.store'), [
            'grade_level' => 10,
            'name' => 'Class 10',
            'stream' => 'General',
        ]);

        $response->assertSessionHasErrors('section_ids');
    }

    /**
     * Test: Class creation instantiates classes from selected reusable sections.
     */
    public function test_class_creation_instantiates_class_with_stream_and_selected_sections(): void
    {
        $secA = SchoolSection::where('stream', 'General')->where('name', 'A')->firstOrFail();
        $secB = SchoolSection::where('stream', 'General')->where('name', 'B')->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('school.classes.store'), [
            'grade_level' => 8,
            'name' => 'Class 8',
            'stream' => 'General',
            'capacity' => 40,
            'section_ids' => [$secA->id, $secB->id],
            'academic_year' => '2026-2027',
        ]);

        $response->assertRedirect(route('school.classes.index'));

        $this->assertDatabaseHas('academic_classes', [
            'grade_level' => 8,
            'stream' => 'General',
            'section' => 'A',
            'section_id' => $secA->id,
            'academic_year' => '2026-2027',
        ]);

        $this->assertDatabaseHas('academic_classes', [
            'grade_level' => 8,
            'stream' => 'General',
            'section' => 'B',
            'section_id' => $secB->id,
            'academic_year' => '2026-2027',
        ]);
    }

    /**
     * Test: Duplicate class prevention for same grade, stream, section and academic year.
     */
    public function test_duplicate_class_creation_is_prevented(): void
    {
        $secA = SchoolSection::where('stream', 'General')->where('name', 'A')->firstOrFail();

        AcademicClass::create([
            'name' => 'Class 9',
            'grade_level' => 9,
            'stream' => 'General',
            'section' => 'A',
            'section_id' => $secA->id,
            'academic_year' => '2026-2027',
        ]);

        // Attempt to create the exact duplicate
        $response = $this->actingAs($this->admin)->post(route('school.classes.store'), [
            'grade_level' => 9,
            'name' => 'Class 9',
            'stream' => 'General',
            'capacity' => 40,
            'section_ids' => [$secA->id],
            'academic_year' => '2026-2027',
        ]);

        $response->assertSessionHasErrors('section_ids');
    }

    /**
     * Test: School Branding can be customized fully and displays the mark of PrimeOS.
     */
    public function test_school_branding_can_be_customized_with_primeos_mark(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update'), [
            'institute_name' => 'St. Xavier International Academy',
            'school_short_name' => 'SXIA',
            'campus_name' => 'South Campus',
            'motto' => 'Light, Truth, Excellence',
            'school_logo_url' => 'https://example.com/sxia-logo.png',
            'board_name' => 'ICSE',
            'affiliation_number' => 'ICSE/2026/778',
            'academic_session' => '2026-2027',
            'principal_name' => 'Rev. Dr. Francis Augustine',
            'institution_phone' => '+91 98111 22233',
            'institution_email' => 'principal@sxia.edu',
            'institution_address' => '12 Heritage Boulevard, South Campus',
            'upi_vpa' => 'sxia@upi',
            'currency_symbol' => '₹',
            'brand_primary_color' => '#059669',
        ]);

        $response->assertSessionHas('success');

        $branding = SchoolBrandingService::get();
        $this->assertEquals('St. Xavier International Academy', $branding['name']);
        $this->assertEquals('SXIA', $branding['short_name']);
        $this->assertEquals('Powered by PrimeOS', $branding['brand_mark']);
        $this->assertEquals('PrimeOS', $branding['system_name']);

        // Check view response contains customized school name AND the mark of PrimeOS
        $viewResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('St. Xavier International Academy');
        $viewResponse->assertSee('Powered by PrimeOS');
        $viewResponse->assertSee('SXIA');
    }

    /**
     * Test: Faculty cannot inspect students outside their assigned classes.
     */
    public function test_teacher_cannot_inspect_students_outside_assigned_classes(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $secA = SchoolSection::where('stream', 'General')->where('name', 'A')->first();

        $classA = AcademicClass::create([
            'name' => 'Class 7',
            'section' => 'A',
            'stream' => 'General',
            'section_id' => $secA?->id,
            'grade_level' => 7,
            'class_teacher_id' => $teacher->id,
        ]);

        $classB = AcademicClass::create([
            'name' => 'Class 8',
            'section' => 'A',
            'stream' => 'General',
            'section_id' => $secA?->id,
            'grade_level' => 8,
        ]);

        $studentA = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $classA->id,
        ]);

        $studentB = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $classB->id,
        ]);

        // Teacher CAN view student in assigned Class A
        $resA = $this->actingAs($teacher)->get(route('school.students.show', $studentA->id));
        $resA->assertStatus(200);

        // Teacher CANNOT view student in Class B (403 Forbidden)
        $resB = $this->actingAs($teacher)->get(route('school.students.show', $studentB->id));
        $resB->assertStatus(403);
    }

    /**
     * Test: Timetable clash detection rejects double-booking a teacher on the same slot.
     */
    public function test_timetable_clash_detection_prevents_teacher_double_booking(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $secA = SchoolSection::where('stream', 'General')->where('name', 'A')->first();

        $class1 = AcademicClass::create([
            'name' => 'Class 5',
            'section' => 'A',
            'stream' => 'General',
            'section_id' => $secA?->id,
            'grade_level' => 5,
        ]);

        $class2 = AcademicClass::create([
            'name' => 'Class 6',
            'section' => 'A',
            'stream' => 'General',
            'section_id' => $secA?->id,
            'grade_level' => 6,
        ]);

        // Book teacher for Class 1 on Monday Period 1
        SchoolPeriodTimetable::create([
            'class_id' => $class1->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30',
            'end_time' => '09:15',
            'is_break' => false,
        ]);

        $subject = AcademicSubject::firstOrCreate([
            'class_id' => $class2->id,
            'code' => 'SCI-06',
        ], [
            'name' => 'Science',
            'teacher_id' => $teacher->id,
        ]);
        $subject->update(['teacher_id' => $teacher->id]);

        // Admin attempts to book same teacher for Class 2 on Monday Period 1
        $response = $this->actingAs($this->admin)->post(route('school.timetable.store'), [
            'class_id' => $class2->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30',
            'end_time' => '09:15',
            'is_break' => false,
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    /**
     * Test: Classes & Sections index view renders unified modals and Alpine controls for admin.
     */
    public function test_classes_index_renders_unified_modals_and_controls_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('school.classes.index'));
        $response->assertOk();
        $response->assertSee('Classes &amp; Sections Management', false);
        $response->assertSee('Create Reusable Section');
        $response->assertSee('Add New Class');
        $response->assertSee('submitSectionForm');
        $response->assertSee('createSectionOpen');
        $response->assertSee('createClassOpen');
    }

    /**
     * Test: Section creation via AJAX returns structured JSON and persists record.
     */
    public function test_section_creation_via_ajax_returns_json(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('school.sections.store'), [
            'stream' => 'Commerce',
            'name' => 'D',
            'capacity' => 42,
            'room_number' => 'Block C-10',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('section.name', 'D');
        $response->assertJsonPath('section.stream', 'Commerce');

        $this->assertDatabaseHas('school_sections', [
            'stream' => 'Commerce',
            'name' => 'D',
            'capacity' => 42,
        ]);
    }

    /**
     * Test: Case-insensitive duplicate section is rejected via JSON with 422.
     */
    public function test_case_insensitive_duplicate_section_rejected_via_json(): void
    {
        // 'General' - 'A' already exists
        $response = $this->actingAs($this->admin)->postJson(route('school.sections.store'), [
            'stream' => 'General',
            'name' => 'a', // lowercase
            'capacity' => 40,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test: Class creation via AJAX returns JSON and creates the classes.
     */
    public function test_class_creation_via_ajax_returns_json(): void
    {
        $secA = SchoolSection::where('stream', 'General')->where('name', 'A')->firstOrFail();

        $response = $this->actingAs($this->admin)->postJson(route('school.classes.store'), [
            'grade_level' => 7,
            'name' => 'Class 7',
            'stream' => 'General',
            'capacity' => 40,
            'section_ids' => [$secA->id],
            'academic_year' => '2026-2027',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'created_count' => 1,
        ]);

        $this->assertDatabaseHas('academic_classes', [
            'grade_level' => 7,
            'stream' => 'General',
            'section' => 'A',
            'section_id' => $secA->id,
        ]);
    }

    /**
     * Test: AcademicClass creation for college courses does not crash on missing code column.
     */
    public function test_college_course_class_creation_does_not_crash(): void
    {
        $class = AcademicClass::firstOrCreate(
            ['name' => 'College Academy Class', 'grade_level' => 12],
            ['section' => 'A', 'stream' => 'General', 'academic_year' => '2026-2027']
        );

        $this->assertNotNull($class);
        $this->assertEquals('College Academy Class', $class->name);
        $this->assertEquals(12, $class->grade_level);
    }
}
