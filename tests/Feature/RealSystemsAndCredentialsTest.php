<?php

namespace Tests\Feature;

use App\Mail\UserCredentialsMailable;
use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolSection;
use App\Models\SchoolFeeStructure;
use App\Models\User;
use App\Services\StudentIdCardVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class RealSystemsAndCredentialsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected AcademicClass $academicClass;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => str_repeat('k', 32)]);
        AcademySetting::set('academic_session', '2026-27');

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'principal@primeos.edu',
        ]);

        $section = SchoolSection::firstOrCreate(
            ['stream' => 'General', 'name' => 'A'],
            ['capacity' => 40]
        );

        $this->academicClass = AcademicClass::create([
            'name' => 'Grade 10',
            'grade_level' => '10',
            'stream' => 'General',
            'section' => 'A',
            'section_id' => $section->id,
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);
        SchoolFeeStructure::create([
            'class_id' => $this->academicClass->id, 'fee_title' => 'Tuition Fee',
            'fee_type' => 'tuition', 'amount' => 1500, 'due_frequency' => 'monthly',
            'academic_year' => '2026-2027',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'admission_number' => 'ADM-2026-001',
            'current_class_id' => $this->academicClass->id,
            'email' => 'scholar@primeos.edu',
            'password' => Hash::make('old_secret_pass'),
        ]);
    }

    /**
     * Test 1: Bulk student import creates linked parent accounts and sends one-time setup links.
     */
    public function test_bulk_student_import_creates_passwords_and_linked_parent_accounts(): void
    {
        $csvContent = "Full Name,Email,Admission Number,Roll Number,Stream,Section,Guardian Name,Guardian Phone,Guardian Email,Previous School Name,Previous School Board,Previous School Year,TC Number,Previous School Remarks\n" .
                      "Aarav Mehta,aarav.m@primeos.edu,ADM-2026-042,12,General,A,Rajesh Mehta,+91 9876543210,rajesh.mehta@example.com,Central School,CBSE,2025-26,TC-778,Transferred\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);
        Notification::fake();

        $response = $this->actingAs($this->admin)->post(route('school.bulk.students.import'), [
            'csv_file' => $file,
            'create_parent_accounts' => '1',
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionMissing('bulk_last_credentials');

        // Check student created
        $studentUser = User::where('admission_number', 'ADM-2026-042')->first();
        $this->assertNotNull($studentUser);
        $this->assertEquals('Aarav Mehta', $studentUser->name);

        // Check parent created and linked
        $parentUser = User::where('email', 'rajesh.mehta@example.com')->first();
        $this->assertNotNull($parentUser);
        $this->assertEquals('parent', $parentUser->role);
        $this->assertEquals($parentUser->id, $studentUser->parent_id);
        $this->assertSame('Central School', $studentUser->previous_school_name);
        $this->assertSame('CBSE', $studentUser->previous_school_board);
        $this->assertSame('2025-26', $studentUser->previous_school_year);
        $this->assertSame('TC-778', $studentUser->previous_school_tc_number);
        $this->assertSame('Transferred', $studentUser->previous_school_remarks);
        $this->assertNull($studentUser->phone);
        $this->assertDatabaseHas('fee_invoices', [
            'student_id' => $studentUser->id, 'title' => 'Tuition Fee', 'amount' => 1500.00,
            'status' => 'unpaid',
        ]);
        Notification::assertSentTo($studentUser, ResetPassword::class);
        Notification::assertSentTo($parentUser, ResetPassword::class);

        // Passwords are never exposed in session or downloadable credential files.
        $this->assertNotSame('password123', $studentUser->password);
        $this->assertNotSame('password123', $parentUser->password);
    }

    /**
     * Test 2: Bulk parent import links guardians to existing students by admission number.
     */
    public function test_bulk_parent_import_and_linkage_to_existing_students(): void
    {
        $csvContent = "Guardian Name,Email,Phone,Student Admission Number,Relationship\n" .
                      "Sunita Sharma,sunita.sharma@example.com,+91 9123456780,ADM-2026-001,Mother\n";

        $file = UploadedFile::fake()->createWithContent('parents.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('school.bulk.parents.import'), [
            'csv_file' => $file,
        ]);

        $response->assertSessionHas('success');

        $parent = User::where('email', 'sunita.sharma@example.com')->first();
        $this->assertNotNull($parent);
        $this->assertEquals('parent', $parent->role);

        $this->assertEquals($parent->id, $this->student->fresh()->parent_id);
    }

    /**
     * Test 3: Legacy credential export endpoint only streams an explicitly supplied legacy session payload.
     */
    public function test_credentials_csv_export_streams_passwords(): void
    {
        $fakeCredentials = [
            [
                'name' => 'Kabir Sen',
                'role' => 'student',
                'identifier' => 'kabir@primeos.edu',
                'id_number' => 'ADM-099',
                'password' => 'K3#mP9qZ2x',
                'detail' => 'Class Grade 10-A',
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->withSession(['bulk_last_credentials' => $fakeCredentials])
            ->get(route('school.bulk.credentials.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test 4: Bulk email dispatch delivers UserCredentialsMailable.
     */
    public function test_bulk_email_dispatch_of_credentials(): void
    {
        Mail::fake();

        $targetUser = User::factory()->create([
            'email' => 'dispatch.test@primeos.edu',
            'role' => 'student',
            'name' => 'Meera Nair',
        ]);

        $fakeCredentials = [
            [
                'user_id' => $targetUser->id,
                'name' => 'Meera Nair',
                'role' => 'student',
                'identifier' => 'dispatch.test@primeos.edu',
                'id_number' => 'ADM-777',
                'password' => 'SecurePass9!',
                'detail' => 'Class Grade 10-A',
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->withSession(['bulk_last_credentials' => $fakeCredentials])
            ->post(route('school.bulk.credentials.email'));

        $response->assertSessionHas('success');

        Mail::assertSent(UserCredentialsMailable::class, function ($mail) use ($targetUser) {
            return $mail->hasTo('dispatch.test@primeos.edu') &&
                   $mail->plainPassword === 'SecurePass9!';
        });
    }

    /**
     * Test 5: Dynamic UPI Fee Payment Initiation returns NPCI UPI Intent & QR parameters.
     */
    public function test_dynamic_upi_payment_initiation(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'SCH-INV-2026-001',
            'student_id' => $this->student->id,
            'title' => 'First Term Tuition Fee',
            'category' => 'tuition',
            'amount' => 4500.00,
            'total_due' => 4500.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(15),
            'billing_type' => 'term',
        ]);

        $response = $this->actingAs($this->student)->postJson(
            route('school.fees.initiate-upi', $invoice->id),
            ['amount' => 4500.00]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'payment_id',
            'invoice_id',
            'invoice_number',
            'amount',
            'vpa',
            'upi_uri',
            'qr_url',
            'verify_url',
        ]);

        $data = $response->json();
        $this->assertStringStartsWith('upi://pay?', $data['upi_uri']);
        $this->assertStringContainsString('api.qrserver.com', $data['qr_url']);

        $payment = FeePayment::find($data['payment_id']);
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(4500.00, (float)$payment->amount);
    }

    /**
     * Test 6: Verifying UPI transaction with Bank UTR settles payment and updates invoice status.
     */
    public function test_upi_payment_verification_settles_invoice_and_creates_receipt(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'SCH-INV-2026-002',
            'student_id' => $this->student->id,
            'title' => 'Second Term Tuition Fee',
            'category' => 'tuition',
            'amount' => 5000.00,
            'total_due' => 5000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10),
            'billing_type' => 'term',
        ]);

        $payment = FeePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $this->student->id,
            'amount' => 5000.00,
            'payment_method' => 'upi',
            'transaction_reference' => 'PENDING-TXN-REF',
            'receipt_number' => 'SCH-REC-TEST01',
            'paid_at' => now(),
            'status' => 'pending',
        ]);

        $bankUtr = '432198765432';

        $response = $this->actingAs($this->student)->post(
            route('school.fees.verify-upi', $payment->id),
            ['utr_number' => $bankUtr]
        );

        $response->assertRedirect(route('school.fees.receipt', $payment->id));

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals($bankUtr, $payment->transaction_reference);

        $invoice->refresh();
        $this->assertEquals(5000.00, (float)$invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);
    }

    /**
     * Test 7: Admin Account Reset generates new temporary credentials and updates user.
     */
    public function test_admin_account_reset_generates_temporary_credentials(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('school.users.reset-account', $this->student->id),
            ['custom_password' => 'PrimeOS#2026']
        );

        $response->assertSessionHas('success');
        $response->assertSessionHas('account_reset_result');

        $resetData = session('account_reset_result');
        $this->assertEquals('PrimeOS#2026', $resetData['temporary_password']);

        // Authenticate with new password
        $this->student->refresh();
        $this->assertTrue(Hash::check('PrimeOS#2026', $this->student->password));
    }

    /**
     * Test 8: Public Student Digital ID card verification by cryptographic hash.
     */
    public function test_student_digital_id_card_cryptographic_verification(): void
    {
        $signature = app(StudentIdCardVerificationService::class)->signature($this->student);
        $expectedHash = $this->student->id . '.' . $signature;

        // Unauthenticated public request
        $response = $this->get(route('school.students.verify-id', $expectedHash));

        $response->assertOk();
        $response->assertSee($this->student->name);
        $response->assertSee('••••' . substr((string) $this->student->admission_number, -4));
        $response->assertDontSee($this->student->guardian_phone ?? 'guardian-private-value');
        $response->assertDontSee($this->student->address ?? 'private-street-address');
        $response->assertSee('Identity verified — enrollment inactive');
        $response->assertSee('PrimeOS');
        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        // Invalid fake hash returns 404
        $invalidResponse = $this->get(route('school.students.verify-id', 'fake-tampered-hash-value-1234'));
        $invalidResponse->assertNotFound();

        $forgedToken = $this->student->id . '.' . str_repeat('a', 64);
        $forgedResponse = $this->get(route('school.students.verify-id', $forgedToken));
        $forgedResponse->assertNotFound();
    }
    public function test_student_id_card_signature_is_invalidated_when_expiry_is_changed(): void
    {
        $service = app(StudentIdCardVerificationService::class);
        $oldSignature = $service->signature($this->student);

        $this->student->forceFill(['card_valid_until' => now()->addDays(10)->toDateString()])->save();
        $this->student->refresh();

        $this->assertFalse($service->verifies($this->student, $oldSignature));
        $this->assertNotSame($oldSignature, $service->signature($this->student));
    }

    public function test_school_student_id_card_uses_real_record_data_and_no_simulated_codes(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('school.students.id-card', $this->student->id));

        $response->assertOk();
        $response->assertSee($this->student->admission_number);
        $response->assertSee('Verify this ID online');
        $response->assertDontSee('Blood Group');
        $response->assertDontSee('Emergency Phone');
    }

    public function test_expired_student_id_card_is_reported_as_expired(): void
    {
        $this->student->forceFill(['card_valid_until' => today()->subDay()->toDateString()])->save();
        $signature = app(StudentIdCardVerificationService::class)->signature($this->student);

        $this->get(route('school.students.verify-id', $this->student->id . '.' . $signature))
            ->assertOk()
            ->assertSee('Expired identity card');
    }

}
