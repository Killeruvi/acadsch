<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Models\BiometricLog;
use App\Services\BiometricSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiometricApiController extends Controller
{
    /**
     * Ingest biometric punch logs from physical devices (ZKTeco ADMS, eSSL, RFID readers).
     */
    public function push(Request $request, BiometricSyncService $syncService): JsonResponse
    {
        $device = null;
        $token = $request->header('X-Device-Token') ?? $request->input('auth_token');
        if ($token) {
            $device = BiometricDevice::where('auth_token', $token)->first();
            if ($device) {
                $device->update([
                    'last_heartbeat' => now(),
                    'status' => 'online',
                    'device_ip' => $request->ip(),
                ]);
            }
        }

        $punches = $request->input('punches');

        // Handle single punch payload
        if (! is_array($punches)) {
            $validated = $request->validate([
                'pin' => 'required|string|max:50',
                'punch_time' => 'required|date',
                'type' => 'nullable|string|in:in,out,auto',
            ]);

            $log = $syncService->recordRawPunch(
                $validated['pin'],
                $validated['punch_time'],
                $device?->id,
                $validated['type'] ?? 'auto'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Biometric punch recorded successfully.',
                'log_id' => $log->id,
                'sync_status' => $log->status,
            ], 201);
        }

        // Handle bulk punches array
        $request->validate([
            'punches.*.pin' => 'required|string|max:50',
            'punches.*.punch_time' => 'required|date',
            'punches.*.type' => 'nullable|string|in:in,out,auto',
        ]);

        $recorded = 0;
        foreach ($punches as $p) {
            $syncService->recordRawPunch(
                $p['pin'],
                $p['punch_time'],
                $device?->id,
                $p['type'] ?? 'auto'
            );
            $recorded++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Successfully recorded {$recorded} biometric punch transactions.",
            'count' => $recorded,
        ], 201);
    }

    /**
     * Biometric machine heartbeat ping.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:100',
            'ip' => 'nullable|string|max:45',
        ]);

        $device = BiometricDevice::where('device_serial', $validated['serial_number'])->first();

        if ($device) {
            $device->update([
                'last_heartbeat' => now(),
                'status' => 'online',
                'device_ip' => $validated['ip'] ?? $request->ip(),
            ]);

            return response()->json([
                'status' => 'acknowledged',
                'server_time' => now()->toIso8601String(),
                'device_id' => $device->id,
                'device_name' => $device->device_name,
            ]);
        }

        return response()->json([
            'status' => 'unregistered_device',
            'message' => 'Device serial number not found in authorized devices register.',
        ], 404);
    }

    /**
     * Get system clock and pending logs status for synchronization agents.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'system_time' => now()->toIso8601String(),
            'pending_logs' => BiometricLog::where('status', 'pending')->count(),
            'total_logs_today' => BiometricLog::whereDate('punch_time', now()->toDateString())->count(),
        ]);
    }
}
