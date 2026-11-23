<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademyNotice;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\LibraryBook;
use App\Models\SchoolExpense;
use App\Models\SchoolIncome;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfixAdminDashboardSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@infixschool.edu',
            'name' => 'Dr. Infix Admin',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@infixschool.edu',
            'name' => 'Prof. Harrison',
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent@infixschool.edu',
            'name' => 'Martha Kent',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student@infixschool.edu',
            'name' => 'Clark Kent',
            'parent_id' => $this->parent->id,
        ]);
    }

    /**
     * Test that the 4 canonical Infix top summary cards render with exact tokens.
     */
    public function test_admin_dashboard_renders_four_canonical_infix_kpi_cards(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('white-box single-summery');
        $response->assertSee('gradient-color2');

        // Top 4 Infix Summary Cards
        $response->assertSee('Faculty');
        $response->assertSee('ti-id-badge');
        $response->assertSee('Students');
        $response->assertSee('ti-user');
        $response->assertSee('Parents');
        $response->assertSee('ti-heart');
        $response->assertSee('Staff');
        $response->assertSee('ti-briefcase');
        $response->assertSee('grid-cols-2 lg:grid-cols-4');
    }

    /**
     * Test that secondary ERP operational modules render on the admin dashboard.
     */
    public function test_admin_dashboard_renders_secondary_erp_cards(): void
    {
        // Seed some module data
        TransportVehicle::create([
            'vehicle_number' => 'INFIX-BUS-01',
            'vehicle_model' => 'Tata Starbus',
            'year_made' => '2024',
            'driver_name' => 'Ramesh Kumar',
            'driver_license' => 'DL-987654321',
            'driver_contact' => '+91 9876543210',
            'is_active' => true,
        ]);

        TransportRoute::create([
            'route_title' => 'North Campus Express',
            'fare' => 1200.00,
            'is_active' => true,
        ]);

        LibraryBook::create([
            'book_title' => 'Fundamentals of Physics',
            'book_number' => 'BK-101',
            'isbn_no' => '978-0123456789',
            'publisher_name' => 'Halliday & Resnick',
            'author_name' => 'Jearl Walker',
            'quantity' => 15,
        ]);

        SchoolIncome::create([
            'name' => 'Annual Sponsorship',
            'amount' => 45000.00,
            'date' => now()->toDateString(),
            'payment_method' => 'Bank',
        ]);

        SchoolExpense::create([
            'name' => 'Laboratory Equipment Repair',
            'amount' => 8500.00,
            'date' => now()->toDateString(),
            'payment_method' => 'UPI',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Fees Collection');
        $response->assertSee('ti-wallet');
        $response->assertSee('Operating Treasury');
        $response->assertSee('ti-money');
        $response->assertSee('Library Center');
        $response->assertSee('ti-book');
        $response->assertSee('Transport &amp; Hostel', false);
        $response->assertSee('ti-truck');
    }

    /**
     * Test that Infix interactive widgets (Calendar, Notice Board, Quick Actions, Tables, Charts) render.
     */
    public function test_admin_dashboard_renders_infix_widgets_and_tables(): void
    {
        AcademicHoliday::create([
            'name' => 'Founder Day Holiday',
            'holiday_date' => now()->addDays(3)->toDateString(),
            'description' => 'Institution celebration',
        ]);

        AcademyNotice::create([
            'author_id' => $this->admin->id,
            'title' => 'Annual Science Exhibition Notice',
            'content' => 'Submissions for science models are open until Friday.',
            'target_audience' => 'all',
            'published_at' => now(),
        ]);

        $class = AcademicClass::create([
            'name' => 'Grade 11 - Science',
            'grade_level' => 11,
            'section' => 'A',
            'stream' => 'Science',
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-INFIX-999',
            'student_id' => $this->student->id,
            'title' => 'Monthly Tuition Fee',
            'amount' => 4000.00,
            'total_due' => 4000.00,
            'paid_amount' => 4000.00,
            'status' => 'paid',
            'due_date' => now()->toDateString(),
        ]);

        FeePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $this->student->id,
            'amount' => 4000.00,
            'payment_method' => 'UPI',
            'receipt_number' => 'RCPT-INFIX-888',
            'transaction_reference' => 'TXN-UPI-777',
            'paid_at' => now(),
            'notes' => 'Full fee settlement',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();

        // Infix Calendar & Notice Board
        $response->assertSee('Calendar &bull;', false);
        $response->assertSee('Founder Day Holiday');
        $response->assertSee('Notice Board &amp; Circulars', false);
        $response->assertSee('Annual Science Exhibition Notice');

        // Recent Fee Collections Table
        $response->assertSee('Recent Fee Collections &amp; Receipts', false);
        $response->assertSee('Clark Kent');
        $response->assertSee('RCPT-INFIX-888');

        // Infix Shortcuts
        $response->assertSee('Quick Actions &amp; Shortcuts', false);
        $response->assertSee('Take Roll Call');
        $response->assertSee('Collect Fee');
        $response->assertSee('Generate Fee');
        $response->assertSee('Admit Scholar');

        // Chart Canvases
        $response->assertSee('id="chart-income-expense-trend"', false);
        $response->assertSee('id="chart-attendance-trend"', false);
    }

    /**
     * Test student is blocked from seeing admin executive components.
     */
    public function test_student_does_not_see_admin_infix_dashboard(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Dr. Infix Admin');
        $response->assertDontSee('Operating Treasury');
        $response->assertDontSee('Class Attendance Taken');
    }
}
