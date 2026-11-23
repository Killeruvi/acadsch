<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentPortalTabStreamFixTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;
    private User $child1;
    private User $child2;
    private AcademicClass $class11Sci;
    private AcademicClass $class11Comm;
    private AcademicClass $class11Arts;
    private AcademicClass $class12Sci;

    protected function setUp(): void
    {
        parent::setUp();

        // Create secondary / higher-secondary classes with streams
        $this->class11Sci = AcademicClass::create([
            'name' => 'Class 11',
            'grade_level' => 11,
            'stream' => 'Science',
            'section' => 'Science',
            'room_number' => 'Lab 1',
            'capacity' => 40,
        ]);

        $this->class11Comm = AcademicClass::create([
            'name' => 'Class 11',
            'grade_level' => 11,
            'stream' => 'Commerce',
            'section' => 'Commerce',
            'room_number' => 'Room 201',
            'capacity' => 40,
        ]);

        $this->class11Arts = AcademicClass::create([
            'name' => 'Class 11',
            'grade_level' => 11,
            'stream' => 'Arts',
            'section' => 'Arts',
            'room_number' => 'Room 202',
            'capacity' => 40,
        ]);

        $this->class12Sci = AcademicClass::create([
            'name' => 'Class 12',
            'grade_level' => 12,
            'stream' => 'Science',
            'section' => 'Science',
            'room_number' => 'Lab 2',
            'capacity' => 40,
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'guardian_portal@primearc.edu',
            'is_approved' => true,
        ]);

        $this->child1 = User::factory()->create([
            'role' => 'student',
            'name' => 'Aarav Patel',
            'email' => 'aarav@primearc.edu',
            'parent_id' => $this->parent->id,
            'current_class_id' => $this->class11Sci->id,
            'is_approved' => true,
        ]);

        $this->child2 = User::factory()->create([
            'role' => 'student',
            'name' => 'Diya Patel',
            'email' => 'diya@primearc.edu',
            'parent_id' => $this->parent->id,
            'current_class_id' => $this->class11Comm->id,
            'is_approved' => true,
        ]);
    }

    /**
     * Test 1: Class Display Fix - Stream is displayed alongside class name.
     */
    public function test_classes_display_with_stream_and_no_naked_duplicates(): void
    {
        $this->assertEquals('Class 11 – Science', $this->class11Sci->display_name);
        $this->assertEquals('Class 11 – Commerce', $this->class11Comm->display_name);
        $this->assertEquals('Class 11 – Arts', $this->class11Arts->display_name);
        $this->assertEquals('Class 12 – Science', $this->class12Sci->display_name);

        // Deduplication helper returns unique class+stream combinations
        $deduped = AcademicClass::getDeduplicatedClasses();
        $this->assertTrue($deduped->contains('id', $this->class11Sci->id));
        $this->assertTrue($deduped->contains('id', $this->class11Comm->id));
        $this->assertTrue($deduped->contains('id', $this->class11Arts->id));
    }

    /**
     * Test 2: Parent portal dashboard displays child's class with stream.
     */
    public function test_parent_dashboard_renders_class_with_stream(): void
    {
        $response = $this->actingAs($this->parent)->get(route('school.parent.dashboard'));
        $response->assertOk();
        $response->assertSee('Class 11 – Science');
    }

    /**
     * Test 3: Tab Switching is client-side and tabs have increased touch-friendly sizes.
     */
    public function test_parent_portal_has_large_touch_tabs_and_client_side_switching(): void
    {
        $response = $this->actingAs($this->parent)->get(route('school.parent.dashboard'));
        $response->assertOk();

        // Client side Alpine.js tab switcher
        $response->assertSee('setTab(\'overview\')', false);
        $response->assertSee('setTab(\'attendance\')', false);
        $response->assertSee('setTab(\'leaves\')', false);
        $response->assertSee('setTab(\'exams\')', false);
        $response->assertSee('setTab(\'timetable\')', false);
        $response->assertSee('setTab(\'fees\')', false);

        // Large tap targets
        $response->assertSee('min-h-[42px]');
    }

    /**
     * Test 4: Top bug/error banner is removed for parent portal.
     */
    public function test_top_banner_not_rendered_for_parents(): void
    {
        $response = $this->actingAs($this->parent)->get(route('school.parent.dashboard'));
        $response->assertOk();
        $response->assertDontSee('id="urgent-pinned-banner"', false);
    }

    /**
     * Test 5: Multi-student selector updates active child and persists across session.
     */
    public function test_multi_student_selector_updates_all_data(): void
    {
        // Initial view defaults to child1 (Aarav Patel)
        $response1 = $this->actingAs($this->parent)->get(route('school.parent.dashboard'));
        $response1->assertOk();
        $response1->assertSee('Aarav Patel');
        $response1->assertSee('Class 11 – Science');

        // Select child2 (Diya Patel) via child_id parameter
        $response2 = $this->actingAs($this->parent)->get(route('school.parent.dashboard', ['child_id' => $this->child2->id]));
        $response2->assertOk();
        $response2->assertSee('Diya Patel');
        $response2->assertSee('Class 11 – Commerce');
        $this->assertEquals($this->child2->id, session('parent_active_student_id'));

        // Subsequent page access retains child2 as active student across pages
        $response3 = $this->actingAs($this->parent)->get(route('school.fees.index'));
        $response3->assertOk();
        $this->assertEquals($this->child2->id, session('parent_active_student_id'));
    }

    /**
     * Test 6: Fee invoices load cleanly without undefined relationship exceptions.
     */
    public function test_fee_invoices_load_without_relation_not_found_exception(): void
    {
        \App\Models\FeeInvoice::create([
            'invoice_number' => 'INV-2026-TEST',
            'student_id' => $this->child1->id,
            'title' => 'Term 1 Tuition Fee',
            'amount' => 5000.00,
            'total_due' => 5000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($this->parent)->get(route('school.parent.dashboard'));
        $response->assertOk();
        $response->assertSee('Term 1 Tuition Fee');
        $response->assertSee('INV-2026-TEST');
    }

    /**
     * Test 7: Mobile bottom tabs and top nav tabs are 100% consistent with Parent Portal.
     */
    public function test_mobile_bottom_tabs_consistent_with_parent_portal_tabs(): void
    {
        $response = $this->actingAs($this->parent)->get(route('school.parent.dashboard'));
        $response->assertOk();

        // 1. Mobile bottom tabs render role-based tabs with real route navigation
        $response->assertSee('id="mobile-bottom-tabs"', false);
        $response->assertSee('Attendance');
        $response->assertSee('Fees');
        $response->assertSee('Leaves');
        $response->assertSee('More');

        // 2. No outdated inconsistent violet color or "Parent Hub" naming in bottom nav
        $response->assertDontSee('text-violet-400');
        $response->assertDontSee('Parent Hub');
    }
}
