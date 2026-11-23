<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\ClassEnrollment;
use App\Models\SchoolSection;
use App\Models\SchoolFeeStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BulkImportExportAndOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Mark system as onboarded by default for regular feature testing
        AcademySetting::set('onboarding_completed', 'true');
        AcademySetting::set('academic_session', '2026-27');
        \App\Models\SchoolStream::firstOrCreate(['name' => 'General'], ['is_active' => true]);
        \App\Models\SchoolStream::firstOrCreate(['name' => 'Science'], ['is_active' => true]);
        $section = SchoolSection::firstOrCreate(['stream' => 'General', 'name' => 'A'], ['capacity' => 40]);
        AcademicClass::firstOrCreate(
            ['name' => 'Grade 10', 'section' => 'A', 'academic_year' => '2026-2027'],
            ['grade_level' => 10, 'stream' => 'General', 'section_id' => $section->id, 'capacity' => 40]
        );
        $bulkClass = AcademicClass::where('name', 'Grade 10')->where('section', 'A')->where('academic_year', '2026-2027')->firstOrFail();
        SchoolFeeStructure::firstOrCreate(
            ['class_id' => $bulkClass->id, 'fee_title' => 'Tuition Fee', 'academic_year' => '2026-2027'],
            ['fee_type' => 'tuition', 'amount' => 1000, 'due_frequency' => 'monthly']
        );

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'principal@primearc.edu',
            'is_approved' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@primearc.edu',
            'is_approved' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student@primearc.edu',
            'is_approved' => true,
        ]);
    }

    public function test_admin_can_view_bulk_data_hub(): void
    {
        $response = $this->actingAs($this->admin)->get(route('school.bulk.index'));
        $response->assertStatus(200);
        $response->assertSee('Bulk Ingestion &amp; Export Hub', false);
        $response->assertSee('Student Admissions');
        $response->assertSee('Faculty &amp; Staff', false);
        $response->assertSee('Classes &amp; Subjects', false);
    }

    public function test_student_cannot_access_bulk_data_hub(): void
    {
        $response = $this->actingAs($this->student)->get(route('school.bulk.index'));
        $response->assertStatus(403);
    }

    public function test_can_download_sample_csv_templates(): void
    {
        foreach (['students', 'staff', 'classes_subjects'] as $type) {
            $response = $this->actingAs($this->admin)->get(route('school.bulk.sample', $type));
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $response->assertHeader('Content-Disposition', "attachment; filename=\"sample_{$type}_import_template.csv\"");
        }
    }

    public function test_bulk_import_students_from_csv(): void
    {
        $csvContent = implode("\n", [
            'Admission No,Roll No,Class,Section,Student Name,Email,Phone,Guardian Name,Guardian Phone,Guardian Email,Blood Group',
            'ADM-2026-9001,11,Grade 10,A,Kabir Khan,kabir.khan@test.com,+919988776655,Rashid Khan,+919988776600,rashid@test.com,O+',
            'ADM-2026-9002,12,Grade 10,A,Pooja Sharma,pooja.sharma@test.com,+919988776656,Mahesh Sharma,+919988776601,mahesh@test.com,B+',
        ]);

        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('school.bulk.students.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('school.bulk.index', ['tab' => 'students']));
        $response->assertSessionHas('success');

        // Assert database records
        $this->assertDatabaseHas('users', [
            'name' => 'Kabir Khan',
            'email' => 'kabir.khan@test.com',
            'admission_number' => 'ADM-2026-9001',
            'roll_number' => 11,
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Pooja Sharma',
            'email' => 'pooja.sharma@test.com',
            'admission_number' => 'ADM-2026-9002',
            'roll_number' => 12,
            'role' => 'student',
        ]);

        $createdClass = AcademicClass::where('name', 'Grade 10')->where('section', 'A')->first();
        $this->assertNotNull($createdClass);

        $kabir = User::where('email', 'kabir.khan@test.com')->first();
        $this->assertEquals($createdClass->id, $kabir->current_class_id);

        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $createdClass->id,
            'student_id' => $kabir->id,
            'status' => 'active',
        ]);
    }


    public function test_bulk_student_import_does_not_create_unknown_class_or_placeholder_data(): void
    {
        $csvContent = "Admission No,Roll No,Class,Section,Student Name,Email,Guardian Name,Guardian Phone,Guardian Email\n" .
            "ADM-UNKNOWN-1,1,Unconfigured Grade, A,Test Student,test.student@example.com,Test Guardian,9876543210,guardian@example.com\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('school.bulk.students.import'), ['csv_file' => $file]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('users', ['email' => 'test.student@example.com']);
        $this->assertDatabaseMissing('academic_classes', ['name' => 'Unconfigured Grade']);
    }

    public function test_teacher_cannot_run_bulk_student_import(): void
    {
        $csvContent = "Admission No,Roll No,Class,Section,Student Name,Email,Guardian Name,Guardian Phone,Guardian Email\n" .
            "ADM-TEACHER-1,1,Grade 10,A,Test Student,teacher.import@example.com,Test Guardian,9876543210,guardian@example.com\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $this->actingAs($this->teacher)
            ->post(route('school.bulk.students.import'), ['csv_file' => $file])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'teacher.import@example.com']);
    }

    public function test_bulk_import_staff_from_csv(): void
    {
        $csvContent = implode("\n", [
            'Name,Email,Phone,Role,Employee ID,Designation',
            'Prof. Robert Lang,robert.lang@test.com,+919811122233,teacher,FAC-901,Senior Chemistry Professor',
            'Dean Alice Cooper,alice.cooper@test.com,+919811122234,admin,ADM-901,Campus Dean of Operations',
        ]);

        $file = UploadedFile::fake()->createWithContent('faculty.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('school.bulk.staff.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('school.bulk.index', ['tab' => 'staff']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Prof. Robert Lang',
            'email' => 'robert.lang@test.com',
            'role' => 'teacher',
            'student_id_number' => 'FAC-901',
            'is_approved' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Dean Alice Cooper',
            'email' => 'alice.cooper@test.com',
            'role' => 'admin',
            'student_id_number' => 'ADM-901',
            'is_approved' => true,
        ]);
    }

    public function test_bulk_import_classes_and_subjects_from_csv(): void
    {
        SchoolSection::firstOrCreate(['stream' => 'Science', 'name' => 'A'], ['capacity' => 40]);
        \App\Models\SubjectMaster::firstOrCreate(
            ['name' => 'Advanced Biology', 'stream' => 'Science'],
            ['code' => 'BIO', 'min_grade' => 11, 'max_grade' => 12, 'default_periods' => 6, 'is_active' => true]
        );
        \App\Models\SubjectMaster::firstOrCreate(
            ['name' => 'Organic Chemistry', 'stream' => 'Science'],
            ['code' => 'CHEM', 'min_grade' => 11, 'max_grade' => 12, 'default_periods' => 6, 'is_active' => true]
        );

        $csvContent = implode("\n", [
            'Grade Level,Class Name,Section,Stream,Room Number,Subject Name,Subject Code,Weekly Periods',
            '11,Class 11 Science,A,Science,Lab 3,Advanced Biology,BIO-11,6',
            '11,Class 11 Science,A,Science,Lab 3,Organic Chemistry,CHEM-11,6',
        ]);

        $file = UploadedFile::fake()->createWithContent('curriculum.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('school.bulk.classes-subjects.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('school.bulk.index', ['tab' => 'classes_subjects']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_classes', [
            'name' => 'Class 11 Science',
            'section' => 'A',
            'grade_level' => 11,
            'room_number' => 'Lab 3',
        ]);

        $cls = AcademicClass::where('name', 'Class 11 Science')->first();

        $this->assertDatabaseHas('academic_subjects', [
            'class_id' => $cls->id,
            'name' => 'Advanced Biology',
            'code' => 'BIO-11',
            'weekly_periods' => 6,
        ]);

        $this->assertDatabaseHas('academic_subjects', [
            'class_id' => $cls->id,
            'name' => 'Organic Chemistry',
            'code' => 'CHEM-11',
            'weekly_periods' => 6,
        ]);
    }

    public function test_bulk_export_streams(): void
    {
        // 1. Student export
        $response = $this->actingAs($this->admin)->get(route('school.bulk.students.export'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 2. Staff export
        $response = $this->actingAs($this->admin)->get(route('school.bulk.staff.export'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 3. Classes & Subjects export
        $response = $this->actingAs($this->admin)->get(route('school.bulk.classes-subjects.export'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_school_onboarding_wizard_step1_database_reset(): void
    {
        // Clear previous classes and create single sample class to ensure reset works
        AcademicClass::query()->delete();
        AcademicClass::create([
            'name' => 'Demo Class',
            'section' => 'A',
            'grade_level' => 5,
            'academic_year' => '2026-27',
        ]);

        $this->assertEquals(1, AcademicClass::count());

        $response = $this->actingAs($this->admin)->post(route('school.onboarding.reset-database'));
        $response->assertRedirect(route('school.onboarding.index', ['step' => 2]));
        $response->assertSessionHas('success');

        $this->assertEquals(0, AcademicClass::count());
        $this->assertDatabaseHas('users', ['email' => $this->admin->email, 'role' => 'admin']);
    }

    public function test_school_onboarding_wizard_step2_save_profile(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.onboarding.save-profile'), [
            'institute_name' => 'St. Xavier Public School',
            'campus_name' => 'Heritage Campus',
            'affiliation_number' => 'CBSE-99881',
            'academic_session' => '2026-27',
            'principal_name' => 'Fr. Thomas K.',
            'institution_phone' => '+91 99000 11223',
            'institution_email' => 'principal@stxavier.edu',
            'institution_address' => '12 Park Road, City Center',
            'motto' => 'Excellence in Truth',
            'currency_symbol' => '₹',
        ]);

        $response->assertRedirect(route('school.onboarding.index', ['step' => 3]));
        $this->assertEquals('St. Xavier Public School', AcademySetting::get('institute_name'));
        $this->assertEquals('2026-27', AcademySetting::get('academic_session'));
    }

    public function test_school_onboarding_wizard_apply_k12_presets(): void
    {
        AcademicSubject::query()->delete();
        AcademicClass::query()->delete();
        \App\Models\SubjectMaster::query()->delete();
        \App\Models\SubjectMaster::firstOrCreate(
            ['name' => 'English Language & Lit', 'stream' => 'General'],
            ['code' => 'ENG', 'min_grade' => 1, 'max_grade' => 12, 'default_periods' => 6, 'is_active' => true]
        );
        \App\Models\SubjectMaster::firstOrCreate(
            ['name' => 'Mathematics', 'stream' => 'General'],
            ['code' => 'MATH', 'min_grade' => 1, 'max_grade' => 12, 'default_periods' => 6, 'is_active' => true]
        );
        \App\Models\SubjectMaster::firstOrCreate(
            ['name' => 'Science (Physics, Chem, Bio)', 'stream' => 'General'],
            ['code' => 'SCI', 'min_grade' => 1, 'max_grade' => 12, 'default_periods' => 6, 'is_active' => true]
        );

        $response = $this->actingAs($this->admin)->post(route('school.onboarding.k12-presets'), [
            'board' => 'CBSE',
        ]);

        $response->assertRedirect(route('school.onboarding.index', ['step' => 4]));

        // Check that 12 classes were initialized
        $this->assertEquals(12, AcademicClass::count());

        // Check standard primary and secondary subjects exist
        $class1 = AcademicClass::where('grade_level', 1)->first();
        $this->assertNotNull($class1);
        $this->assertTrue($class1->subjects()->where('name', 'English Language & Lit')->exists());
        $this->assertTrue($class1->subjects()->where('name', 'Mathematics')->exists());

        $class10 = AcademicClass::where('grade_level', 10)->first();
        $this->assertNotNull($class10);
        $this->assertTrue($class10->subjects()->where('name', 'Science (Physics, Chem, Bio)')->exists());
    }

    public function test_school_onboarding_wizard_finish(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.onboarding.finish'));
        $response->assertRedirect(route('school.classes.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('true', AcademySetting::get('onboarding_completed'));
    }

    public function test_middleware_redirects_to_onboarding_when_not_completed(): void
    {
        // Onboarding is not completed by default
        AcademySetting::set('onboarding_completed', 'false');

        // Accessing dashboard or home or any non-exempt route should redirect to onboarding
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertRedirect(route('school.onboarding.index'));

        $response = $this->get(route('home'));
        $response->assertRedirect(route('school.onboarding.index'));

        // Once completed, access is restored
        AcademySetting::set('onboarding_completed', 'true');
        AcademySetting::set('academic_session', '2026-27');
        \App\Models\SchoolStream::firstOrCreate(['name' => 'General'], ['is_active' => true]);
        \App\Models\SchoolStream::firstOrCreate(['name' => 'Science'], ['is_active' => true]);
        $section = SchoolSection::firstOrCreate(['stream' => 'General', 'name' => 'A'], ['capacity' => 40]);
        AcademicClass::firstOrCreate(
            ['name' => 'Grade 10', 'section' => 'A', 'academic_year' => '2026-2027'],
            ['grade_level' => 10, 'stream' => 'General', 'section_id' => $section->id, 'capacity' => 40]
        );
        $bulkClass = AcademicClass::where('name', 'Grade 10')->where('section', 'A')->where('academic_year', '2026-2027')->firstOrFail();
        SchoolFeeStructure::firstOrCreate(
            ['class_id' => $bulkClass->id, 'fee_title' => 'Tuition Fee', 'academic_year' => '2026-2027'],
            ['fee_type' => 'tuition', 'amount' => 1000, 'due_frequency' => 'monthly']
        );
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_onboarding_resumes_at_correct_saved_step(): void
    {
        AcademySetting::set('onboarding_completed', 'false');
        AcademySetting::set('onboarding_current_step', '3');

        $response = $this->actingAs($this->admin)->get(route('school.onboarding.index'));
        $response->assertStatus(200);
        $response->assertSee('Step 3: Academic Curriculum Setup');
    }

    public function test_first_time_installer_allows_admin_creation_when_zero_admins(): void
    {
        // Delete all users to simulate fresh zero-admin installation
        User::query()->delete();
        $this->assertEquals(0, User::count());

        $response = $this->get(route('school.onboarding.index'));
        $response->assertStatus(200);
        $response->assertSee('Create Master Super Administrator Account');

        // Create Master Administrator via onboarding step 1
        $postResponse = $this->post(route('school.onboarding.create-admin'), [
            'installer_key' => config('school.installer_key'),
            'admin_name' => 'Campus Director',
            'admin_email' => 'director@academy.edu',
            'admin_password' => 'secretPassword123!',
            'admin_password_confirmation' => 'secretPassword123!',
        ]);

        $postResponse->assertRedirect(route('school.onboarding.index', ['step' => 2]));
        $this->assertDatabaseHas('users', [
            'name' => 'Campus Director',
            'email' => 'director@academy.edu',
            'role' => 'admin',
        ]);
        $this->assertTrue(auth()->check());
    }
}
