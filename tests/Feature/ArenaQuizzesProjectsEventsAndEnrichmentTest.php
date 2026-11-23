<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicProject;
use App\Models\AcademicSubject;
use App\Models\AcademyEvent;
use App\Models\LiveQuiz;
use App\Models\LiveQuizQuestion;
use App\Models\ProjectSubmission;
use App\Models\User;
use App\Models\UserCertificate;
use App\Services\BirthdayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ArenaQuizzesProjectsEventsAndEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $academicClass;

    protected AcademicSubject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Dean Administrator',
            'email' => 'dean@primearc.edu.in',
            'role' => 'admin',
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'Prof. Vikram Sen',
            'email' => 'prof.sen@primearc.edu.in',
            'role' => 'teacher',
        ]);

        $this->student = User::factory()->create([
            'name' => 'Aarav Sharma',
            'email' => 'aarav.sharma@primearc.edu.in',
            'role' => 'student',
            'student_id_number' => 'PA-STD-2026-01',
            'date_of_birth' => '2008-11-01', // Birthday matches local test time
            'xp_points' => 120,
            'streak_count' => 5,
        ]);

        $this->academicClass = AcademicClass::create([
            'name' => 'Class 8',
            'grade_level' => 8,
            'section' => 'A',
            'academic_year' => '2026-2027',
        ]);

        $this->subject = AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Physics & Classical Dynamics',
            'code' => 'PHY-08',
        ]);
    }

    /** 1. Live Quizzes Arena Tests */
    public function test_can_view_quizzes_index_and_arena(): void
    {
        $quiz = LiveQuiz::create([
            'creator_id' => $this->teacher->id,
            'class_id' => $this->academicClass->id,
            'title' => 'Thermodynamics Speed Sprint',
            'access_code' => 'QZ-TEST1',
            'time_per_question' => 30,
            'xp_reward' => 100,
            'status' => 'active',
        ]);

        LiveQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'What is absolute zero in Celsius?',
            'option_a' => '-273.15 °C',
            'option_b' => '0 °C',
            'option_c' => '-100 °C',
            'option_d' => '100 °C',
            'correct_option' => 'A',
            'points' => 10,
        ]);

        $response = $this->actingAs($this->student)->get(route('quizzes.index'));
        $response->assertStatus(200);
        $response->assertSee('Thermodynamics Speed Sprint');
        $response->assertSee('QZ-TEST1');

        $showResponse = $this->actingAs($this->student)->get(route('quizzes.show', $quiz->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('What is absolute zero in Celsius?');
    }

    public function test_student_can_submit_live_quiz_and_earn_xp(): void
    {
        $quiz = LiveQuiz::create([
            'creator_id' => $this->teacher->id,
            'class_id' => $this->academicClass->id,
            'title' => 'Speed Quiz Sprint',
            'access_code' => 'QZ-SPRINT',
            'time_per_question' => 20,
            'xp_reward' => 100,
            'status' => 'active',
        ]);

        $q1 = LiveQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'Light speed in vacuum?',
            'option_a' => '3x10^8 m/s',
            'option_b' => '3x10^6 m/s',
            'option_c' => '300 m/s',
            'option_d' => 'Infinite',
            'correct_option' => 'A',
            'points' => 10,
        ]);

        $response = $this->actingAs($this->student)->post(route('quizzes.submit', $quiz->id), [
            'answers' => [
                $q1->id => 'A',
            ],
        ]);

        $response->assertRedirect(route('quizzes.show', $quiz->id));
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'correct_answers' => 1,
            'score' => 10,
        ]);
    }

    /** 2. Case Studies & Projects (Grouped & Ungrouped) Tests */
    public function test_grouped_and_ungrouped_projects_workflow(): void
    {
        // Create Grouped Project
        $groupedProject = AcademicProject::create([
            'class_id' => $this->academicClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Autonomous Drone Navigation - Group Project',
            'type' => 'project',
            'group_mode' => 'grouped',
            'max_team_size' => 4,
            'description' => 'Build simulated pathfinding algorithms.',
            'due_date' => now()->addWeeks(2)->toDateString(),
            'max_marks' => 100,
        ]);

        // Create Individual Case Study
        $individualCase = AcademicProject::create([
            'class_id' => $this->academicClass->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Chernobyl Reactor Physics - Case Study',
            'type' => 'case_study',
            'group_mode' => 'individual',
            'max_team_size' => 1,
            'description' => 'Analyze positive void coefficient dynamics.',
            'due_date' => now()->addWeeks(2)->toDateString(),
            'max_marks' => 50,
        ]);

        // Student submits to grouped project
        $submitResponse = $this->actingAs($this->student)->post(route('projects.submit', $groupedProject->id), [
            'team_name' => 'AeroDynamics Lab',
            'team_members' => 'Aarav Sharma, Bhavna Verma',
            'solution_summary' => 'Implemented Dijkstra and A* pathfinding algorithms with obstacle avoidance.',
            'deliverable_url' => 'https://github.com/primearc/drone-nav',
        ]);
        $submitResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_submissions', [
            'project_id' => $groupedProject->id,
            'student_id' => $this->student->id,
            'team_name' => 'AeroDynamics Lab',
        ]);

        $submission = ProjectSubmission::where('project_id', $groupedProject->id)->first();

        // Teacher evaluates and grades
        $gradeResponse = $this->actingAs($this->teacher)->post(route('projects.submissions.evaluate', $submission->id), [
            'score' => 95,
            'teacher_feedback' => 'Superb algorithmic efficiency and comprehensive simulation diagrams.',
            'status' => 'evaluated',
        ]);
        $gradeResponse->assertSessionHasNoErrors();

        $submission->refresh();
        $this->assertEquals(95, $submission->score);
    }

    /** 3. Extracurricular Events (Debate Tournaments & Talent Shows) */
    public function test_debate_and_talent_show_events(): void
    {
        $debate = AcademyEvent::create([
            'coordinator_id' => $this->teacher->id,
            'title' => 'Inter-Class Parliamentary Debate',
            'category' => 'debate',
            'event_format' => 'parliamentary_team',
            'description' => 'Motion: THW Replace Standardized Testing with Continuous Portfolio Review.',
            'event_datetime' => now()->addDays(3),
            'venue' => 'Chanakya Auditorium',
            'status' => 'upcoming',
        ]);

        // Student registers
        $regResponse = $this->actingAs($this->student)->post(route('events.register', $debate->id));
        $regResponse->assertSessionHasNoErrors();

        $debate->refresh();
        $this->assertContains($this->student->id, $debate->registered_participants);

        // Teacher awards winners podium
        $podiumResponse = $this->actingAs($this->teacher)->post(route('events.podium', $debate->id), [
            'winner_1st' => 'Quantum Orators (Class 8)',
            'winner_2nd' => 'Dialectic Scholars',
            'winner_3rd' => 'The Rhetoricians',
            'status' => 'completed',
        ]);
        $podiumResponse->assertSessionHasNoErrors();

        $debate->refresh();
        $this->assertEquals('completed', $debate->status);
        $this->assertEquals('Quantum Orators (Class 8)', $debate->podium_winners['1st']);
    }

    /** 4. Automated Birthday Greeter on DOBs */
    public function test_automated_birthday_greeter_on_dob(): void
    {
        // Set student's DOB to today's month and day
        $today = Carbon::today();
        $this->student->update([
            'date_of_birth' => $today->copy()->subYears(15)->toDateString(),
            'xp_points' => 100,
        ]);

        $this->assertTrue($this->student->isBirthdayToday());

        // Call BirthdayService
        $celebration = BirthdayService::checkAndCelebrate($this->student);

        $this->assertNotNull($celebration);
        $this->assertTrue($celebration['is_birthday']);
        $this->assertEquals('Aarav Sharma', $celebration['name']);

        // Check that +100 bonus XP was awarded
        $this->student->refresh();
        $this->assertEquals(200, $this->student->xp_points);

        // Check that "birthday_star" badge was unlocked
        $currentYear = $today->year;
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $this->student->id,
            'badge_key' => 'birthday_star_'.$currentYear,
        ]);

        // Check that celebratory in-app notification was generated
        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->student->id,
            'icon' => '🎂',
        ]);

        // Visiting dashboard displays birthday card
        $dashboardResponse = $this->actingAs($this->student)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Happy Birthday, Aarav Sharma!');
    }

    /** 5. Leaderboard (Global & Class-Wise) */
    public function test_campus_and_class_leaderboard(): void
    {
        $response = $this->actingAs($this->student)->get(route('leaderboard.index'));
        $response->assertStatus(200);
        $response->assertSee('Prime Arc Campus Leaderboard');
        $response->assertSee('Aarav Sharma');

        // Streak tab
        $streakResponse = $this->actingAs($this->student)->get(route('leaderboard.index', ['tab' => 'streak']));
        $streakResponse->assertStatus(200);
        $streakResponse->assertSee('Aarav Sharma');
    }

    /** 7. Certificates & Badges System */
    public function test_certificates_and_badges_verification(): void
    {
        $certNumber = 'CERT-PA-2026-9999';
        $hash = 'verifyhash1234567890abcdef123456';

        $cert = UserCertificate::create([
            'user_id' => $this->student->id,
            'issuer_id' => $this->teacher->id,
            'certificate_number' => $certNumber,
            'title' => 'Certificate of Olympiad Distinction',
            'category' => 'olympiad',
            'citation' => 'In recognition of superlative mathematical precision and physics analysis.',
            'issued_date' => now()->toDateString(),
            'verification_hash' => $hash,
        ]);

        $response = $this->actingAs($this->student)->get(route('certificates.index'));
        $response->assertStatus(200);
        $response->assertSee('Certificate of Olympiad Distinction');

        // Parchment print view
        $parchmentResponse = $this->actingAs($this->student)->get(route('certificates.show', $cert->id));
        $parchmentResponse->assertStatus(200);
        $parchmentResponse->assertSee('Certificate of Olympiad Distinction');
        $parchmentResponse->assertSee('Aarav Sharma');

        // Public Verification Check
        $verifyResponse = $this->get(route('certificates.verify', $hash));
        $verifyResponse->assertStatus(200);
        $verifyResponse->assertSee('Cryptographically Verified Credential');
        $verifyResponse->assertSee($certNumber);
    }

    /** 7. Live Quiz CSV, Text Import & Question Management */
    public function test_live_quiz_csv_and_text_import_and_question_management(): void
    {
        $quiz = LiveQuiz::create([
            'creator_id' => $this->teacher->id,
            'class_id' => $this->academicClass->id,
            'title' => 'Genetics & Biotechnology Challenge',
            'access_code' => 'QZ-GENET',
            'time_per_question' => 45,
            'xp_reward' => 200,
            'status' => 'active',
        ]);

        // 1. Download sample CSV
        $sampleResponse = $this->actingAs($this->teacher)->get(route('quizzes.sample-csv'));
        $sampleResponse->assertStatus(200);
        $sampleResponse->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        // 2. Import CSV questions
        $csvContent = "question,option_a,option_b,option_c,option_d,correct_option,explanation,points\n"
            ."\"What is the basic physical unit of heredity?\",\"Gene\",\"Chromosome\",\"Protein\",\"Lipid\",\"A\",\"A gene is the fundamental unit of inheritance.\",10\n"
            ."\"Which organism was used by Mendel for genetic crosses?\",\"Drosophila\",\"Pisum sativum\",\"E. coli\",\"Neurospora\",\"B\",\"Mendel studied garden peas (Pisum sativum).\",15\n";

        $uploadedCsv = UploadedFile::fake()->createWithContent('quiz_questions.csv', $csvContent);

        $importResponse = $this->actingAs($this->teacher)->post(route('quizzes.import-csv', $quiz->id), [
            'csv_file' => $uploadedCsv,
        ]);
        $importResponse->assertRedirect();
        $this->assertEquals(2, $quiz->questions()->count());

        // 3. Import Bulk Text questions
        $rawText = 'Q: What is the genetic material in most organisms?
A) DNA
B) RNA
C) Carbohydrate
D) Hemoglobin
Ans: A
Exp: Deoxyribonucleic acid carries the genetic instructions.

Q: Who proposed the double-helix model of DNA?
A) Watson and Crick
B) Gregor Mendel
C) Charles Darwin
D) Louis Pasteur
Ans: A';

        $textResponse = $this->actingAs($this->teacher)->post(route('quizzes.import-text', $quiz->id), [
            'raw_text' => $rawText,
        ]);
        $textResponse->assertRedirect();
        $this->assertEquals(4, $quiz->questions()->count());

        // 4. Add single question
        $addResponse = $this->actingAs($this->teacher)->post(route('quizzes.questions.store', $quiz->id), [
            'question' => 'What is the full form of PCR?',
            'option_a' => 'Polymerase Chain Reaction',
            'option_b' => 'Protein Complex Reagent',
            'option_c' => 'Peptide Cleavage Rate',
            'option_d' => 'Phosphorylation Cycle Ratio',
            'correct_option' => 'A',
            'explanation' => 'PCR amplifies specific DNA sequences.',
            'points' => 10,
        ]);
        $addResponse->assertRedirect();
        $this->assertEquals(5, $quiz->questions()->count());

        // 5. Export CSV
        $exportResponse = $this->actingAs($this->teacher)->get(route('quizzes.export-csv', $quiz->id));
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        // 6. Delete question
        $lastQ = $quiz->questions()->latest('id')->first();
        $deleteQResponse = $this->actingAs($this->teacher)->delete(route('quizzes.questions.destroy', [$quiz->id, $lastQ->id]));
        $deleteQResponse->assertRedirect();
        $this->assertEquals(4, $quiz->questions()->count());
    }

    /** 10. Events Unregister & Participants Export */
    public function test_events_unregister_and_participants_export(): void
    {
        $event = AcademyEvent::create([
            'coordinator_id' => $this->teacher->id,
            'title' => 'Prime Arc Parliamentary Debate Cup',
            'category' => 'debate',
            'event_format' => 'parliamentary_team',
            'description' => 'Annual debate tournament.',
            'event_datetime' => now()->addDays(3),
            'venue' => 'Chamber of Discourse',
            'status' => 'upcoming',
        ]);

        // Register student
        $this->actingAs($this->student)->post(route('events.register', $event->id));
        $event->refresh();
        $this->assertContains($this->student->id, $event->registered_participants);

        // Export participants CSV
        $exportResponse = $this->actingAs($this->teacher)->get(route('events.export-participants-csv', $event->id));
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        // Unregister student
        $unregResponse = $this->actingAs($this->student)->post(route('events.unregister', $event->id));
        $unregResponse->assertRedirect();
        $event->refresh();
        $this->assertNotContains($this->student->id, $event->registered_participants ?? []);
    }

    /** 9. Certificate Revocation & Deletion */
    public function test_certificate_revocation_and_deletion(): void
    {
        $cert = UserCertificate::create([
            'user_id' => $this->student->id,
            'issuer_id' => $this->teacher->id,
            'certificate_number' => 'CERT-REVOKE-01',
            'title' => 'Certificate to be Revoked',
            'category' => 'olympiad',
            'citation' => 'Temporary certificate citation.',
            'issued_date' => now()->toDateString(),
            'verification_hash' => 'hash_to_revoke_123',
        ]);

        $deleteResp = $this->actingAs($this->teacher)->delete(route('certificates.destroy', $cert->id));
        $deleteResp->assertRedirect(route('certificates.index'));
        $this->assertDatabaseMissing('user_certificates', ['id' => $cert->id]);
    }

    /** 14. Leaderboard Streak Tab Ranking */
    public function test_leaderboard_streak_tab_ranking(): void
    {
        $student2 = User::factory()->create([
            'role' => 'student',
            'name' => 'High Streak Scholar',
            'streak_count' => 20,
            'xp_points' => 50,
        ]);

        $response = $this->actingAs($this->student)->get(route('leaderboard.index', ['tab' => 'streak']));
        $response->assertStatus(200);
        $response->assertSee('High Streak Scholar');
    }
}
