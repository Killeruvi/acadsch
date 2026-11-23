<?php

namespace App\Console\Commands;

use App\Services\HR\StaffLeaveEntitlementService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrueStaffLeaveCommand extends Command
{
    protected $signature = 'staff:accrue-leave {--date=}';
    protected $description = 'Accrue configured staff leave entitlements idempotently';

    public function handle(StaffLeaveEntitlementService $service): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfMonth() : now()->startOfMonth();
        $count = $service->accrueMonthly($date);
        $this->info("Created {$count} leave accrual ledger entries for {$date->format('Y-m')}.");
        return self::SUCCESS;
    }
}
