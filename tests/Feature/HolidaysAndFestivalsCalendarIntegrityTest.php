<?php

namespace Tests\Feature;

use App\Models\AcademicHoliday;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidaysAndFestivalsCalendarIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        AcademySetting::set('academic_session', '2026-2027');

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'is_approved' => true,
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'is_approved' => true,
        ]);
    }

    public function test_admin_can_create_update_and_delete_holiday(): void
    {
        // 1. Create Holiday
        $response = $this->actingAs($this->admin)->post(route('school.holidays.store'), [
            'name' => 'National Science Day Observance',
            'holiday_date' => '2026-02-28',
            'type' => 'festival',
            'badge_icon' => '🔬',
            'description' => 'Honoring discovery of Raman Effect',
            'is_instructional_off' => 1,
        ]);

        $response->assertRedirect(route('school.holidays.index', ['year' => 2026]));
        $response->assertSessionHas('success');

        $holiday = AcademicHoliday::where('name', 'National Science Day Observance')->first();
        $this->assertNotNull($holiday);
        $this->assertEquals('2026-02-28', $holiday->holiday_date->toDateString());
        $this->assertEquals('2026-2027', $holiday->academic_year);

        // 2. Update Holiday
        $updateRes = $this->actingAs($this->admin)->put(route('school.holidays.update', $holiday->id), [
            'name' => 'National Science Day & Robotics Expo',
            'holiday_date' => '2026-02-28',
            'end_date' => '2026-03-01',
            'type' => 'institutional',
            'badge_icon' => '🚀',
            'description' => 'Extended celebration with robotics symposium',
            'is_instructional_off' => 1,
        ]);

        $updateRes->assertRedirect(route('school.holidays.index', ['year' => 2026]));
        $holiday->refresh();
        $this->assertEquals('National Science Day & Robotics Expo', $holiday->name);
        $this->assertEquals('2026-03-01', $holiday->end_date->toDateString());

        // 3. Delete Holiday
        $delRes = $this->actingAs($this->admin)->delete(route('school.holidays.destroy', $holiday->id));
        $delRes->assertRedirect(route('school.holidays.index'));
        $this->assertDatabaseMissing('academic_holidays', ['id' => $holiday->id]);
    }

    public function test_server_side_overlap_prevention_for_instructional_off(): void
    {
        AcademicHoliday::create([
            'name' => 'Diwali Break',
            'holiday_date' => '2026-11-08',
            'end_date' => '2026-11-12',
            'type' => 'festival',
            'badge_icon' => '🪔',
            'is_instructional_off' => true,
            'academic_year' => '2026-2027',
        ]);

        // Attempt to create overlapping holiday
        $res = $this->actingAs($this->admin)->post(route('school.holidays.store'), [
            'name' => 'Conflict Holiday',
            'holiday_date' => '2026-11-10',
            'end_date' => '2026-11-15',
            'type' => 'vacation',
            'is_instructional_off' => 1,
        ]);

        $res->assertSessionHasErrors('holiday_date');
    }

    public function test_all_roles_can_view_holiday_calendar(): void
    {
        AcademicHoliday::create([
            'name' => 'Independence Day',
            'holiday_date' => '2026-08-15',
            'type' => 'national',
            'badge_icon' => '🇮🇳',
            'is_instructional_off' => true,
            'academic_year' => '2026-2027',
        ]);

        foreach ([$this->admin, $this->teacher, $this->student, $this->parent] as $user) {
            $response = $this->actingAs($user)->get(route('school.holidays.index', ['year' => 2026]));
            $response->assertStatus(200);
            $response->assertSee('Independence Day');
        }
    }

    public function test_non_admin_cannot_create_or_modify_holidays(): void
    {
        $holiday = AcademicHoliday::create([
            'name' => 'Republic Day',
            'holiday_date' => '2026-01-26',
            'type' => 'national',
            'is_instructional_off' => true,
            'academic_year' => '2026-2027',
        ]);

        foreach ([$this->teacher, $this->student, $this->parent] as $user) {
            $createRes = $this->actingAs($user)->post(route('school.holidays.store'), [
                'name' => 'Unauthorized Holiday',
                'holiday_date' => '2026-05-01',
                'type' => 'national',
            ]);
            $createRes->assertStatus(403);

            $updateRes = $this->actingAs($user)->put(route('school.holidays.update', $holiday->id), [
                'name' => 'Unauthorized Update',
                'holiday_date' => '2026-01-26',
                'type' => 'national',
            ]);
            $updateRes->assertStatus(403);

            $delRes = $this->actingAs($user)->delete(route('school.holidays.destroy', $holiday->id));
            $delRes->assertStatus(403);
        }
    }
}
