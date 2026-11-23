<?php

namespace App\Console\Commands;

use App\Services\DatabaseSetupService;
use Illuminate\Console\Command;

class CleanSlateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:clean-slate 
                            {--admin-email=principal@primearc.edu.in : The master administrator email}
                            {--admin-password=password123 : The master administrator password}
                            {--no-admin : Completely wipe all users including administrator for absolute zero-user manual setup}
                            {--force : Force the wipe operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe all operational/test data and reset database to a fresh clean slate for manual testing or new deployment';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseSetupService $dbService): int
    {
        $noAdmin = (bool)$this->option('no-admin');

        $this->warn('====================================================================');
        $this->warn(' CAUTION: SCHOOL MANAGEMENT SYSTEM CLEAN SLATE RESET');
        $this->warn('====================================================================');
        $this->line('This action will delete all classes, subjects, students, teachers,');
        $this->line('timetables, exams, fees, and attendance logs.');
        if ($noAdmin) {
            $this->error('>>> ALL USERS INCLUDING ADMINISTRATOR WILL BE DELETED (0 USERS). <<<');
        } else {
            $this->line('Only ONE clean Master Administrator account will be retained.');
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Are you absolutely sure you want to wipe everything and reset to clean slate?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $email = $this->option('admin-email');
        $password = $this->option('admin-password');

        $result = $dbService->wipeAndInitializeCleanSlate($email, $password, !$noAdmin);

        if (!$result['success']) {
            $this->error('ERROR: ' . $result['message']);
            return self::FAILURE;
        }

        $this->info('✔ ' . $result['message']);

        if ($noAdmin) {
            $this->table(
                ['Key', 'Value'],
                [
                    ['Total Users in Database', '0 (Zero Users)'],
                    ['Master Administrator', 'None (Create via Step 1 of Onboarding)'],
                    ['Onboarding Status', 'Fresh (0% - Unconfigured)'],
                    ['Onboarding Wizard URL', '/school/onboarding'],
                ]
            );
        } else {
            $this->table(
                ['Key', 'Value'],
                [
                    ['Master Administrator Email', $email],
                    ['Master Administrator Password', $password],
                    ['Onboarding Status', 'Fresh (0% - Ready for manual onboarding)'],
                    ['Onboarding Wizard URL', '/school/onboarding'],
                ]
            );
        }

        return self::SUCCESS;
    }
}
