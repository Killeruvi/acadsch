<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseLesson;
use App\Models\CourseLessonVideo;
use App\Models\CourseModule;
use App\Models\DailyDpp;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCrudCompleteAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createTeacher(): User
    {
        return User::factory()->create(['role' => 'teacher']);
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'title' => 'Test Course',
            'code' => 'TC-101',
            'category' => 'Engineering',
            'credits' => 4,
            'capacity' => 30,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_update_course_details(): void
    {
        $teacher = $this->createTeacher();
        $course = $this->createCourse($teacher);

        $res = $this->actingAs($teacher)->put(route('courses.update', $course->id), [
            'code' => 'TC-999',
            'title' => 'Advanced Applied Engineering',
            'category' => 'Advanced Sciences',
            'credits' => 5,
            'capacity' => 45,
            'status' => 'active',
            'description' => 'Updated syllabus description.',
        ]);

        $res->assertRedirect();
        $course->refresh();
        $this->assertEquals('TC-999', $course->code);
        $this->assertEquals('Advanced Applied Engineering', $course->title);
        $this->assertEquals(5, $course->credits);
        $this->assertEquals(45, $course->capacity);
    }

    public function test_teacher_can_delete_course(): void
    {
        $teacher = $this->createTeacher();
        $course = $this->createCourse($teacher);

        $res = $this->actingAs($teacher)->delete(route('courses.destroy', $course->id));

        $res->assertRedirect(route('courses.index'));
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_teacher_can_update_and_delete_lesson_videos(): void
    {
        $teacher = $this->createTeacher();
        $course = $this->createCourse($teacher);
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'M1', 'order_index' => 1, 'week_number' => 1]);
        $lesson = CourseLesson::create(['course_id' => $course->id, 'module_id' => $module->id, 'lesson_number' => 1, 'title' => 'L1', 'week_number' => 1, 'day_of_week' => 1]);

        $video = CourseLessonVideo::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'Original Video Title',
            'video_url' => 'https://www.youtube.com/watch?v=111111',
            'duration_minutes' => 20,
            'order_index' => 1,
        ]);

        // 1. Update Video
        $this->actingAs($teacher)->put(route('courses.videos.update', [$course->id, $video->id]), [
            'title' => 'Updated Masterclass Drill',
            'video_url' => 'https://www.youtube.com/watch?v=222222',
            'duration_minutes' => 35,
        ])->assertRedirect();

        $video->refresh();
        $this->assertEquals('Updated Masterclass Drill', $video->title);
        $this->assertEquals('https://www.youtube.com/watch?v=222222', $video->video_url);
        $this->assertEquals(35, $video->duration_minutes);

        // 2. Delete Video
        $this->actingAs($teacher)->delete(route('courses.videos.destroy', [$course->id, $video->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('course_lesson_videos', ['id' => $video->id]);
    }

    public function test_teacher_can_remove_pdf_note_from_lesson(): void
    {
        $teacher = $this->createTeacher();
        $course = $this->createCourse($teacher);
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'M1', 'order_index' => 1, 'week_number' => 1]);
        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_number' => 1,
            'week_number' => 1,
            'day_of_week' => 1,
            'title' => 'L1',
            'pdf_url' => '/uploads/course_notes/sample_handout.pdf',
        ]);

        $this->actingAs($teacher)->delete(route('courses.lessons.destroy-note', [$course->id, $lesson->id]))
            ->assertRedirect();

        $lesson->refresh();
        $this->assertNull($lesson->pdf_url);
    }

    public function test_dpp_creation_redirects_to_editor_and_deletion_preserves_course(): void
    {
        $teacher = $this->createTeacher();
        $course = $this->createCourse($teacher);

        // 1. Store Course DPP -> redirects to dpp.edit
        $res = $this->actingAs($teacher)->post(route('courses.dpps.store', $course->id), [
            'title' => 'Sprint 1 DPP Set',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'standard',
            'duration_minutes' => 30,
            'total_marks' => 25,
        ]);

        $dpp = DailyDpp::where('course_id', $course->id)->first();
        $this->assertNotNull($dpp);
        $res->assertRedirect(route('dpp.edit', $dpp->id));

        // 2. Delete Course DPP -> redirects to course show dpp tab
        $delRes = $this->actingAs($teacher)->delete(route('dpp.destroy', $dpp->id));
        $delRes->assertRedirect(route('courses.show', ['id' => $course->id, 'tab' => 'dpp']));
        $this->assertDatabaseMissing('daily_dpps', ['id' => $dpp->id]);
    }

    public function test_schedule_crud_and_toggle_cancel(): void
    {
        $teacher = $this->createTeacher();
        $course = $this->createCourse($teacher);

        $sch = Schedule::create([
            'course_id' => $course->id,
            'title' => 'Weekly Lab Drill',
            'day_of_week' => 'Wednesday',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'delivery_mode' => 'offline',
            'room' => 'Lab B-2',
        ]);

        // Toggle Cancel
        $this->actingAs($teacher)->post(route('schedules.toggle-cancel', $sch->id), ['reason' => 'Holiday'])
            ->assertRedirect();
        $sch->refresh();
        $this->assertTrue((bool) $sch->is_cancelled);
        $this->assertEquals('Holiday', $sch->cancellation_reason);

        // Update
        $this->actingAs($teacher)->put(route('schedules.update', $sch->id), [
            'title' => 'Advanced Robotics Session',
            'day_of_week' => 'Friday',
            'start_time' => '14:00',
            'end_time' => '15:30',
            'delivery_mode' => 'online',
        ])->assertRedirect();

        $sch->refresh();
        $this->assertEquals('Advanced Robotics Session', $sch->title);
        $this->assertEquals('Friday', $sch->day_of_week);

        // Delete
        $this->actingAs($teacher)->delete(route('schedules.destroy', $sch->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('schedules', ['id' => $sch->id]);
    }

    public function test_course_assessment_full_crud_and_submission(): void
    {
        $teacher = $this->createTeacher();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);

        // 1. Create Assessment
        $this->actingAs($teacher)->post(route('courses.assessments.store', $course->id), [
            'title' => 'Unit 1 Milestone Oral Examination',
            'type' => 'oral',
            'week_number' => 2,
            'total_points' => 100,
            'passing_score' => 65,
            'time_limit_minutes' => 45,
            'description' => 'Speech presentation and impromptu response.',
        ])->assertRedirect();

        $assessment = CourseAssessment::where('course_id', $course->id)->first();
        $this->assertNotNull($assessment);
        $this->assertEquals('Unit 1 Milestone Oral Examination', $assessment->title);

        // 2. Update Assessment
        $this->actingAs($teacher)->put(route('courses.assessments.update', [$course->id, $assessment->id]), [
            'title' => 'Updated Milestone Oral Examination',
            'type' => 'oral',
            'week_number' => 2,
            'total_points' => 120,
            'passing_score' => 70,
            'time_limit_minutes' => 50,
        ])->assertRedirect();

        $assessment->refresh();
        $this->assertEquals(120, $assessment->total_points);

        // 3. Student Takes / Submits Assessment
        $this->actingAs($student)->get(route('courses.assessments.show', [$course->id, $assessment->id]))
            ->assertStatus(200)
            ->assertSee('Examination Response Submission');

        $this->actingAs($student)->post(route('courses.assessments.submit', [$course->id, $assessment->id]), [
            'answers' => ['response_text' => 'My speech submission transcript.'],
            'score' => 110,
        ])->assertRedirect();

        $submission = CourseAssessmentSubmission::where('assessment_id', $assessment->id)->where('student_id', $student->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals(110, (int) $submission->score);

        // 4. Delete Assessment
        $this->actingAs($teacher)->delete(route('courses.assessments.destroy', [$course->id, $assessment->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('course_assessments', ['id' => $assessment->id]);
        $this->assertDatabaseMissing('course_assessment_submissions', ['id' => $submission->id]);
    }
}
