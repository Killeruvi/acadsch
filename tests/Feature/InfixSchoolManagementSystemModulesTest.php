<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\ClassEnrollment;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\BookIssue;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\FrontOfficeCall;
use App\Models\FrontOfficePostalDispatch;
use App\Models\FrontOfficeVisitor;
use App\Models\LibraryBook;
use App\Models\SchoolExpense;
use App\Models\SchoolIncome;
use App\Models\StaffPayroll;
use App\Models\StudyMaterial;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfixSchoolManagementSystemModulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $student;
    protected AcademicClass $class9;
    protected AcademicClass $class10;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin.infix@academy.edu',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher.infix@academy.edu',
        ]);

        $this->class9 = AcademicClass::create([
            'name' => 'Class 9',
            'section' => 'A',
            'grade_level' => 9,
            'academic_year' => '2026-2027',
        ]);

        $this->class10 = AcademicClass::create([
            'name' => 'Class 10',
            'section' => 'A',
            'grade_level' => 10,
            'academic_year' => '2027-2028',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student.infix@academy.edu',
            'current_class_id' => $this->class9->id,
        ]);
    }

    public function test_library_book_catalog_issue_and_return_cycle(): void
    {
        // 1. Add book
        $res = $this->actingAs($this->admin)->post(route('school.library.books.store'), [
            'book_title' => 'Advanced Cellular Biology',
            'book_number' => 'BK-BIO-001',
            'isbn_number' => '978-0123456789',
            'author_name' => 'Dr. Campbell',
            'publisher_name' => 'Pearson',
            'rack_number' => 'Rack B-02',
            'quantity' => 5,
            'book_price' => 850.00,
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('library_books', [
            'book_number' => 'BK-BIO-001',
            'quantity' => 5,
            'available_quantity' => 5,
        ]);

        $book = LibraryBook::where('book_number', 'BK-BIO-001')->first();

        // 2. Issue book to student
        $issueRes = $this->actingAs($this->admin)->post(route('school.library.issue'), [
            'book_id' => $book->id,
            'member_id' => $this->student->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'note' => 'Issued for semester coursework',
        ]);

        $issueRes->assertRedirect();
        $this->assertDatabaseHas('book_issues', [
            'book_id' => $book->id,
            'member_id' => $this->student->id,
            'status' => 'issued',
        ]);

        $book->refresh();
        $this->assertEquals(4, $book->available_quantity);

        // 3. Return book
        $issue = BookIssue::first();
        $returnRes = $this->actingAs($this->admin)->post(route('school.library.return', $issue->id));
        $returnRes->assertRedirect();

        $issue->refresh();
        $this->assertEquals('returned', $issue->status);

        $book->refresh();
        $this->assertEquals(5, $book->available_quantity);

        // 4. Student can view library
        $viewRes = $this->actingAs($this->student)->get(route('school.library.index'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Advanced Cellular Biology');
    }

    public function test_transport_route_and_vehicle_management(): void
    {
        // 1. Add route
        $routeRes = $this->actingAs($this->admin)->post(route('school.transport.routes.store'), [
            'route_title' => 'City Center Shuttle',
            'start_point' => 'Central Station',
            'end_point' => 'Academy Campus',
            'fare' => 1500.00,
        ]);
        $routeRes->assertRedirect();
        $this->assertDatabaseHas('transport_routes', ['route_title' => 'City Center Shuttle']);

        $route = TransportRoute::first();

        // 2. Add vehicle
        $vehRes = $this->actingAs($this->admin)->post(route('school.transport.vehicles.store'), [
            'vehicle_number' => 'DL-01-EXP-5555',
            'vehicle_type' => 'bus',
            'capacity' => 42,
            'status' => 'active',
            'vehicle_model' => 'Ashok Leyland 42S',
            'driver_name' => 'Rajesh Kumar',
            'driver_phone' => '9876543210',
            'route_id' => $route->id,
        ]);
        $vehRes->assertRedirect();
        $this->assertDatabaseHas('transport_vehicles', ['vehicle_number' => 'DL-01-EXP-5555']);

        $indexRes = $this->actingAs($this->admin)->get(route('school.transport.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('City Center Shuttle');
        $indexRes->assertSee('Rajesh Kumar');
    }

    public function test_dormitory_and_room_management(): void
    {
        // 1. Create Dormitory
        $dormRes = $this->actingAs($this->admin)->post(route('school.dormitory.store'), [
            'dormitory_name' => 'C.V. Raman Science Block',
            'type' => 'boys',
            'intake_capacity' => 120,
            'address' => 'North Residential Complex',
        ]);
        $dormRes->assertRedirect();
        $this->assertDatabaseHas('dormitories', ['dormitory_name' => 'C.V. Raman Science Block']);

        $dorm = Dormitory::first();

        // 2. Add Room
        $roomRes = $this->actingAs($this->admin)->post(route('school.dormitory.rooms.store'), [
            'dormitory_id' => $dorm->id,
            'room_number' => 'B-101',
            'room_type' => 'double',
            'number_of_beds' => 2,
            'cost_per_bed' => 4500.00,
        ]);
        $roomRes->assertRedirect();
        $this->assertDatabaseHas('dormitory_rooms', ['room_number' => 'B-101', 'number_of_beds' => 2]);

        $viewRes = $this->actingAs($this->admin)->get(route('school.dormitory.index'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('C.V. Raman Science Block');
        $viewRes->assertSee('B-101');
    }

    public function test_institutional_accounts_income_and_expense_tracking(): void
    {
        // 1. Log Income
        $incRes = $this->actingAs($this->admin)->post(route('school.accounts.income.store'), [
            'name' => 'Science Exhibition Sponsor Grant',
            'income_head' => 'Event',
            'amount' => 25000.00,
            'date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);
        $incRes->assertRedirect();
        $this->assertDatabaseHas('school_incomes', ['amount' => 25000.00]);

        // 2. Log Expense
        $expRes = $this->actingAs($this->admin)->post(route('school.accounts.expense.store'), [
            'name' => 'High-Speed Campus Server Maintenance',
            'expense_head' => 'Equipment',
            'amount' => 8500.00,
            'date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'paid_to' => 'Server Cloud Systems',
        ]);
        $expRes->assertRedirect();
        $this->assertDatabaseHas('school_expenses', ['amount' => 8500.00]);

        $accRes = $this->actingAs($this->admin)->get(route('school.accounts.index'));
        $accRes->assertStatus(200);
        $accRes->assertSee('Science Exhibition Sponsor Grant');
        $accRes->assertSee('Server Cloud Systems');
    }

    public function test_staff_payroll_generation_and_disbursement(): void
    {
        $structureId = \Illuminate\Support\Facades\DB::table('staff_salary_structures')->insertGetId([
            'code' => 'TCH_STANDARD',
            'name' => 'Standard Teacher Grade 1',
            'basic_salary' => 40000.00,
            'dearness_allowance' => 5000.00,
            'hra' => 5000.00,
            'other_allowances' => 5000.00,
            'fixed_monthly_earnings' => 55000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = \Illuminate\Support\Facades\DB::table('staff_salary_assignments')->insertGetId([
            'staff_id' => $this->teacher->id,
            'salary_structure_id' => $structureId,
            'effective_from' => '2026-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payroll = StaffPayroll::create([
            'staff_id' => $this->teacher->id,
            'payroll_month' => '2026-10',
            'gross_salary' => 55000.00,
            'deductions' => 2000.00,
            'net_salary' => 53000.00,
            'payment_status' => 'generated',
            'approval_status' => 'approved',
            'generated_by' => $this->admin->id,
            'approved_by' => $this->admin->id,
            'salary_assignment_id' => $assignmentId,
            'calculation_snapshot' => ['components' => []],
        ]);

        $this->assertNotNull($payroll);
        $this->assertEquals(53000.00, (float)$payroll->net_salary);
        $this->assertEquals('generated', $payroll->payment_status);

        // Mark as paid
        $payRes = $this->actingAs($this->admin)->post(route('school.payroll.pay', $payroll->id), [
            'paid_on' => now()->toDateString(),
            'payment_reference' => 'TXN-PAYROLL-9988',
            'payment_method' => 'bank_transfer',
        ]);
        $payRes->assertRedirect();

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->payment_status);
        $this->assertNotNull($payroll->paid_at);

        // Teacher can view own payroll
        $teacherView = $this->actingAs($this->teacher)->get(route('school.payroll.index'));
        $teacherView->assertStatus(200);
        $teacherView->assertSee('53,000.00');
    }

    public function test_study_materials_download_center(): void
    {
        $this->class9->update(['class_teacher_id' => $this->teacher->id]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('thermo_formulas.pdf', 50, 'application/pdf');

        $uploadRes = $this->actingAs($this->teacher)->post(route('school.study-materials.store'), [
            'content_title' => 'Thermodynamics Chapter Formula Sheet',
            'content_type' => 'study_material',
            'class_id' => $this->class9->id,
            'available_for' => 'all',
            'material_file' => $file,
        ]);
        $uploadRes->assertRedirect();
        $this->assertDatabaseHas('study_materials', ['content_title' => 'Thermodynamics Chapter Formula Sheet']);

        $studentRes = $this->actingAs($this->student)->get(route('school.study-materials.index'));
        $studentRes->assertStatus(200);
        $studentRes->assertSee('Thermodynamics Chapter Formula Sheet');
    }

    public function test_front_office_visitor_call_and_postal_logging(): void
    {
        // 1. Visitor
        $visRes = $this->actingAs($this->admin)->post(route('school.front-office.visitor.store'), [
            'name' => 'Ananya Sen',
            'phone' => '9988776655',
            'purpose' => 'Admission Discussion',
            'person_to_meet' => 'Principal',
        ]);
        $visRes->assertRedirect();
        $this->assertDatabaseHas('front_office_visitors', ['name' => 'Ananya Sen']);

        // 2. Call
        $callRes = $this->actingAs($this->admin)->post(route('school.front-office.call.store'), [
            'name' => 'Guardian Verma',
            'phone' => '9123456789',
            'call_type' => 'incoming',
            'description' => 'Inquiry regarding transportation timings',
        ]);
        $callRes->assertRedirect();
        $this->assertDatabaseHas('front_office_calls', ['name' => 'Guardian Verma']);

        // 3. Postal
        $postRes = $this->actingAs($this->admin)->post(route('school.front-office.postal.store'), [
            'from_title' => 'CBSE Delhi Regional Office',
            'to_title' => 'School Principal',
            'type' => 'receive',
        ]);
        $postRes->assertRedirect();
        $this->assertDatabaseHas('front_office_postal_dispatches', ['from_title' => 'CBSE Delhi Regional Office']);

        $viewRes = $this->actingAs($this->admin)->get(route('school.front-office.index'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Ananya Sen');
    }

    public function test_student_promotion_and_session_rollover(): void
    {
        $this->assertEquals($this->class9->id, $this->student->current_class_id);
        ClassEnrollment::create([
            'class_id' => $this->class9->id, 'student_id' => $this->student->id,
            'roll_number' => 1, 'admission_number' => $this->student->admission_number,
            'academic_year' => '2026-2027', 'status' => 'active',
        ]);
        $attendanceSession = AttendanceSession::create([
            'class_id' => $this->class9->id, 'teacher_id' => $this->teacher->id,
            'session_date' => now()->toDateString(), 'title' => 'Promotion eligibility attendance',
        ]);
        AttendanceRecord::create([
            'session_id' => $attendanceSession->id, 'student_id' => $this->student->id, 'status' => 'present',
        ]);

        $indexRes = $this->actingAs($this->admin)->get(route('school.promotion.index', ['source_class_id' => $this->class9->id]));
        $indexRes->assertStatus(200);
        $indexRes->assertSee($this->student->name);

        $promoteRes = $this->actingAs($this->admin)->post(route('school.promotion.promote'), [
            'source_class_id' => $this->class9->id,
            'target_class_id' => $this->class10->id,
            'student_ids' => [$this->student->id],
        ]);
        $promoteRes->assertRedirect();

        $this->student->refresh();
        $this->assertEquals($this->class10->id, $this->student->current_class_id);
        $this->assertDatabaseHas('class_enrollments', ['class_id' => $this->class9->id, 'student_id' => $this->student->id, 'status' => 'promoted']);
        $this->assertDatabaseHas('class_enrollments', ['class_id' => $this->class10->id, 'student_id' => $this->student->id, 'academic_year' => '2027-2028', 'status' => 'active']);
    }
}
