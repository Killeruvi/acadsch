<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Models\BiometricLog;
use App\Services\AttendanceAlertService;
use App\Services\BiometricSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BiometricDeviceController extends Controller
{
    /**
     * Display Biometric Hardware & Punch Logs Manager.
     */
    public function index(Request $request): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $devices = BiometricDevice::withCount('logs')->get();

        $logsQuery = BiometricLog::with(['device', 'user'])
            ->latest('punch_time');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $logsQuery->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $logsQuery->whereDate('punch_time', $request->input('date'));
        }

        $logs = $logsQuery->paginate(25)->withQueryString();

        $today = now()->toDateString();
        $stats = [
            'total_devices' => $devices->count(),
            'online_devices' => $devices->filter->isOnline()->count(),
            'today_punches' => BiometricLog::whereDate('punch_time', $today)->count(),
            'pending_punches' => BiometricLog::where('status', 'pending')->count(),
            'failed_punches' => BiometricLog::where('status', 'failed')->count(),
        ];

        return view('school.attendance.biometric', compact('devices', 'logs', 'stats'));
    }

    /**
     * Register a new physical biometric device.
     */
    public function storeDevice(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'device_name' => 'required|string|max:100',
            'device_serial' => 'required|string|max:100|unique:biometric_devices,device_serial',
            'device_model' => 'nullable|string|max:100',
            'device_ip' => 'nullable|ip',
            'device_location' => 'nullable|string|max:191',
        ]);

        $data['auth_token'] = Str::random(40);
        $data['status'] = 'offline';

        $device = BiometricDevice::create($data);

        return back()->with('success', "Biometric Device '{$device->device_name}' registered successfully with API Token.");
    }

    /**
     * Remove a biometric device registration.
     */
    public function destroyDevice(BiometricDevice $device): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $name = $device->device_name;
        $device->delete();

        return back()->with('success', "Device '{$name}' unregistered.");
    }

    /**
     * Manual sync trigger to process all pending biometric logs.
     */
    public function syncPendingLogs(BiometricSyncService $syncService): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $result = $syncService->processPendingLogs(500);

        return back()->with('success', "Biometric sync executed: {$result['processed']} processed, {$result['failed']} failed out of {$result['total_attempted']} pending punches.");
    }

    /**
     * Dispatch morning absentee alerts to parents.
     */
    public function dispatchAbsenteeAlerts(Request $request, AttendanceAlertService $alertService): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $date = $request->input('date', now()->toDateString());
        $results = $alertService->dispatchDailyAbsenteeAlerts($date);

        return back()->with('success', "Absentee alerts dispatched for {$results['date']}: {$results['in_app_sent']} in-app parent alerts sent, {$results['telegram_sent']} telegram broadcasts, {$results['already_notified']} already notified.");
    }
}
