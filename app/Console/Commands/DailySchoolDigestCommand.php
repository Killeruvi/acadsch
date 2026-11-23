<?php

namespace App\Console\Commands;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailySchoolDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'school:daily-digest 
                            {--date= : Target date (YYYY-MM-DD, defaults to today)}';

    /**
     * The console command description.
     */
    protected $description = 'Compile and deliver the end-of-day executive operational briefing for school leadership';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateStr = $this->option('date') ?: Carbon::today()->toDateString();
        $currencySymbol = AcademySetting::get('currency_symbol', '₹');

        $this->info("Compiling daily operational digest for date: {$dateStr}");

        // 1. Attendance Metrics
        $totalSessionsToday = AttendanceSession::whereDate('session_date', $dateStr)->count();
        $totalClasses = AcademicClass::count();
        $missedClasses = max(0, $totalClasses - $totalSessionsToday);

        $attendanceRecords = AttendanceRecord::whereHas('session', function ($q) use ($dateStr) {
            $q->whereDate('session_date', $dateStr);
        })->get();

        $totalMarked = $attendanceRecords->count();
        $presentCount = $attendanceRecords->whereIn('status', ['present', 'late', 'half_day'])->count();
        $absentCount = $attendanceRecords->where('status', 'absent')->count();
        $excusedCount = $attendanceRecords->where('status', 'excused')->count();
        $attendanceRate = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0.0;

        // 2. Financial Metrics
        $todayPayments = FeePayment::settled()
            ->whereDate('paid_at', $dateStr)
            ->get();

        $totalRevenueToday = (float) $todayPayments->sum('amount');
        $upiRevenue = (float) $todayPayments->where('payment_method', 'upi')->sum('amount');
        $cashRevenue = (float) $todayPayments->where('payment_method', 'cash')->sum('amount');
        $otherRevenue = $totalRevenueToday - ($upiRevenue + $cashRevenue);

        $overdueInvoicesCount = FeeInvoice::where('status', 'overdue')->count();
        $overdueBalance = (float) FeeInvoice::where('status', 'overdue')->get()->sum(fn($i) => $i->balance);

        // 3. Operational Exceptions
        $pendingLeaves = StudentLeaveRequest::where('status', 'pending')->count();

        // 4. Deliver Briefing to Administrators
        $admins = User::where('role', 'admin')->get();
        $digestTitle = "📊 Daily Operational Digest — " . date('D, M d, Y', strtotime($dateStr));
        $digestMessage = "Attendance: {$attendanceRate}% ({$presentCount} present, {$absentCount} absent, {$missedClasses} classes unrecorded). Revenue Collected: {$currencySymbol}" . number_format($totalRevenueToday, 2) . " ({$todayPayments->count()} payments). Pending Leaves: {$pendingLeaves}.";

        foreach ($admins as $admin) {
            NotificationService::notifyUser(
                $admin->id,
                'system',
                $digestTitle,
                $digestMessage,
                route('dashboard'),
                '📊'
            );
        }

        // Send Telegram briefing if configured
        try {
            $telegramText = "<b>[Daily School Briefing — " . date('M d, Y', strtotime($dateStr)) . "]</b>\n\n"
                . "🏫 <b>Student Attendance:</b> {$attendanceRate}%\n"
                . "   • Present: {$presentCount}\n"
                . "   • Absent: {$absentCount} (Excused: {$excusedCount})\n"
                . "   • Incomplete Class Registers: {$missedClasses}\n\n"
                . "💰 <b>Fee Treasury Collected:</b> {$currencySymbol}" . number_format($totalRevenueToday, 2) . "\n"
                . "   • UPI: {$currencySymbol}" . number_format($upiRevenue, 2) . "\n"
                . "   • Cash: {$currencySymbol}" . number_format($cashRevenue, 2) . "\n"
                . "   • Other: {$currencySymbol}" . number_format($otherRevenue, 2) . "\n\n"
                . "⚠️ <b>Action Items:</b>\n"
                . "   • Unreviewed Leaves: {$pendingLeaves}\n"
                . "   • Total Overdue Invoices: {$overdueInvoicesCount} ({$currencySymbol}" . number_format($overdueBalance, 2) . ")";

            TelegramService::sendMessage($telegramText);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        $this->table(
            ['Operational Indicator', 'Daily Value'],
            [
                ['Report Date', $dateStr],
                ['Student Attendance Rate', "{$attendanceRate}% ({$presentCount}/{$totalMarked})"],
                ['Students Absent', $absentCount],
                ['Unrecorded Class Registers', $missedClasses],
                ['Today\'s Revenue Collection', "{$currencySymbol}" . number_format($totalRevenueToday, 2)],
                ['Payments Settled Today', $todayPayments->count()],
                ['Pending Leave Applications', $pendingLeaves],
                ['Active Overdue Invoices', "{$overdueInvoicesCount} ({$currencySymbol}" . number_format($overdueBalance, 2) . ")"],
            ]
        );

        $this->info("Daily executive briefing dispatched successfully to " . $admins->count() . " administrators.");
        return self::SUCCESS;
    }
}
