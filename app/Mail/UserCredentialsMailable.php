<?php

namespace App\Mail;

use App\Models\User;
use App\Services\SchoolBrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCredentialsMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
        public array $school = []
    ) {
        if (empty($this->school)) {
            $this->school = SchoolBrandingService::get();
        }
    }

    public function build(): self
    {
        $schoolName = $this->school['name'] ?? config('app.name', 'School Portal');
        $subject = "Welcome to {$schoolName} — Your Official Login Credentials";

        return $this->subject($subject)
            ->view('emails.user-credentials')
            ->with([
                'user' => $this->user,
                'password' => $this->plainPassword,
                'school' => $this->school,
                'loginUrl' => route('login'),
            ]);
    }
}
