<?php

namespace App\Services;

use App\Models\User;

class StudentIdCardVerificationService
{
    public function signature(User $student): ?string
    {
        $key = (string) config('app.key');
        $admissionNumber = trim((string) $student->admission_number);

        if ($key === '' || $admissionNumber === '' || $student->role !== 'student') {
            return null;
        }

        $expiry = $student->card_valid_until?->toDateString() ?? '';
        $payload = implode(':', ['student-id-card-v1', $student->id, $admissionNumber, $expiry]);

        return hash_hmac('sha256', $payload, $key);
    }

    public function verificationUrl(User $student): ?string
    {
        $signature = $this->signature($student);

        if ($signature === null) {
            return null;
        }

        return route('school.students.verify-id', $student->id.'.'.$signature);
    }

    public function verifies(User $student, string $signature): bool
    {
        $expected = $this->signature($student);

        return $expected !== null && hash_equals($expected, strtolower($signature));
    }
}
