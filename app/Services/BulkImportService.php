<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\ClassEnrollment;
use App\Models\SchoolSection;
use App\Models\SchoolStream;
use App\Models\SubjectMaster;
use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkImportService
{
    /**
     * Generate a cryptographically secure, human-readable temporary password.
     */
    public function generateRandomPassword(int $length = 10): string
    {
        $chars = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ!@#';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }

    /**
     * Import students from a CSV file.
     */
    public function importStudentsFromCsv(UploadedFile $file, bool $autoGeneratePassword = true, bool $createParentAccounts = true): array
    {
        $rows = $this->parseCsvFile($file);
        if (count($rows) < 2) {
            return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'parents_created' => 0, 'errors' => ['The CSV must contain a header row and at least one student row.'], 'credentials' => []];
        }
        if (count($rows) > 1001) {
            return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'parents_created' => 0, 'errors' => ['A single import is limited to 1,000 student rows. Split the file and retry.'], 'credentials' => []];
        }

        $headers = array_map(fn ($h) => strtolower(trim(str_replace([' ', '_', '-'], '', (string) $h))), array_shift($rows));
        $sessionSetting = \App\Models\AcademySetting::get('academic_session');
        $academicYear = $this->normalizeAcademicSession($sessionSetting);
        if (!$academicYear) {
            return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'parents_created' => 0, 'errors' => ['Configure a valid active academic session in School Settings before importing students.'], 'credentials' => []];
        }

        $prepared = [];
        $errors = [];
        $seenEmails = [];
        $seenAdmissions = [];
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            if (empty(array_filter($row, fn ($value) => trim((string) $value) !== ''))) {
                continue;
            }
            $data = $this->mapRowToHeaders($headers, $row);
            $name = trim((string) ($data['fullname'] ?? $data['studentname'] ?? $data['name'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $admissionNo = trim((string) ($data['admissionno'] ?? $data['admissionnumber'] ?? $data['studentid'] ?? ''));
            $className = trim((string) ($data['class'] ?? $data['classname'] ?? ''));
            $section = strtoupper(trim((string) ($data['section'] ?? '')));
            $stream = trim((string) ($data['stream'] ?? ''));
            $guardianName = trim((string) ($data['guardianname'] ?? ''));
            $guardianEmail = strtolower(trim((string) ($data['guardianemail'] ?? '')));
            $guardianPhone = trim((string) ($data['guardianphone'] ?? ''));
            $studentPhone = trim((string) ($data['phone'] ?? ''));

            if ($name === '') $errors[] = "Row {$rowNum}: Student name is required.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Row {$rowNum}: A valid student email is required; generated placeholder emails are not accepted.";
            if ($admissionNo === '') $errors[] = "Row {$rowNum}: Admission number is required.";
            if (mb_strlen($admissionNo) > 50) $errors[] = "Row {$rowNum}: Admission number cannot exceed 50 characters.";
            if (mb_strlen($name) > 120) $errors[] = "Row {$rowNum}: Student name cannot exceed 120 characters.";
            if (mb_strlen($email) > 150) $errors[] = "Row {$rowNum}: Student email cannot exceed 150 characters.";
            if ($className === '' || $section === '') $errors[] = "Row {$rowNum}: Class and section are required and must already exist in academic master data.";
            if (!preg_match('/^[A-Z]$/', $section)) $errors[] = "Row {$rowNum}: Section must be a single letter A-Z.";
            $hasGuardianData = $guardianName !== '' || $guardianEmail !== '' || $guardianPhone !== '';
            if (($createParentAccounts || $hasGuardianData) && ($guardianName === '' || !filter_var($guardianEmail, FILTER_VALIDATE_EMAIL) || $guardianPhone === '')) $errors[] = "Row {$rowNum}: Guardian name, valid guardian email and guardian phone must all be supplied together.";
            if (mb_strlen($guardianName) > 120 || mb_strlen($guardianEmail) > 150) $errors[] = "Row {$rowNum}: Guardian name or email exceeds the supported length.";
            if (isset($seenEmails[$email]) && $email !== '') $errors[] = "Row {$rowNum}: Duplicate student email in this CSV.";
            if (isset($seenAdmissions[$admissionNo]) && $admissionNo !== '') $errors[] = "Row {$rowNum}: Duplicate admission number in this CSV.";
            if ($email !== '') $seenEmails[$email] = true;
            if ($admissionNo !== '') $seenAdmissions[$admissionNo] = true;

            $rollRaw = trim((string) ($data['rollno'] ?? $data['rollnumber'] ?? ''));
            if ($rollRaw !== '' && (!ctype_digit($rollRaw) || (int) $rollRaw < 1)) $errors[] = "Row {$rowNum}: Roll number must be a positive whole number.";
            if ($studentPhone !== '' && mb_strlen($studentPhone) > 25) $errors[] = "Row {$rowNum}: Student phone number is too long.";
            if ($guardianPhone !== '' && mb_strlen($guardianPhone) > 25) $errors[] = "Row {$rowNum}: Guardian phone number is too long.";
            if (mb_strlen((string) ($data['bloodgroup'] ?? '')) > 10) $errors[] = "Row {$rowNum}: Blood group value is too long.";
            if (mb_strlen((string) ($data['previousschoolname'] ?? $data['previousschool'] ?? $data['lastschool'] ?? '')) > 150) $errors[] = "Row {$rowNum}: Previous school name cannot exceed 150 characters.";
            if (mb_strlen((string) ($data['previousschoolboard'] ?? $data['previousboard'] ?? $data['board'] ?? '')) > 60) $errors[] = "Row {$rowNum}: Previous school board cannot exceed 60 characters.";
            if (mb_strlen((string) ($data['previousschoolyear'] ?? $data['passingyear'] ?? '')) > 30) $errors[] = "Row {$rowNum}: Previous school year cannot exceed 30 characters.";
            if (mb_strlen((string) ($data['tcnumber'] ?? $data['previoustransfercertificate'] ?? $data['transfercertificateno'] ?? '')) > 60) $errors[] = "Row {$rowNum}: Transfer certificate number cannot exceed 60 characters.";

            $prepared[] = [
                'row' => $rowNum, 'name' => $name, 'email' => $email, 'admission' => $admissionNo,
                'roll' => $rollRaw === '' ? null : (int) $rollRaw, 'class_name' => $className, 'section' => $section, 'stream' => $stream ?: null,
                'phone' => $studentPhone !== '' ? $studentPhone : null,
                'guardian_name' => $guardianName ?: null, 'guardian_email' => $guardianEmail ?: null, 'guardian_phone' => $guardianPhone ?: null,
                'blood_group' => trim((string) ($data['bloodgroup'] ?? '')) ?: null,
                'previous_school_name' => trim((string) ($data['previousschoolname'] ?? $data['previousschool'] ?? $data['lastschool'] ?? '')) ?: null,
                'previous_school_board' => trim((string) ($data['previousschoolboard'] ?? $data['previousboard'] ?? $data['board'] ?? '')) ?: null,
                'previous_school_year' => trim((string) ($data['previousschoolyear'] ?? $data['passingyear'] ?? '')) ?: null,
                'previous_school_tc_number' => trim((string) ($data['tcnumber'] ?? $data['previoustransfercertificate'] ?? $data['transfercertificateno'] ?? '')) ?: null,
                'previous_school_remarks' => trim((string) ($data['previousschoolremarks'] ?? $data['tcremarks'] ?? '')) ?: null,
            ];
        }
        if ($errors) return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'parents_created' => 0, 'errors' => $errors, 'credentials' => []];
        if (!$prepared) return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'parents_created' => 0, 'errors' => ['No student records were found in the CSV.'], 'credentials' => []];

        $created = 0;
        $updated = 0;
        $parentsCreated = 0;
        $newAccountEmails = [];
        $credentials = [];
        try {
            DB::transaction(function () use ($prepared, $academicYear, $createParentAccounts, $autoGeneratePassword, &$created, &$updated, &$parentsCreated, &$newAccountEmails, &$credentials) {
                foreach ($prepared as $item) {
                    $shortAcademicYear = substr($academicYear, 0, 4) . '-' . substr($academicYear, -2);
                    $classQuery = AcademicClass::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($item['class_name'])])
                        ->whereRaw('UPPER(TRIM(section)) = ?', [$item['section']])
                        ->whereIn('academic_year', array_unique([$academicYear, $shortAcademicYear]));
                    if ($item['stream'] !== null) {
                        $classQuery->whereRaw('LOWER(TRIM(stream)) = ?', [mb_strtolower($item['stream'])]);
                    }
                    $matchingClasses = $classQuery->lockForUpdate()->get();
                    if ($matchingClasses->count() !== 1) {
                        throw new \RuntimeException("Row {$item['row']}: Class '{$item['class_name']}' section {$item['section']}" . ($item['stream'] ? " stream {$item['stream']}" : '') . " must match exactly one class configured for active session {$academicYear}. Create or correct master data first.");
                    }
                    $class = $matchingClasses->first();
                    $feeStructures = \App\Models\SchoolFeeStructure::query()
                        ->where('class_id', $class->id)
                        ->whereIn('academic_year', array_unique([$academicYear, $shortAcademicYear]))
                        ->where('amount', '>', 0)
                        ->orderBy('id')->lockForUpdate()->get();
                    if ($feeStructures->isEmpty()) {
                        throw new \RuntimeException("Row {$item['row']}: No payable fee structure is configured for {$item['class_name']} / {$item['section']} in session {$academicYear}. Configure fee master data before importing.");
                    }

                    $studentByEmail = User::where('email', $item['email'])->lockForUpdate()->first();
                    $studentByAdmission = User::where('admission_number', $item['admission'])->lockForUpdate()->first();
                    if ($studentByEmail && !$studentByEmail->isStudent()) {
                        throw new \RuntimeException("Row {$item['row']}: Student email belongs to a non-student account.");
                    }
                    if ($studentByAdmission && !$studentByAdmission->isStudent()) {
                        throw new \RuntimeException("Row {$item['row']}: Admission number belongs to a non-student account.");
                    }
                    if ($studentByEmail && $studentByAdmission && $studentByEmail->id !== $studentByAdmission->id) {
                        throw new \RuntimeException("Row {$item['row']}: Student email and admission number identify different accounts.");
                    }
                    $student = $studentByEmail ?: $studentByAdmission;
                    $isNewStudent = !$student;
                    if ($isNewStudent && $class->capacity) {
                        $activeEnrollmentCount = ClassEnrollment::where('class_id', $class->id)
                            ->where('academic_year', $academicYear)->where('status', 'active')->count();
                        if ($activeEnrollmentCount >= (int) $class->capacity) {
                            throw new \RuntimeException("Row {$item['row']}: Class '{$item['class_name']}' / {$item['section']} has reached its configured capacity.");
                        }
                    }
                    if ($student && $student->current_class_id && (int) $student->current_class_id !== (int) $class->id) {
                        throw new \RuntimeException("Row {$item['row']}: Existing student is assigned to another class. Use the formal class-transfer workflow instead of changing class through bulk import.");
                    }
                    if ($student && ClassEnrollment::where('student_id', $student->id)
                        ->where('academic_year', $academicYear)->where('status', 'active')
                        ->where('class_id', '!=', $class->id)->exists()) {
                        throw new \RuntimeException("Row {$item['row']}: Student already has an active enrollment in another class for this session. Use the formal class-transfer workflow.");
                    }
                    if (!$student && User::where('student_id_number', $item['admission'])->exists()) {
                        throw new \RuntimeException("Row {$item['row']}: Admission number is already in use.");
                    }
                    if (ClassEnrollment::where('class_id', $class->id)->where('academic_year', $academicYear)
                        ->when($student, fn ($q) => $q->where('student_id', '!=', $student->id))
                        ->where('admission_number', $item['admission'])->exists()) {
                        throw new \RuntimeException("Row {$item['row']}: Admission number is already enrolled in this class/session.");
                    }

                    $roll = $item['roll'];
                    if ($roll !== null && !ClassEnrollment::isRollNumberAvailable($class->id, $roll, $student?->id, $academicYear)) {
                        throw new \RuntimeException("Row {$item['row']}: Roll number {$roll} is already assigned in this class.");
                    }
                    if ($roll === null) $roll = ClassEnrollment::generateNextRollNumber($class->id, $student?->id, $academicYear);
                    if ($class->capacity && $roll > (int) $class->capacity) {
                        throw new \RuntimeException("Row {$item['row']}: Roll number exceeds the configured class capacity.");
                    }

                    $attributes = [
                        'name' => $item['name'], 'email' => $item['email'], 'role' => 'student',
                        'admission_number' => $item['admission'], 'student_id_number' => $item['admission'],
                        'roll_number' => $roll, 'current_class_id' => $class->id,
                        'phone' => $item['phone'], 'guardian_name' => $item['guardian_name'],
                        'guardian_phone' => $item['guardian_phone'], 'guardian_email' => $item['guardian_email'],
                        'blood_group' => $item['blood_group'],
                        'previous_school_name' => $item['previous_school_name'],
                        'previous_school_board' => $item['previous_school_board'],
                        'previous_school_year' => $item['previous_school_year'],
                        'previous_school_tc_number' => $item['previous_school_tc_number'],
                        'previous_school_remarks' => $item['previous_school_remarks'],
                        'is_approved' => true,
                    ];
                    if ($student) {
                        $student->update(array_filter($attributes, fn ($value) => $value !== null));
                        $updated++;
                    } else {
                        $studentPlainPassword = $this->generateRandomPassword(10);
                        $student = User::create($attributes + ['password' => Hash::make($studentPlainPassword)]);
                        $created++;
                        $newAccountEmails[] = $student->email;
                        $credentials[] = [
                            'user_id' => $student->id,
                            'name' => $student->name,
                            'role' => 'student',
                            'email' => $student->email,
                            'id_number' => $student->admission_number,
                            'password' => $studentPlainPassword,
                            'detail' => $class->display_name ?? ($class->name . ' - ' . $class->section),
                        ];
                    }

                    if ($createParentAccounts) {
                        $parent = User::where('email', $item['guardian_email'])->lockForUpdate()->first();
                        if ($parent && !$parent->isParent()) {
                            throw new \RuntimeException("Row {$item['row']}: Guardian email belongs to a non-parent account.");
                        }
                        if ($student->parent_id && (int) $student->parent_id !== (int) ($parent?->id ?? 0)) {
                            throw new \RuntimeException("Row {$item['row']}: Student is already linked to a different parent account. Resolve the guardian relationship before importing this row.");
                        }
                        if (!$parent) {
                            $parentPlainPassword = $this->generateRandomPassword(10);
                            $parent = User::create([
                                'name' => $item['guardian_name'], 'email' => $item['guardian_email'],
                                'password' => Hash::make($parentPlainPassword), 'role' => 'parent',
                                'phone' => $item['guardian_phone'], 'is_approved' => true,
                            ]);
                            $parentsCreated++;
                            $newAccountEmails[] = $parent->email;
                            $credentials[] = [
                                'user_id' => $parent->id,
                                'name' => $parent->name,
                                'role' => 'parent',
                                'email' => $parent->email,
                                'id_number' => 'Guardian of ' . $student->admission_number,
                                'password' => $parentPlainPassword,
                                'detail' => 'Ward: ' . $student->name,
                            ];
                        }
                        $student->update(['parent_id' => $parent->id]);
                    }

                    $activeEnrollment = ClassEnrollment::query()->where('class_id', $class->id)
                        ->where('student_id', $student->id)->where('academic_year', $academicYear)
                        ->where('status', 'active')->lockForUpdate()->first();
                    if ($activeEnrollment) {
                        $activeEnrollment->update([
                            'roll_number' => $roll,
                            'admission_number' => $item['admission'],
                        ]);
                    } else {
                        ClassEnrollment::create([
                            'class_id' => $class->id, 'student_id' => $student->id,
                            'academic_year' => $academicYear, 'roll_number' => $roll,
                            'admission_number' => $item['admission'], 'status' => 'active',
                        ]);
                    }

                    if ($isNewStudent) {
                        foreach ($feeStructures as $feeStructure) {
                            $amount = round((float) $feeStructure->amount, 2);
                            $frequency = in_array($feeStructure->due_frequency, ['monthly', 'quarterly', 'annual'], true)
                                ? $feeStructure->due_frequency : 'manual';
                            \App\Models\FeeInvoice::create([
                                'invoice_number' => 'SCH-' . now()->format('Ymd') . '-' . Str::upper(Str::random(10)),
                                'student_id' => $student->id,
                                'title' => $feeStructure->fee_title,
                                'category' => $feeStructure->fee_type,
                                'amount' => $amount,
                                'discount' => 0,
                                'tax' => 0,
                                'total_due' => $amount,
                                'paid_amount' => 0,
                                'status' => 'unpaid',
                                'billing_type' => $frequency,
                                'billing_month' => $frequency === 'monthly' ? now()->format('Y-m') : null,
                                'due_date' => now()->addDays(15)->toDateString(),
                                'notes' => 'Generated from fee structure #' . $feeStructure->id . ' for academic session ' . $academicYear,
                            ]);
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'parents_created' => 0, 'errors' => [$e instanceof \RuntimeException ? $e->getMessage() : 'Student import failed. No records were committed. Check application logs for details.'], 'credentials' => []];
        }

        // Credentials are never exported or stored in the session. Users receive one-time setup links.
        $notificationFailures = [];
        foreach (array_unique($newAccountEmails) as $email) {
            try {
                $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $email]);
                if ($status !== \Illuminate\Support\Facades\Password::RESET_LINK_SENT) $notificationFailures[] = $email;
            } catch (\Throwable $e) {
                report($e);
                $notificationFailures[] = $email;
            }
        }

        $resultErrors = [];
        if ($notificationFailures) $resultErrors[] = 'Some account setup emails could not be sent: ' . implode(', ', $notificationFailures) . '. Resend setup links from Account Management.';
        return [
            'success' => true, 'total' => $created + $updated, 'created' => $created,
            'updated' => $updated, 'parents_created' => $parentsCreated,
            'errors' => $resultErrors, 'credentials' => $credentials,
        ];
    }

    private function normalizeAcademicSession(?string $session): ?string
    {
        $session = trim((string) $session);
        if (preg_match('/^(\\d{4})-(\\d{2})$/', $session, $matches)) return $matches[1] . '-' . ((int) $matches[1] + 1);
        if (preg_match('/^\\d{4}-\\d{4}$/', $session)) return $session;
        return null;
    }

    /**
     * Import staff / teachers / admins from CSV.
     */
    public function importStaffFromCsv(UploadedFile $file, bool $autoGeneratePassword = true): array
    {
        $rows = $this->parseCsvFile($file);
        if (empty($rows)) {
            return [
                'success' => false,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => ['The uploaded CSV file is empty.'],
                'credentials' => [],
            ];
        }

        $headers = array_map(fn($h) => strtolower(trim(str_replace([' ', '_', '-'], '', (string)$h))), array_shift($rows));

        $created = 0;
        $updated = 0;
        $errors = [];
        $credentials = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = $this->mapRowToHeaders($headers, $row);

                $name = $data['fullname'] ?? $data['name'] ?? $data['teachername'] ?? $data['staffname'] ?? null;
                $email = $data['email'] ?? null;
                $phone = !empty(trim((string)($data['phone'] ?? ''))) ? trim((string)$data['phone']) : null;
                $role = strtolower(trim((string)($data['role'] ?? 'teacher')));
                if (!in_array($role, ['teacher', 'admin'])) {
                    $role = 'teacher';
                }
                $employeeId = $data['employeeid'] ?? $data['idnumber'] ?? $data['staffid'] ?? null;
                $designation = $data['designation'] ?? $data['title'] ?? null;

                if (!$name || !$email) {
                    $errors[] = "Row {$rowNum}: Name and Email are mandatory.";
                    continue;
                }

                $user = User::where('email', $email)->first();

                // Disambiguate student_id_number if another user already holds it
                if ($employeeId) {
                    $conflictQuery = User::where('student_id_number', $employeeId);
                    if ($user) {
                        $conflictQuery->where('id', '!=', $user->id);
                    }
                    if ($conflictQuery->exists()) {
                        $employeeId = $employeeId . '-' . Str::upper(Str::random(3));
                    }
                } else {
                    $employeeId = 'EMP-' . Str::upper(Str::random(5));
                }

                if ($user) {
                    $user->update([
                        'name' => $name,
                        'phone' => $phone ?? $user->phone,
                        'role' => $role,
                        'student_id_number' => $employeeId,
                        'bio' => $designation ?? $user->bio,
                        'is_approved' => true,
                    ]);
                    $updated++;
                } else {
                    $plainPassword = $this->generateRandomPassword(10);
                    $staffUser = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($plainPassword),
                        'role' => $role,
                        'phone' => $phone,
                        'student_id_number' => $employeeId,
                        'bio' => $designation,
                        'is_approved' => true,
                    ]);
                    $created++;

                    $credentials[] = [
                        'user_id' => $staffUser->id,
                        'name' => $staffUser->name,
                        'email' => $staffUser->email,
                        'role' => $role,
                        'id_number' => $employeeId,
                        'password' => $plainPassword,
                        'detail' => $designation ?: ucfirst($role),
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'total' => $created + $updated,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'credentials' => $credentials,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => ['Staff import failed: ' . $e->getMessage()],
                'credentials' => [],
            ];
        }
    }

    /**
     * Import Parents and Guardians from CSV, linking them to student records.
     */
    public function importParentsFromCsv(UploadedFile $file, bool $autoGeneratePassword = true): array
    {
        $rows = $this->parseCsvFile($file);
        if (empty($rows)) {
            return [
                'success' => false,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'linked' => 0,
                'errors' => ['The uploaded CSV file is empty or formatted incorrectly.'],
                'credentials' => [],
            ];
        }

        $headers = array_map(fn($h) => strtolower(trim(str_replace([' ', '_', '-'], '', (string)$h))), array_shift($rows));
        $created = 0;
        $updated = 0;
        $linked = 0;
        $errors = [];
        $credentials = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = $this->mapRowToHeaders($headers, $row);
                $name = $data['fullname'] ?? $data['parentname'] ?? $data['guardianname'] ?? $data['name'] ?? null;
                $email = $data['email'] ?? $data['guardianemail'] ?? null;
                $phone = $data['phone'] ?? $data['guardianphone'] ?? null;
                $address = $data['address'] ?? null;
                $studentRef = $data['studentadmissionnumber'] ?? $data['studentadmissionno'] ?? $data['admissionnumber'] ?? $data['admissionno'] ?? $data['studentid'] ?? $data['studentemail'] ?? null;

                if (!$name || !$email) {
                    $errors[] = "Row {$rowNum}: Parent Name and Email are mandatory.";
                    continue;
                }

                $cleanEmail = strtolower(trim($email));
                $parent = User::where('email', $cleanEmail)->first();
                $plainPassword = $this->generateRandomPassword(10);

                if ($parent) {
                    $parent->update([
                        'name' => $name,
                        'phone' => $phone ?? $parent->phone,
                        'address' => $address ?? $parent->address,
                        'role' => 'parent',
                        'is_approved' => true,
                    ]);
                    $updated++;
                } else {
                    $parent = User::create([
                        'name' => $name,
                        'email' => $cleanEmail,
                        'password' => Hash::make($plainPassword),
                        'role' => 'parent',
                        'phone' => $phone,
                        'address' => $address,
                        'is_approved' => true,
                    ]);
                    $created++;

                    $credentials[] = [
                        'user_id' => $parent->id,
                        'name' => $parent->name,
                        'email' => $parent->email,
                        'role' => 'parent',
                        'id_number' => 'PAR-' . $parent->id,
                        'password' => $plainPassword,
                        'detail' => $studentRef ? "Guardian (Child: {$studentRef})" : 'Parent Account',
                    ];
                }

                // Link to Student if reference provided
                if ($studentRef) {
                    $student = User::where('role', 'student')
                        ->where(function ($q) use ($studentRef) {
                            $q->where('admission_number', trim($studentRef))
                              ->orWhere('student_id_number', trim($studentRef))
                              ->orWhere('email', trim($studentRef));
                        })
                        ->first();

                    if ($student) {
                        $student->update([
                            'parent_id' => $parent->id,
                            'guardian_name' => $parent->name,
                            'guardian_email' => $parent->email,
                            'guardian_phone' => $parent->phone ?? $student->guardian_phone,
                        ]);
                        $linked++;
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'total' => $created + $updated,
                'created' => $created,
                'updated' => $updated,
                'linked' => $linked,
                'errors' => $errors,
                'credentials' => $credentials,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'linked' => 0,
                'errors' => ['Parent import failed: ' . $e->getMessage()],
                'credentials' => [],
            ];
        }
    }

    /**
     * Stream a CSV file containing generated login credentials for administrative distribution.
     */
    public function exportCredentialsCsv(array $credentials): StreamedResponse
    {
        return response()->stream(function () use ($credentials) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Full Name',
                'Role',
                'Institutional Email / Username',
                'ID / Admission Number',
                'Temporary Password',
                'Profile Detail / Class',
            ]);

            foreach ($credentials as $cred) {
                fputcsv($handle, [
                    $cred['name'] ?? '',
                    strtoupper($cred['role'] ?? ''),
                    $cred['email'] ?? '',
                    $cred['id_number'] ?? '',
                    $cred['password'] ?? '',
                    $cred['detail'] ?? '',
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="generated_credentials_' . date('Y-m-d_His') . '.csv"',
        ]);
    }
    public function importClassesAndSubjectsFromCsv(UploadedFile $file, ?User $actor = null, ?Request $request = null): array
    {
        $rows = $this->parseCsvFile($file);
        if (empty($rows)) {
            return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'errors' => ['The uploaded CSV file is empty.']];
        }

        $headers = array_map(fn ($h) => strtolower(trim(str_replace([' ', '_', '-'], '', (string) $h))), array_shift($rows));
        $classesCreated = 0;
        $subjectsCreated = 0;
        $errors = [];
        $academicYear = $this->normalizeAcademicSession(AcademySetting::get('academic_session'));
        if (!$academicYear) {
            return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'errors' => ['Configure a valid active academic session before importing classes and subjects.']];
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                if (empty(array_filter($row, fn ($value) => trim((string) $value) !== ''))) {
                    continue;
                }

                $data = $this->mapRowToHeaders($headers, $row);
                $className = trim((string) ($data['classname'] ?? $data['class'] ?? ''));
                $gradeLevel = isset($data['gradelevel']) && $data['gradelevel'] !== '' ? (int) $data['gradelevel'] : null;
                $sectionName = strtoupper(trim((string) ($data['section'] ?? '')));
                $streamName = trim((string) ($data['stream'] ?? ''));
                $room = trim((string) ($data['roomnumber'] ?? $data['room'] ?? '')) ?: null;
                $capacity = isset($data['capacity']) && $data['capacity'] !== '' ? (int) $data['capacity'] : null;
                $subjectName = trim((string) ($data['subjectname'] ?? $data['subject'] ?? ''));
                $subjectCode = strtoupper(trim((string) ($data['subjectcode'] ?? $data['code'] ?? '')));
                $weeklyPeriods = isset($data['weeklyperiods']) && $data['weeklyperiods'] !== '' ? (int) $data['weeklyperiods'] : null;

                if (!$gradeLevel && $className && preg_match('/\d+/', $className, $matches)) {
                    $gradeLevel = (int) $matches[0];
                }
                if (!$className && $gradeLevel) {
                    $className = 'Class '.$gradeLevel;
                }
                if (!$className || !$gradeLevel || !in_array($gradeLevel, config('school.grade_levels', []), true)) {
                    $errors[] = "Row {$rowNum}: A valid class name and grade from 1 to 12 are required.";
                    continue;
                }
                if (!preg_match('/^[A-Z]$/', $sectionName)) {
                    $errors[] = "Row {$rowNum}: Section must be one letter from A to Z.";
                    continue;
                }
                if ($streamName === '' || !SchoolStream::active()->where('name', $streamName)->exists()) {
                    $errors[] = "Row {$rowNum}: Select an existing active academic stream; imports cannot create stream master records.";
                    continue;
                }

                $sectionRecord = SchoolSection::where('stream', $streamName)
                    ->where('name', $sectionName)->where('is_active', true)->first();
                if (!$sectionRecord) {
                    $errors[] = "Row {$rowNum}: Section {$sectionName} does not exist as an active section in {$streamName}. Create it in Section Master first.";
                    continue;
                }
                $capacity = $capacity ?? (int) $sectionRecord->capacity;
                if ($capacity < config('school.capacity.minimum') || $capacity > config('school.capacity.maximum')) {
                    $errors[] = "Row {$rowNum}: Capacity must be within the configured school capacity limits, or left blank to use the section's configured capacity.";
                    continue;
                }
                if ($capacity > (int) $sectionRecord->capacity) {
                    $errors[] = "Row {$rowNum}: Capacity cannot exceed the configured section capacity of {$sectionRecord->capacity}.";
                    continue;
                }
                if ($room === null) {
                    $room = $sectionRecord->room_number;
                }

                $academicClass = AcademicClass::where('grade_level', $gradeLevel)
                    ->where('stream', $streamName)
                    ->where('section', $sectionName)
                    ->where('academic_year', $academicYear)
                    ->lockForUpdate()->first();
                if ($academicClass) {
                    if ($academicClass->section_id && (int) $academicClass->section_id !== (int) $sectionRecord->id) {
                        $errors[] = "Row {$rowNum}: Existing class section identity conflicts with Section Master.";
                        continue;
                    }
                    if (!$academicClass->section_id) {
                        $academicClass->update(['section_id' => $sectionRecord->id]);
                    }
                } else {
                    $academicClass = AcademicClass::create([
                        'name' => $className,
                        'grade_level' => $gradeLevel,
                        'stream' => $streamName,
                        'section' => $sectionName,
                        'section_id' => $sectionRecord->id,
                        'academic_year' => $academicYear,
                        'room_number' => $room,
                        'capacity' => $capacity,
                    ]);
                    $classesCreated++;
                }
                $inheritance = app(\App\Services\SubjectCurriculumInheritanceService::class)->inheritForClass($academicClass, $actor, $request);
                $subjectsCreated += $inheritance['created'];

                if ($subjectName !== '') {
                    $masterQuery = SubjectMaster::active()->whereRaw('UPPER(name) = ?', [mb_strtoupper($subjectName)])
                        ->where(function ($query) use ($streamName) {
                            $query->where('stream', $streamName)->orWhere('stream', 'All');
                        })->where('min_grade', '<=', $gradeLevel)->where('max_grade', '>=', $gradeLevel);
                    $master = $subjectCode !== ''
                        ? $masterQuery->whereRaw('UPPER(code) = ?', [preg_replace('/-[0-9]+[A-Za-z]*$/', '', $subjectCode)])->first()
                        : $masterQuery->first();
                    if (!$master) {
                        $errors[] = "Row {$rowNum}: Subject '{$subjectName}' is not an active subject master for {$streamName}; no subject mapping was created.";
                        continue;
                    }
                    $canonicalCode = $master->generateCodeForGrade($gradeLevel);
                    if ($subjectCode !== '' && !in_array($subjectCode, [strtoupper($master->code), strtoupper($canonicalCode)], true)) {
                        $errors[] = "Row {$rowNum}: Subject code does not match the selected subject master.";
                        continue;
                    }
                    $periods = $weeklyPeriods ?? (int) $master->default_periods;
                    if ($periods < 1 || $periods > 40) {
                        $errors[] = "Row {$rowNum}: Weekly periods must be configured in the CSV or subject master.";
                        continue;
                    }

                    $existingSubject = AcademicSubject::where('class_id', $academicClass->id)
                        ->where(function ($query) use ($canonicalCode, $master) {
                            $query->whereRaw('UPPER(code) = ?', [strtoupper($canonicalCode)])
                                ->orWhereRaw('UPPER(name) = ?', [strtoupper($master->name)]);
                        })->first();
                    if ($existingSubject) {
                        if (strtoupper($existingSubject->code) !== strtoupper($canonicalCode) || strtoupper($existingSubject->name) !== strtoupper($master->name) || ($existingSubject->subject_master_id && (int) $existingSubject->subject_master_id !== (int) $master->id)) {
                            $errors[] = "Row {$rowNum}: The class already has a subject with a conflicting name, code or master mapping; no duplicate mapping was created.";
                        } elseif (!$existingSubject->subject_master_id) {
                            $existingSubject->subject_master_id = $master->id;
                            $existingSubject->teacher_id = $existingSubject->teacher_id ?? $master->default_teacher_id;
                            if ($weeklyPeriods) $existingSubject->weekly_periods = $weeklyPeriods;
                            $existingSubject->save();
                        } elseif ($weeklyPeriods) {
                            $existingSubject->update(['weekly_periods' => $weeklyPeriods]);
                        }
                        continue;
                    }
                    AcademicSubject::create([
                        'class_id' => $academicClass->id,
                        'subject_master_id' => $master->id,
                        'teacher_id' => $master->default_teacher_id,
                        'code' => $canonicalCode,
                        'name' => $master->name,
                        'weekly_periods' => $periods,
                    ]);
                    $subjectsCreated++;
                }
            }

            if ($errors) {
                DB::rollBack();
                return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'classes_count' => 0, 'subjects_count' => 0, 'errors' => $errors];
            }

            DB::commit();
            return [
                'success' => true,
                'total' => $classesCreated + $subjectsCreated,
                'created' => $classesCreated + $subjectsCreated,
                'updated' => 0,
                'classes_count' => $classesCreated,
                'subjects_count' => $subjectsCreated,
                'errors' => $errors,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return ['success' => false, 'total' => 0, 'created' => 0, 'updated' => 0, 'errors' => ['Import failed. No changes were committed. '.$e->getMessage()]];
        }
    }

    /**
     * Generate downloadable sample CSV template with demo rows.
     */
    public function getSampleCsv(string $type): StreamedResponse
    {
        $filename = "sample_{$type}_import_template.csv";

        return response()->stream(function () use ($type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'students') {
                fputcsv($handle, [
                    'Admission No', 'Roll No', 'Class', 'Section', 'Stream', 'Student Name', 'Email', 'Phone',
                    'Guardian Name', 'Guardian Phone', 'Guardian Email', 'Blood Group',
                    'Previous School Name', 'Previous School Board', 'Previous School Year',
                    'TC Number', 'Previous School Remarks',
                ]);
            } elseif ($type === 'staff') {
                fputcsv($handle, [
                    'Name',
                    'Email',
                    'Phone',
                    'Role',
                    'Employee ID',
                    'Designation',
                ]);
                fputcsv($handle, [
                    'Dr. Alok Nath',
                    'alok.nath@school.edu',
                    '+91 98111 22334',
                    'teacher',
                    'FAC-1001',
                    'Senior Physics Master (M.Sc, B.Ed)',
                ]);
                fputcsv($handle, [
                    'Sunita Rao',
                    'sunita.rao@school.edu',
                    '+91 98222 33445',
                    'teacher',
                    'FAC-1002',
                    'Head of Mathematics (M.Sc, M.Ed)',
                ]);
                fputcsv($handle, [
                    'Col. R. K. Singh',
                    'rk.singh@school.edu',
                    '+91 98333 44556',
                    'admin',
                    'ADM-1001',
                    'Registrar & Academic Operations Chief',
                ]);
            } elseif ($type === 'classes_subjects') {
                fputcsv($handle, [
                    'Grade Level',
                    'Class Name',
                    'Stream',
                    'Section',
                    'Capacity',
                    'Room Number',
                    'Subject Name',
                    'Subject Code',
                    'Weekly Periods',
                ]);
            } elseif ($type === 'parents') {
                fputcsv($handle, [
                    'Parent Name',
                    'Email',
                    'Phone',
                    'Address',
                    'Student Admission No',
                ]);
                fputcsv($handle, [
                    'Rajesh Sharma',
                    'rajesh.sharma@example.com',
                    '+91 98765 00001',
                    '42 Park View Apts, Civil Lines',
                    'ADM-2026-0001',
                ]);
                fputcsv($handle, [
                    'Vikram Patel',
                    'vikram.patel@example.com',
                    '+91 98765 00002',
                    '18 Heritage Road, South Campus',
                    'ADM-2026-0002',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Helper to read CSV lines into array safely.
     */
    protected function parseCsvFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $rows = [];

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 65536, ',', '"', '\\')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Helper to map parsed row values by lowercase sanitized header keys.
     */
    protected function mapRowToHeaders(array $headers, array $row): array
    {
        $mapped = [];
        foreach ($headers as $i => $header) {
            $mapped[$header] = isset($row[$i]) ? trim((string)$row[$i]) : null;
        }
        return $mapped;
    }
}
