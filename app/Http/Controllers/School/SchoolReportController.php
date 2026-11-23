<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\SchoolExamTerm;
use App\Services\SchoolBrandingService;
use App\Services\SchoolReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolReportController extends Controller
{
    protected SchoolReportService $reportService;

    public function __construct(SchoolReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display centralized Institutional Reports Hub.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $requestedCategory = $request->query('category');
        $requestedReportType = $request->query('report_type');

        // Explicit security check: non-admin requesting admin-only category or report
        if ($requestedCategory && isset(SchoolReportService::CATEGORIES[$requestedCategory])) {
            $catAllowed = !SchoolReportService::CATEGORIES[$requestedCategory]['admin_only']
                || $user->isAdmin()
                || ($user->isCashier() && $requestedCategory === 'finance');
            if (!$catAllowed) {
                abort(403, 'Unauthorized access to institutional financial reports.');
            }
        }
        if ($requestedReportType && isset(SchoolReportService::REPORTS[$requestedReportType])) {
            $rep = SchoolReportService::REPORTS[$requestedReportType];
            $repAllowed = !$rep['admin_only']
                || $user->isAdmin()
                || ($user->isCashier() && $rep['category'] === 'finance');
            if (!$repAllowed) {
                abort(403, 'Unauthorized access to institutional financial reports.');
            }
        }

        // 1. Determine active Category
        $availableCategories = $this->reportService->getCategories($user);
        $firstCatKey = array_key_first($availableCategories) ?? 'academic';
        $activeCategory = $requestedCategory ?: $firstCatKey;

        if (!isset($availableCategories[$activeCategory])) {
            $activeCategory = $firstCatKey;
        }

        // 2. Determine active Report Type
        $availableReports = $this->reportService->getReportsForCategory($activeCategory, $user);
        $firstRepKey = array_key_first($availableReports) ?? 'exam_merit_list';
        $activeReportType = $requestedReportType ?: $firstRepKey;

        if (!isset($availableReports[$activeReportType])) {
            $activeReportType = $firstRepKey;
        }

        // 3. Assemble Filters
        $filters = [
            'class_id' => $request->query('class_id'),
            'term_id' => $request->query('term_id'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'threshold' => $request->query('threshold', 75),
            'gateway' => $request->query('gateway'),
            'student_id' => $request->query('student_id'),
        ];

        // 4. Generate Report Data
        $reportData = $this->reportService->generateReport($activeReportType, $filters, $user);

        // 5. Filter Dropdown Options
        $classes = AcademicClass::orderBy('grade_level')->orderBy('section')->get();
        $terms = SchoolExamTerm::orderBy('start_date', 'desc')->get();
        $gateways = [
            'cash'   => 'Cash Counter',
            'upi'    => 'UPI Transfer',
            'qr'     => 'QR Code Scan',
            'bank'   => 'Bank Transfer / NEFT',
            'cheque' => 'Cheque',
            'manual' => 'Other / Manual',
        ];

        return view('school.reports.index', [
            'categories' => $availableCategories,
            'activeCategory' => $activeCategory,
            'reports' => $availableReports,
            'activeReportType' => $activeReportType,
            'reportData' => $reportData,
            'filters' => $filters,
            'classes' => $classes,
            'terms' => $terms,
            'gateways' => $gateways,
            'school' => SchoolBrandingService::get(),
        ]);
    }

    /**
     * Export currently filtered report to downloadable CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $reportType = $request->query('report_type', 'exam_merit_list');

        $filters = [
            'class_id' => $request->query('class_id'),
            'term_id' => $request->query('term_id'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'threshold' => $request->query('threshold', 75),
            'gateway' => $request->query('gateway'),
            'student_id' => $request->query('student_id'),
        ];

        return $this->reportService->exportCsv($reportType, $filters, $user);
    }

    /**
     * Dedicated institutional print view (PDF / Hard Copy optimized).
     */
    public function print(Request $request): View
    {
        $user = Auth::user();
        $reportType = $request->query('report_type', 'exam_merit_list');

        $filters = [
            'class_id' => $request->query('class_id'),
            'term_id' => $request->query('term_id'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'threshold' => $request->query('threshold', 75),
            'gateway' => $request->query('gateway'),
            'student_id' => $request->query('student_id'),
        ];

        $reportData = $this->reportService->generateReport($reportType, $filters, $user);
        $school = SchoolBrandingService::get();

        // Selected class name if filtered
        $selectedClassName = null;
        if (!empty($filters['class_id'])) {
            $cls = AcademicClass::find($filters['class_id']);
            $selectedClassName = $cls ? $cls->name : null;
        }

        return view('school.reports.print', [
            'reportData' => $reportData,
            'filters' => $filters,
            'selectedClassName' => $selectedClassName,
            'school' => $school,
            'printedBy' => $user->name,
        ]);
    }
}
