<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademySetting;
use App\Models\SchoolAdminAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;

class AdministrationController extends Controller
{
    public function index(): View
    {
        $settings = AcademySetting::pluck('value', 'key')->toArray();
        $counts = [
            'students' => User::where('role', 'student')->count(),
            'parents' => User::where('role', 'parent')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'admins' => User::where('role', 'admin')->count(),
        ];
        $auditLogs = SchoolAdminAudit::with('user:id,name')->latest()->paginate(25);
        $checks = [
            'School name configured' => ! empty($settings['institute_name']),
            'Campus name configured' => ! empty($settings['campus_name']),
            'Academic session configured' => ! empty($settings['academic_session']),
            'Institution contact configured' => ! empty($settings['institution_phone']) || ! empty($settings['institution_email']),
            'School address configured' => ! empty($settings['institution_address']),
        ];

        return view('school.administration.index', compact('counts', 'checks', 'auditLogs'));
    }

    public function exportAudit(Request $request): StreamedResponse
    {
        $from = $request->date('from');
        $to = $request->date('to');
        abort_if($from && $to && $from->gt($to), 422, 'Start date must be before end date.');

        $query = SchoolAdminAudit::with('user:id,name')->orderBy('id');
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Timestamp', 'Administrator', 'Action', 'Description', 'IP Address', 'Old Values', 'New Values']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [$row->created_at?->toDateTimeString(), $row->user?->name ?? 'Deleted user', $row->action, $row->description, $row->ip_address, json_encode($row->old_values), json_encode($row->new_values)]);
                }
            });
            fclose($out);
        }, 'school-admin-audit-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
