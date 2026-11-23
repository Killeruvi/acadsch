<?php

namespace App\Services;

use App\Models\AcademySetting;
use App\Models\FeeInvoice;
use App\Models\StudentTransportAssignment;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransportManagementService
{
    /**
     * Create or update transport vehicle.
     */
    public function saveVehicle(array $data, ?int $id = null): TransportVehicle
    {
        return DB::transaction(function () use ($data, $id) {
            if ($id) {
                $vehicle = TransportVehicle::findOrFail($id);
                $vehicle->update($data);

                return $vehicle;
            }

            return TransportVehicle::create($data);
        });
    }

    /**
     * Create or update transport route.
     */
    public function saveRoute(array $data, ?int $id = null): TransportRoute
    {
        return DB::transaction(function () use ($data, $id) {
            if ($id) {
                $route = TransportRoute::findOrFail($id);
                $route->update($data);

                return $route;
            }

            return TransportRoute::create($data);
        });
    }

    /**
     * Assign student to bus route and pickup stop.
     */
    public function assignStudent(int $studentId, int $routeId, ?int $stopId = null, ?string $session = null): StudentTransportAssignment
    {
        return DB::transaction(function () use ($studentId, $routeId, $stopId, $session) {
            $session = $session ?? AcademySetting::get('academic_session', '2026-2027');

            // Deactivate any existing active assignment for this session
            StudentTransportAssignment::where('student_id', $studentId)
                ->where('academic_session', $session)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);

            return StudentTransportAssignment::create([
                'student_id' => $studentId,
                'route_id' => $routeId,
                'stop_id' => $stopId,
                'academic_session' => $session,
                'status' => 'active',
                'assigned_date' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Generate monthly transport fees for all active transport students.
     */
    public function generateMonthlyFees(int $month, int $year, ?int $routeId = null): array
    {
        $assignmentsQuery = StudentTransportAssignment::with(['student', 'route'])
            ->where('status', 'active');

        if ($routeId) {
            $assignmentsQuery->where('route_id', $routeId);
        }

        $assignments = $assignmentsQuery->get();
        $generated = 0;
        $totalAmount = 0.0;
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        DB::transaction(function () use ($assignments, $month, $year, $monthName, &$generated, &$totalAmount) {
            foreach ($assignments as $a) {
                if (! $a->student || ! $a->route) {
                    continue;
                }

                $fare = (float) $a->route->monthly_fare;
                if ($fare <= 0) {
                    continue;
                }

                $existingInvoice = FeeInvoice::where('student_id', $a->student_id)
                    ->where('category', 'Transport')
                    ->where('billing_type', 'monthly_auto')
                    ->whereMonth('due_date', $month)
                    ->whereYear('due_date', $year)
                    ->exists();

                if ($existingInvoice) {
                    continue;
                }

                $dueDate = sprintf('%04d-%02d-10', $year, $month);
                $invNumber = 'TRN-'.$year.'-'.strtoupper((string) Str::ulid());

                FeeInvoice::create([
                    'invoice_number' => $invNumber,
                    'student_id' => $a->student_id,
                    'title' => "Transport Fee - {$monthName} {$year} ({$a->route->title})",
                    'category' => 'Transport',
                    'amount' => $fare,
                    'discount' => 0.0,
                    'tax' => 0.0,
                    'total_due' => $fare,
                    'paid_amount' => 0.0,
                    'status' => 'pending',
                    'billing_type' => 'monthly_auto',
                    'due_date' => $dueDate,
                    'notes' => "Automated route transport billing for {$a->route->title}",
                ]);

                $generated++;
                $totalAmount += $fare;
            }
        });

        return [
            'generated_count' => $generated,
            'total_amount' => $totalAmount,
            'month' => $monthName,
            'year' => $year,
        ];
    }
}
