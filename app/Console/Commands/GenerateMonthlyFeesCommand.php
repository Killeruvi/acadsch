<?php

namespace App\Console\Commands;

use App\Services\MonthlyFeeService;
use Illuminate\Console\Command;

class GenerateMonthlyFeesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fees:generate-monthly 
                            {--month= : Target month (1-12)}
                            {--year= : Target 4-digit year}
                            {--class= : Filter by Class ID}
                            {--batch= : Filter by Batch ID}
                            {--amount= : Custom monthly fee amount}
                            {--due= : Custom due date (YYYY-MM-DD)}
                            {--title= : Custom invoice title}
                            {--force : Force generation even if monthly automation is disabled in settings}';

    /**
     * The console command description.
     */
    protected $description = 'Generate recurring monthly fee invoices for enrolled students';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = MonthlyFeeService::getMonthlyFeeConfig();

        if (! $config['auto_enabled'] && ! $this->option('force')) {
            $this->warn('Monthly fee auto-generation is currently disabled in Academy Settings.');
            $this->line('Use --force to run anyway, or enable it in the Fees configuration dashboard.');

            return self::SUCCESS;
        }

        $month = $this->option('month') ? (int) $this->option('month') : null;
        $year = $this->option('year') ? (int) $this->option('year') : null;
        $classId = $this->option('class') ? (int) $this->option('class') : null;
        $batchId = $this->option('batch') ? (int) $this->option('batch') : null;
        $amount = $this->option('amount') ? (float) $this->option('amount') : null;
        $due = $this->option('due') ?: null;
        $title = $this->option('title') ?: null;

        $this->info('Initializing monthly fee generation...');

        $result = MonthlyFeeService::generateMonthlyInvoices(
            month: $month,
            year: $year,
            classId: $classId,
            batchId: $batchId,
            customAmount: $amount,
            customDueDate: $due,
            customTitle: $title
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Billing Month', "{$result['month_name']} {$result['year']} ({$result['billing_month']})"],
                ['Due Date', $result['due_date']],
                ['Fee per Student', number_format($result['amount_per_student'], 2)],
                ['Total Eligible Students', $result['total_candidates']],
                ['Invoices Generated', $result['generated_count']],
                ['Skipped (Already Invoiced)', $result['skipped_count']],
                ['Total Invoiced Amount', number_format($result['total_amount_invoiced'], 2)],
            ]
        );

        $this->info('Monthly fee billing cycle finished successfully.');

        return self::SUCCESS;
    }
}
