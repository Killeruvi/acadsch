<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\SchoolPeriodTimetable;
use App\Models\TeacherAssignmentAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAndSubjectAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher1;
    protected User $teacher2;
    protected User $student;
    protected User $parent;
    protected AcademicClass $class10A;
    protected AcademicClass $class10B;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Principal Sharma',
            'email' => 'principal@primearc.academy',
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacher1 = User::factory()->create([
            'name' => 'Dr. Vikramaditya Sen',
            'email' => 'vikram@primearc.academy',
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->teacher2 = User::factory()->create([
            'name' => 'Prof. Ananya Rao',
            'email' => 'ananya@primearc.academy',
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->student = User::factory()->create([
            'name' => 'Aryabhata',
            'role' => 'student',
            'is_approved' => true,
        ]);

        $this->parent = User::factory()->create([
            'name' => 'Parent Sharma',
            'role' => 'parent',
            'is_approved' => true,
        ]);

        $this->class10A = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'A',
            'room_number' => 'Room 101',
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        $this->class10B = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'B',
            'room_number' => 'Room 102',
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);
    }

    /**
     * 1. Test Admin-only access enforcement.
     */
    public function test_only_admin_can_access_teacher_and_subject_assignment(): void
    {
        // Unauthenticated access redirects to login
        $resGuest = $this->get(route('school.assignments.index'));
        $resGuest->assertRedirect(route('login'));

        // Student is forbidden (403)
        $resStudent = $this->actingAs($this->student)->get(route('school.assignments.index'));
        $resStudent->assertStatus(403);

        // Parent is forbidden (403)
        $resParent = $this->actingAs($this->parent)->get(route('school.assignments.index'));
        $resParent->assertStatus(403);

        // Teacher is forbidden (403)
        $resTeacher = $this->actingAs($this->teacher1)->get(route('school.assignments.index'));
        $resTeacher->assertStatus(403);

        // Admin is authorized (200 OK)
        $resAdmin = $this->actingAs($this->admin)->get(route('school.assignments.index'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Teacher &amp; Subject Assignment', false);
        $resAdmin->assertSee('Admin Master Panel');
        $resAdmin->assertSee('Conflict Guard: Active');
    }

    /**
     * 2. Test admin can assign teacher to subject for class/section.
     */
    public function test_admin_can_assign_teacher_to_subject_with_audit_log(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.assignments.subjects.store'), [
            'class_id' => $this->class10A->id,
            'name' => 'Physics',
            'code' => 'PHY-10A',
            'teacher_id' => $this->teacher1->id,
            'weekly_periods' => 6,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify subject created and mapped
        $this->assertDatabaseHas('academic_subjects', [
            'class_id' => $this->class10A->id,
            'name' => 'Physics',
            'code' => 'PHY-10A',
            'teacher_id' => $this->teacher1->id,
            'weekly_periods' => 6,
        ]);

        // Verify audit log entry
        $this->assertDatabaseHas('teacher_assignment_audits', [
            'admin_id' => $this->admin->id,
            'action' => 'assigned_subject',
            'teacher_id' => $this->teacher1->id,
            'class_id' => $this->class10A->id,
            'subject_name' => 'Physics',
        ]);
    }

    /**
     * 3. Test mapping teacher -> subject -> class with optional period/timetable link.
     */
    public function test_admin_can_assign_subject_with_timetable_period_link(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.assignments.subjects.store'), [
            'class_id' => $this->class10A->id,
            'name' => 'Chemistry',
            'code' => 'CHEM-10A',
            'teacher_id' => $this->teacher1->id,
            'weekly_periods' => 5,
            'link_timetable' => 1,
            'day_of_week' => 'Monday',
            'period_number' => 2,
            'room_number' => 'Chemistry Lab 1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check timetable slot created
        $this->assertDatabaseHas('school_period_timetables', [
            'class_id' => $this->class10A->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 2,
            'room_number' => 'Chemistry Lab 1',
        ]);

        // Verify audit log records timetable slot link
        $audit = TeacherAssignmentAudit::where('subject_name', 'Chemistry')->latest()->first();
        $this->assertNotNull($audit);
        $this->assertNotNull($audit->period_timetable_id);
    }

    /**
     * 4. Test view, edit, and remove subject assignments.
     */
    public function test_admin_can_edit_and_remove_subject_assignments(): void
    {
        $subject = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Biology',
            'code' => 'BIO-10A',
            'teacher_id' => $this->teacher1->id,
            'weekly_periods' => 4,
        ]);

        // 1. Edit: Reassign to teacher2 and update periods
        $editRes = $this->actingAs($this->admin)->put(route('school.assignments.subjects.update', $subject->id), [
            'name' => 'Advanced Biology',
            'code' => 'BIO-10A',
            'teacher_id' => $this->teacher2->id,
            'weekly_periods' => 6,
        ]);

        $editRes->assertRedirect();
        $this->assertDatabaseHas('academic_subjects', [
            'id' => $subject->id,
            'name' => 'Advanced Biology',
            'teacher_id' => $this->teacher2->id,
            'weekly_periods' => 6,
        ]);

        $this->assertDatabaseHas('teacher_assignment_audits', [
            'action' => 'updated_subject',
            'teacher_id' => $this->teacher2->id,
        ]);

        // 2. Unassign teacher
        $unassignRes = $this->actingAs($this->admin)->delete(route('school.assignments.subjects.destroy', $subject->id), [
            'delete_subject' => 0,
        ]);

        $unassignRes->assertRedirect();
        $subject->refresh();
        $this->assertNull($subject->teacher_id);

        $this->assertDatabaseHas('teacher_assignment_audits', [
            'action' => 'unassigned_teacher',
        ]);

        // 3. Delete subject entirely
        $deleteRes = $this->actingAs($this->admin)->delete(route('school.assignments.subjects.destroy', $subject->id), [
            'delete_subject' => 1,
        ]);

        $deleteRes->assertRedirect();
        $this->assertDatabaseMissing('academic_subjects', ['id' => $subject->id]);

        $this->assertDatabaseHas('teacher_assignment_audits', [
            'action' => 'removed_subject',
        ]);
    }

    /**
     * 5. Test Class Teacher (Homeroom) assignment and removal.
     */
    public function test_admin_can_assign_and_remove_class_teacher(): void
    {
        // 1. Assign Dr. Sen as Class Teacher of 10-A
        $assignRes = $this->actingAs($this->admin)->post(route('school.assignments.class-teacher.assign'), [
            'class_id' => $this->class10A->id,
            'teacher_id' => $this->teacher1->id,
        ]);

        $assignRes->assertRedirect();
        $this->class10A->refresh();
        $this->assertEquals($this->teacher1->id, $this->class10A->class_teacher_id);

        $this->assertDatabaseHas('teacher_assignment_audits', [
            'action' => 'assigned_class_teacher',
            'teacher_id' => $this->teacher1->id,
            'class_id' => $this->class10A->id,
        ]);

        // 2. Prevent duplicate assignment of the exact same class teacher
        $duplicateRes = $this->actingAs($this->admin)->post(route('school.assignments.class-teacher.assign'), [
            'class_id' => $this->class10A->id,
            'teacher_id' => $this->teacher1->id,
        ]);
        $duplicateRes->assertSessionHasErrors('teacher_id');

        // 3. Remove Class Teacher
        $removeRes = $this->actingAs($this->admin)->delete(route('school.assignments.class-teacher.remove', $this->class10A->id));
        $removeRes->assertRedirect();
        $this->class10A->refresh();
        $this->assertNull($this->class10A->class_teacher_id);

        $this->assertDatabaseHas('teacher_assignment_audits', [
            'action' => 'removed_class_teacher',
            'class_id' => $this->class10A->id,
        ]);
    }

    /**
     * 6. Test conflict prevention: Same teacher cannot be scheduled in two classes at the same period.
     */
    public function test_prevent_conflicting_teacher_timetable_schedule(): void
    {
        $subjectA = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10A',
            'teacher_id' => $this->teacher1->id,
        ]);

        $subjectB = AcademicSubject::create([
            'class_id' => $this->class10B->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10B',
            'teacher_id' => $this->teacher1->id,
        ]);

        // 1. Assign Teacher 1 to Class 10-A on Monday Period 3
        SchoolPeriodTimetable::create([
            'class_id' => $this->class10A->id,
            'subject_id' => $subjectA->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 3,
            'start_time' => '10:00',
            'end_time' => '10:45',
            'room_number' => 'Room 101',
        ]);

        // 2. Attempt to assign Teacher 1 to Class 10-B on Monday Period 3 (Conflicting period!)
        $conflictRes = $this->actingAs($this->admin)->post(route('school.assignments.timetable.store'), [
            'class_id' => $this->class10B->id,
            'subject_id' => $subjectB->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 3,
            'room_number' => 'Room 102',
        ]);

        // Must fail with schedule conflict error
        $conflictRes->assertSessionHasErrors('teacher_id');

        // Confirm Class 10-B was NOT scheduled for Teacher 1 in that slot
        $this->assertDatabaseMissing('school_period_timetables', [
            'class_id' => $this->class10B->id,
            'period_number' => 3,
            'teacher_id' => $this->teacher1->id,
        ]);
    }

    /**
     * 7. Test collision prevention: Class cannot have two conflicting subjects scheduled simultaneously.
     */
    public function test_prevent_conflicting_class_period_collision(): void
    {
        $math = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10A',
            'teacher_id' => $this->teacher1->id,
        ]);

        $science = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Science',
            'code' => 'SCI-10A',
            'teacher_id' => $this->teacher2->id,
        ]);

        // 1. Class 10-A has Math with Teacher 1 on Tuesday Period 2
        SchoolPeriodTimetable::create([
            'class_id' => $this->class10A->id,
            'subject_id' => $math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Tuesday',
            'period_number' => 2,
            'start_time' => '09:15',
            'end_time' => '10:00',
            'room_number' => 'Room 101',
        ]);

        // 2. Attempt to schedule Science with Teacher 2 for Class 10-A in the same Tuesday Period 2 slot
        $collisionRes = $this->actingAs($this->admin)->post(route('school.assignments.timetable.store'), [
            'class_id' => $this->class10A->id,
            'subject_id' => $science->id,
            'teacher_id' => $this->teacher2->id,
            'day_of_week' => 'Tuesday',
            'period_number' => 2,
            'room_number' => 'Room 101',
        ]);

        $collisionRes->assertSessionHasErrors('period_number');
    }

    /**
     * 8. Test duplicate subject assignment prevention.
     */
    public function test_prevent_duplicate_subject_assignment(): void
    {
        $subject = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'English Literature',
            'code' => 'ENG-10A',
            'teacher_id' => $this->teacher1->id,
            'weekly_periods' => 5,
        ]);

        // Attempt to re-assign same teacher to the exact same subject
        $dupRes = $this->actingAs($this->admin)->post(route('school.assignments.subjects.store'), [
            'class_id' => $this->class10A->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher1->id,
        ]);

        $dupRes->assertSessionHasErrors('teacher_id');
    }

    /**
     * 9. Test room collision prevention.
     */
    public function test_prevent_room_collision_during_same_period(): void
    {
        $subA = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Robotics',
            'code' => 'ROB-10A',
            'teacher_id' => $this->teacher1->id,
        ]);

        $subB = AcademicSubject::create([
            'class_id' => $this->class10B->id,
            'name' => 'Electronics',
            'code' => 'ELEC-10B',
            'teacher_id' => $this->teacher2->id,
        ]);

        // Class 10-A books Innovation Lab for Friday Period 4
        SchoolPeriodTimetable::create([
            'class_id' => $this->class10A->id,
            'subject_id' => $subA->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Friday',
            'period_number' => 4,
            'start_time' => '10:45',
            'end_time' => '11:30',
            'room_number' => 'Innovation Lab',
        ]);

        // Class 10-B tries to book the same Innovation Lab for Friday Period 4
        $res = $this->actingAs($this->admin)->post(route('school.assignments.timetable.store'), [
            'class_id' => $this->class10B->id,
            'subject_id' => $subB->id,
            'teacher_id' => $this->teacher2->id,
            'day_of_week' => 'Friday',
            'period_number' => 4,
            'room_number' => 'Innovation Lab',
        ]);

        $res->assertSessionHasErrors('room_number');
    }

    /**
     * 10. Test UI renders dense tabs, small cards, minimal padding, and zero banners.
     */
    public function test_ui_renders_dense_layout_and_tabs(): void
    {
        AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Geography',
            'code' => 'GEO-10A',
            'teacher_id' => $this->teacher1->id,
        ]);

        $res = $this->actingAs($this->admin)->get(route('school.assignments.index', ['tab' => 'subjects']));

        $res->assertStatus(200);
        $res->assertSee('Geography');
        $res->assertSee('GEO-10A');
        $res->assertSee('Dr. Vikramaditya Sen');
        $res->assertSee('Subject Mappings');
        $res->assertSee('Class Teachers');
        $res->assertSee('Timetable Slots');
        $res->assertSee('Audit Log');
    }

    /**
     * 11. Test search with joined tables does not cause ambiguous column SQL errors.
     */
    public function test_assignments_index_search_with_class_id_filter_succeeds_without_sql_ambiguity(): void
    {
        AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Environmental Studies (EVS)',
            'code' => 'EVS-10A',
            'teacher_id' => $this->teacher1->id,
        ]);

        $res = $this->actingAs($this->admin)->get(route('school.assignments.index', [
            'tab' => 'subjects',
            'class_id' => $this->class10A->id,
            'search' => 'Environmental Studies (EVS)',
        ]));

        $res->assertStatus(200);
        $res->assertSee('Environmental Studies (EVS)');
        $res->assertSee('EVS-10A');
    }
}
