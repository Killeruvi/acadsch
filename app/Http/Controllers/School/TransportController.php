<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\FeeInvoice;
use App\Models\StudentTransportAssignment;
use App\Models\TransportAuditLog;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\TransportStaff;
use App\Models\TransportTrip;
use App\Models\TransportTripPassenger;
use App\Models\TransportVehicle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransportController extends Controller
{
    /**
     * Display Transport Management Hub.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();
        $tab = $request->query('tab', 'vehicles'); // vehicles, routes, stops, staff, students, trips, reports, audit
        $search = trim((string) $request->query('search', ''));
        $statusFilter = $request->query('status');
        $routeFilter = $request->query('route_id');
        $classFilter = $request->query('class_id');

        // Master data for filters & dropdowns
        $allRoutes = TransportRoute::orderBy('route_title')->get();
        $allVehicles = TransportVehicle::orderBy('vehicle_number')->get();
        $allDrivers = TransportStaff::where('role', 'driver')->orderBy('name')->get();
        $allStaff = TransportStaff::orderBy('name')->get();
        $allClasses = AcademicClass::orderBy('grade_level')->orderBy('section')->get();

        // 1. Vehicles Query
        $vehiclesQuery = TransportVehicle::with(['route', 'driver'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('vehicle_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_model', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%")
                        ->orWhereHas('driver', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($routeFilter, fn ($q) => $q->where('route_id', $routeFilter))
            ->orderBy('vehicle_number');
        $vehicles = $vehiclesQuery->paginate(15, ['*'], 'vehicles_page')->withQueryString();

        // 2. Routes Query
        $routesQuery = TransportRoute::with(['vehicles', 'stops', 'activeStudentAssignments'])
            ->withCount(['vehicles', 'stops', 'activeStudentAssignments as active_students_count'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('route_title', 'like', "%{$search}%")
                        ->orWhere('start_point', 'like', "%{$search}%")
                        ->orWhere('end_point', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->orderBy('route_title');
        $routes = $routesQuery->paginate(15, ['*'], 'routes_page')->withQueryString();

        // 3. Stops Query
        $stopsQuery = TransportRouteStop::with('route')
            ->when($search, function ($q) use ($search) {
                $q->where('stop_name', 'like', "%{$search}%")
                    ->orWhere('landmark', 'like', "%{$search}%")
                    ->orWhereHas('route', fn ($rq) => $rq->where('route_title', 'like', "%{$search}%"));
            })
            ->when($routeFilter, fn ($q) => $q->where('route_id', $routeFilter))
            ->orderBy('route_id')
            ->orderBy('stop_sequence');
        $stops = $stopsQuery->paginate(20, ['*'], 'stops_page')->withQueryString();

        // 4. Staff (Drivers & Attendants) Query
        $staffQuery = TransportStaff::with(['vehicle', 'route'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->orderBy('name');
        $staffMembers = $staffQuery->paginate(15, ['*'], 'staff_page')->withQueryString();

        // 5. Student Assignments Query
        $studentAssignmentsQuery = StudentTransportAssignment::with([
            'student.currentClass',
            'route',
            'stop',
            'vehicle.driver',
        ])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%");
                });
            })
            ->when($routeFilter, fn ($q) => $q->where('route_id', $routeFilter))
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($classFilter, function ($q) use ($classFilter) {
                $q->whereHas('student', fn ($sq) => $sq->where('current_class_id', $classFilter));
            })
            ->latest('id');
        $studentAssignments = $studentAssignmentsQuery->paginate(20, ['*'], 'students_page')->withQueryString();

        // Unassigned students available for transport assignment
        $assignedStudentIds = StudentTransportAssignment::where('status', 'active')->pluck('student_id')->toArray();
        $unassignedStudents = User::where('role', 'student')
            ->whereNotIn('id', $assignedStudentIds)
            ->with('currentClass')
            ->orderBy('name')
            ->limit(100)
            ->get();

        // 6. Live Trips & Dispatch
        $tripsQuery = TransportTrip::with(['route', 'vehicle', 'driver', 'passengers.student'])
            ->withCount('passengers')
            ->latest('trip_date')
            ->latest('id');
        $trips = $tripsQuery->paginate(15, ['*'], 'trips_page')->withQueryString();

        // 7. Audit Trail Query
        $audits = TransportAuditLog::with('user')
            ->latest('created_at')
            ->paginate(25, ['*'], 'audit_page')
            ->withQueryString();

        // Metrics Summary
        $stats = [
            'total_vehicles' => TransportVehicle::count(),
            'active_vehicles' => TransportVehicle::where('status', 'active')->count(),
            'maintenance_vehicles' => TransportVehicle::where('status', 'maintenance')->count(),
            'total_routes' => TransportRoute::count(),
            'total_stops' => TransportRouteStop::count(),
            'total_drivers' => TransportStaff::where('role', 'driver')->count(),
            'total_staff' => TransportStaff::count(),
            'active_riders' => StudentTransportAssignment::where('status', 'active')->count(),
            'opted_out_riders' => StudentTransportAssignment::where('status', 'opted_out')->count(),
            'total_monthly_transport_revenue' => (float) StudentTransportAssignment::where('status', 'active')->get()->sum(fn ($a) => $a->effective_fare),
        ];

        return view('school.transport.index', compact(
            'tab',
            'search',
            'statusFilter',
            'routeFilter',
            'classFilter',
            'allRoutes',
            'allVehicles',
            'allDrivers',
            'allStaff',
            'allClasses',
            'vehicles',
            'routes',
            'stops',
            'staffMembers',
            'studentAssignments',
            'unassignedStudents',
            'trips',
            'audits',
            'stats'
        ));
    }

    // ==========================================
    // 1. VEHICLES CRUD & OPERATIONS
    // ==========================================

    public function storeVehicle(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'vehicle_number' => 'required|string|max:60|unique:transport_vehicles,vehicle_number',
            'vehicle_type' => 'required|string|max:50', 'capacity' => 'required|integer|min:1|max:150',
            'vehicle_model' => 'nullable|string|max:100', 'year_made' => 'nullable|string|max:20',
            'insurance_renewal_date' => 'nullable|date', 'fitness_certificate_expiry' => 'nullable|date',
            'driver_id' => 'nullable|exists:transport_staff,id', 'route_id' => 'nullable|exists:transport_routes,id',
            'status' => 'required|in:active,inactive,maintenance', 'note' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated) {
            if (! empty($validated['route_id']) && ! TransportRoute::whereKey($validated['route_id'])->where('status', 'active')->exists()) {
                return back()->withInput()->with('error', 'Assign the vehicle to an active route.');
            }
            $driver = ! empty($validated['driver_id']) ? TransportStaff::lockForUpdate()->findOrFail($validated['driver_id']) : null;
            if ($driver && ($driver->role !== 'driver' || $driver->status !== 'active' || ! $driver->license_number || ($driver->license_expiry && $driver->license_expiry->isBefore(today())) || $driver->assigned_vehicle_id)) {
                return back()->withInput()->with('error', 'Selected driver must be active and have a valid driving licence.');
            }
            $vehicle = TransportVehicle::create($validated + [
                'driver_name' => $driver?->name, 'driver_phone' => $driver?->contact_number, 'driver_license' => $driver?->license_number,
            ]);
            if ($driver) {
                $driver->update(['assigned_vehicle_id' => $vehicle->id]);
            }
            TransportAuditLog::log('created', 'vehicle', $vehicle->id, "Vehicle {$vehicle->vehicle_number} added to fleet.");

            return redirect()->route('school.transport.index', ['tab' => 'vehicles'])->with('success', 'Vehicle registered successfully.');
        });
    }

    public function updateVehicle(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $vehicle = TransportVehicle::findOrFail($id);
        $validated = $request->validate([
            'vehicle_number' => ['required', 'string', 'max:60', Rule::unique('transport_vehicles')->ignore($vehicle->id)],
            'vehicle_type' => 'required|string|max:50', 'capacity' => 'required|integer|min:1|max:150',
            'vehicle_model' => 'nullable|string|max:100', 'year_made' => 'nullable|string|max:20',
            'insurance_renewal_date' => 'nullable|date', 'fitness_certificate_expiry' => 'nullable|date',
            'driver_id' => 'nullable|exists:transport_staff,id', 'route_id' => 'nullable|exists:transport_routes,id',
            'status' => 'required|in:active,inactive,maintenance', 'note' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($vehicle, $validated) {
            if (! empty($validated['route_id']) && ! TransportRoute::whereKey($validated['route_id'])->where('status', 'active')->exists()) {
                return back()->withInput()->with('error', 'Assign the vehicle to an active route.');
            }
            if ($vehicle->activeStudentAssignments()->exists() && (int) $vehicle->route_id !== (int) ($validated['route_id'] ?? 0)) {
                return back()->withInput()->with('error', 'Reassign active students before changing this vehicle route.');
            }
            $driver = ! empty($validated['driver_id']) ? TransportStaff::lockForUpdate()->findOrFail($validated['driver_id']) : null;
            if ($driver && ($driver->role !== 'driver' || $driver->status !== 'active' || ! $driver->license_number || ($driver->license_expiry && $driver->license_expiry->isBefore(today())) || ($driver->assigned_vehicle_id && (int) $driver->assigned_vehicle_id !== (int) $vehicle->id))) {
                return back()->withInput()->with('error', 'Selected driver must be active and have a valid driving licence.');
            }
            if ((int) $vehicle->capacity > (int) $validated['capacity'] && $vehicle->activeStudentAssignments()->count() > (int) $validated['capacity']) {
                return back()->withInput()->with('error', 'Capacity cannot be reduced below the number of currently assigned students.');
            }
            if ($vehicle->status === 'active' && $validated['status'] !== 'active' && $vehicle->trips()->where('status', 'in_transit')->exists()) {
                return back()->withInput()->with('error', 'A vehicle on an active trip cannot be deactivated or sent to maintenance.');
            }
            if ($vehicle->driver_id && $vehicle->driver_id != ($driver?->id)) {
                TransportStaff::whereKey($vehicle->driver_id)->where('assigned_vehicle_id', $vehicle->id)->update(['assigned_vehicle_id' => null]);
            }
            $vehicle->update($validated + ['driver_name' => $driver?->name, 'driver_phone' => $driver?->contact_number, 'driver_license' => $driver?->license_number]);
            if ($driver) {
                $driver->update(['assigned_vehicle_id' => $vehicle->id]);
            }
            TransportAuditLog::log('updated', 'vehicle', $vehicle->id, "Vehicle {$vehicle->vehicle_number} updated.");

            return redirect()->route('school.transport.index', ['tab' => 'vehicles'])->with('success', 'Vehicle updated successfully.');
        });
    }

    public function updateVehicleStatus(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        $vehicle = TransportVehicle::findOrFail($id);
        $status = $request->validate(['status' => 'required|in:active,inactive,maintenance'])['status'];

        $oldStatus = $vehicle->status;
        if ($status === 'active' && (($vehicle->insurance_renewal_date && $vehicle->insurance_renewal_date->isBefore(today())) || ($vehicle->fitness_certificate_expiry && $vehicle->fitness_certificate_expiry->isBefore(today())))) {
            return back()->with('error', 'Vehicle compliance documents have expired. Update the records before activating it.');
        }
        if ($status !== 'active' && $vehicle->trips()->where('status', 'in_transit')->exists()) {
            return back()->with('error', 'A vehicle on an active trip cannot be deactivated or sent to maintenance.');
        }
        if ($status !== 'active' && $vehicle->activeStudentAssignments()->exists()) {
            return back()->with('error', 'Reassign active students before deactivating or maintaining this vehicle.');
        }
        $vehicle->update(['status' => $status]);

        TransportAuditLog::log('status_changed', 'vehicle', $vehicle->id, "Vehicle {$vehicle->vehicle_number} status changed from {$oldStatus} to {$status}.");

        return redirect()->back()->with('success', "Vehicle {$vehicle->vehicle_number} status set to ".ucfirst($status));
    }

    public function destroyVehicle(int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $vehicle = TransportVehicle::findOrFail($id);
        if ($vehicle->trips()->exists()) {
            return back()->with('error', 'Vehicles with trip history cannot be deleted. Mark the vehicle inactive instead.');
        }
        if ($vehicle->activeStudentAssignments()->exists()) {
            return back()->with('error', 'Reassign active students before removing this vehicle.');
        }
        DB::transaction(function () use ($vehicle) {
            StudentTransportAssignment::where('vehicle_id', $vehicle->id)->update(['vehicle_id' => null]);
            TransportStaff::where('assigned_vehicle_id', $vehicle->id)->update(['assigned_vehicle_id' => null]);
            $number = $vehicle->vehicle_number;
            $id = $vehicle->id;
            $vehicle->delete();
            TransportAuditLog::log('deleted', 'vehicle', $id, "Vehicle {$number} removed from fleet.");
        });

        return redirect()->route('school.transport.index', ['tab' => 'vehicles'])->with('success', 'Vehicle removed from fleet.');
    }

    // ==========================================
    // 2. ROUTES CRUD & OPERATIONS
    // ==========================================

    public function storeRoute(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'route_title' => 'required|string|max:150|unique:transport_routes,route_title',
            'fare' => 'required|numeric|min:0',
            'start_point' => 'nullable|string|max:150',
            'end_point' => 'nullable|string|max:150',
            'distance_km' => 'nullable|numeric|min:0|max:999.99',
            'morning_timing' => 'nullable|string|max:50',
            'evening_timing' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';

        $route = TransportRoute::create($validated);

        TransportAuditLog::log('created', 'route', $route->id, "Transit route '{$route->route_title}' registered (Fare: ₹{$route->fare}).");

        return redirect()->route('school.transport.index', ['tab' => 'routes'])
            ->with('success', "Route '{$route->route_title}' created successfully!");
    }

    public function updateRoute(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        $route = TransportRoute::findOrFail($id);

        $validated = $request->validate([
            'route_title' => ['required', 'string', 'max:150', Rule::unique('transport_routes')->ignore($route->id)],
            'fare' => 'required|numeric|min:0',
            'start_point' => 'nullable|string|max:150',
            'end_point' => 'nullable|string|max:150',
            'distance_km' => 'nullable|numeric|min:0|max:999.99',
            'morning_timing' => 'nullable|string|max:50',
            'evening_timing' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validated['status'] === 'inactive' && ($route->activeStudentAssignments()->exists() || $route->trips()->whereIn('status', ['scheduled', 'in_transit'])->exists())) {
            return back()->withInput()->with('error', 'Reassign active students and finish or cancel scheduled trips before deactivating this route.');
        }

        $route->update($validated);

        TransportAuditLog::log('updated', 'route', $route->id, "Transit route '{$route->route_title}' updated.");

        return redirect()->route('school.transport.index', ['tab' => 'routes'])
            ->with('success', "Route '{$route->route_title}' updated successfully!");
    }

    public function destroyRoute(int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        $route = TransportRoute::findOrFail($id);
        $title = $route->route_title;

        // Retain routes referenced by assignments, fleet records, stops, or trip history.
        if (StudentTransportAssignment::where('route_id', $route->id)->exists() || $route->vehicles()->exists() || $route->stops()->exists() || $route->trips()->exists()) {
            return redirect()->back()->with('error', "Cannot delete route '{$title}' because transport history or linked records exist. Mark it inactive instead.");
        }

        $route->delete();

        TransportAuditLog::log('deleted', 'route', $id, "Route '{$title}' deleted.");

        return redirect()->route('school.transport.index', ['tab' => 'routes'])
            ->with('success', "Route '{$title}' deleted.");
    }

    // ==========================================
    // 3. STOPS CRUD & OPERATIONS
    // ==========================================

    public function storeStop(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $v = $request->validate(['route_id' => 'required|exists:transport_routes,id', 'stop_name' => 'required|string|max:150', 'stop_sequence' => 'required|integer|min:1|max:100', 'pickup_time' => 'nullable|date_format:H:i,h:i A', 'drop_time' => 'nullable|date_format:H:i,h:i A', 'fare' => 'nullable|numeric|min:0', 'latitude' => 'nullable|numeric|between:-90,90', 'longitude' => 'nullable|numeric|between:-180,180', 'landmark' => 'nullable|string|max:150']);

        return DB::transaction(function () use ($v) {
            TransportRoute::lockForUpdate()->findOrFail($v['route_id']);
            if (TransportRouteStop::where('route_id', $v['route_id'])->where('stop_sequence', $v['stop_sequence'])->exists()) {
                return back()->withInput()->with('error', 'That stop sequence is already used on this route.');
            }
            $stop = TransportRouteStop::create($v);
            TransportAuditLog::log('created', 'stop', $stop->id, "Stop {$stop->stop_name} added to route {$stop->route_id}.");

            return redirect()->route('school.transport.index', ['tab' => 'stops', 'route_id' => $stop->route_id])->with('success', 'Stop added.');
        });
    }

    public function updateStop(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $stop = TransportRouteStop::findOrFail($id);
        $v = $request->validate(['route_id' => 'required|exists:transport_routes,id', 'stop_name' => 'required|string|max:150', 'stop_sequence' => 'required|integer|min:1|max:100', 'pickup_time' => 'nullable|date_format:H:i,h:i A', 'drop_time' => 'nullable|date_format:H:i,h:i A', 'fare' => 'nullable|numeric|min:0', 'latitude' => 'nullable|numeric|between:-90,90', 'longitude' => 'nullable|numeric|between:-180,180', 'landmark' => 'nullable|string|max:150']);
        if ($stop->studentAssignments()->where('status', 'active')->exists() && (int) $stop->route_id !== (int) $v['route_id']) {
            return back()->withInput()->with('error', 'Reassign students before moving a stop to another route.');
        }

        return DB::transaction(function () use ($stop, $v) {
            TransportRoute::lockForUpdate()->findOrFail($v['route_id']);
            if (TransportRouteStop::where('route_id', $v['route_id'])->where('stop_sequence', $v['stop_sequence'])->where('id', '!=', $stop->id)->exists()) {
                return back()->withInput()->with('error', 'That stop sequence is already used on this route.');
            }
            $stop->update($v);
            TransportAuditLog::log('updated', 'stop', $stop->id, "Stop {$stop->stop_name} updated.");

            return redirect()->route('school.transport.index', ['tab' => 'stops', 'route_id' => $stop->route_id])->with('success', 'Stop updated.');
        });
    }

    public function destroyStop(int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $stop = TransportRouteStop::findOrFail($id);
        if ($stop->studentAssignments()->exists() || TransportTripPassenger::where('stop_id', $stop->id)->exists()) {
            return back()->with('error', 'A stop referenced by student assignments or trip history cannot be deleted. Reassign students and retain historical stops.');
        }
        $routeId = $stop->route_id;
        DB::transaction(function () use ($stop) {
            $name = $stop->stop_name;
            $id = $stop->id;
            $stop->delete();
            TransportAuditLog::log('deleted', 'stop', $id, "Stop {$name} deleted.");
        });

        return redirect()->route('school.transport.index', ['tab' => 'stops', 'route_id' => $routeId])->with('success', 'Stop removed.');
    }

    // ==========================================
    // 4. DRIVERS & STAFF CRUD
    // ==========================================

    public function storeStaff(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'role' => 'required|in:driver,attendant,conductor',
            'contact_number' => 'required|string|max:30',
            'license_number' => 'required_if:role,driver|nullable|string|max:100',
            'license_expiry' => 'nullable|date',
            'assigned_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'assigned_route_id' => 'nullable|exists:transport_routes,id',
            'status' => 'required|in:active,on_leave,inactive',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validated['role'] === 'driver' && $validated['status'] === 'active' && (! $validated['license_number'] || (! empty($validated['license_expiry']) && $validated['license_expiry'] < today()->toDateString()))) {
            return back()->withInput()->with('error', 'An active driver must have a valid, unexpired licence.');
        }
        if (! empty($validated['assigned_vehicle_id'])) {
            $vehicle = TransportVehicle::findOrFail($validated['assigned_vehicle_id']);
            if ($vehicle->status !== 'active' || ($validated['role'] === 'driver' && $vehicle->driver_id)) {
                return back()->withInput()->with('error', 'Choose an available active vehicle.');
            }
        }

        $staff = TransportStaff::create($validated);

        // Synchronize the vehicle's driver reference for newly assigned drivers.
        if (! empty($validated['assigned_vehicle_id']) && $validated['role'] === 'driver') {
            TransportVehicle::where('id', $validated['assigned_vehicle_id'])->update([
                'driver_id' => $staff->id,
                'driver_name' => $staff->name,
                'driver_phone' => $staff->contact_number,
                'driver_license' => $staff->license_number,
            ]);
        }

        TransportAuditLog::log('created', 'staff', $staff->id, "Transport staff '{$staff->name}' ({$staff->role}) registered.");

        return redirect()->route('school.transport.index', ['tab' => 'staff'])
            ->with('success', "Staff member '{$staff->name}' added successfully!");
    }

    public function updateStaff(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        $staff = TransportStaff::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'role' => 'required|in:driver,attendant,conductor',
            'contact_number' => 'required|string|max:30',
            'license_number' => 'required_if:role,driver|nullable|string|max:100',
            'license_expiry' => 'nullable|date',
            'assigned_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'assigned_route_id' => 'nullable|exists:transport_routes,id',
            'status' => 'required|in:active,on_leave,inactive',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validated['role'] === 'driver' && $validated['status'] === 'active' && (! $validated['license_number'] || (! empty($validated['license_expiry']) && $validated['license_expiry'] < today()->toDateString()))) {
            return back()->withInput()->with('error', 'An active driver must have a valid, unexpired licence.');
        }
        if (! empty($validated['assigned_vehicle_id'])) {
            $vehicle = TransportVehicle::findOrFail($validated['assigned_vehicle_id']);
            if ($vehicle->status !== 'active' || ($validated['role'] === 'driver' && $vehicle->driver_id && $vehicle->driver_id != $staff->id)) {
                return back()->withInput()->with('error', 'Choose an available active vehicle.');
            }
        }
        if ($validated['status'] !== 'active' && $staff->trips()->where('status', 'in_transit')->exists()) {
            return back()->withInput()->with('error', 'A staff member on an active trip cannot be made inactive or placed on leave.');
        }
        $oldVehicleId = $staff->assigned_vehicle_id;
        $wasDriver = $staff->role === 'driver';
        $staff->update($validated);
        if ($wasDriver && ($validated['role'] !== 'driver' || $oldVehicleId != ($validated['assigned_vehicle_id'] ?? null))) {
            TransportVehicle::where('driver_id', $staff->id)->update(['driver_id' => null, 'driver_name' => null, 'driver_phone' => null, 'driver_license' => null]);
        }

        if (! empty($validated['assigned_vehicle_id']) && $validated['role'] === 'driver') {
            TransportVehicle::where('id', $validated['assigned_vehicle_id'])->update([
                'driver_id' => $staff->id,
                'driver_name' => $staff->name,
                'driver_phone' => $staff->contact_number,
                'driver_license' => $staff->license_number,
            ]);
        }

        TransportAuditLog::log('updated', 'staff', $staff->id, "Transport staff '{$staff->name}' updated.");

        return redirect()->route('school.transport.index', ['tab' => 'staff'])
            ->with('success', "Staff member '{$staff->name}' updated!");
    }

    public function reassignStaff(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $staff = TransportStaff::findOrFail($id);
        $v = $request->validate(['assigned_vehicle_id' => 'nullable|exists:transport_vehicles,id', 'assigned_route_id' => 'nullable|exists:transport_routes,id']);

        return DB::transaction(function () use ($staff, $v) {
            $oldVehicleId = $staff->assigned_vehicle_id;
            $vehicle = ! empty($v['assigned_vehicle_id']) ? TransportVehicle::lockForUpdate()->findOrFail($v['assigned_vehicle_id']) : null;
            if ($vehicle && ($vehicle->status !== 'active' || ($staff->role === 'driver' && $vehicle->driver_id && $vehicle->driver_id != $staff->id))) {
                return back()->withInput()->with('error', 'Selected vehicle is unavailable.');
            }
            if ($oldVehicleId && $oldVehicleId != ($vehicle?->id)) {
                TransportVehicle::whereKey($oldVehicleId)->where('driver_id', $staff->id)->update(['driver_id' => null, 'driver_name' => null, 'driver_phone' => null, 'driver_license' => null]);
            }
            $staff->update($v);
            if ($staff->role === 'driver' && $vehicle) {
                $vehicle->update(['driver_id' => $staff->id, 'driver_name' => $staff->name, 'driver_phone' => $staff->contact_number, 'driver_license' => $staff->license_number]);
            }
            TransportAuditLog::log('reassigned', 'staff', $staff->id, "Staff {$staff->name} assignment updated.");

            return back()->with('success', 'Staff assignment updated.');
        });
    }

    public function destroyStaff(int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        $staff = TransportStaff::findOrFail($id);
        $name = $staff->name;
        if ($staff->trips()->exists()) {
            return back()->with('error', 'Staff with trip history cannot be deleted. Mark the staff member inactive instead.');
        }

        // Clear driver fields from vehicles
        TransportVehicle::where('driver_id', $staff->id)->update([
            'driver_id' => null,
            'driver_name' => null,
            'driver_phone' => null,
            'driver_license' => null,
        ]);

        $staff->delete();

        TransportAuditLog::log('deleted', 'staff', $id, "Staff '{$name}' removed from transport personnel.");

        return redirect()->route('school.transport.index', ['tab' => 'staff'])
            ->with('success', "Staff '{$name}' removed.");
    }

    // ==========================================
    // 5. STUDENT TRANSPORT ASSIGNMENT & BULK
    // ==========================================

    public function assignStudent(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $v = $request->validate(['student_id' => 'required|exists:users,id', 'route_id' => 'required|exists:transport_routes,id', 'stop_id' => 'nullable|exists:transport_route_stops,id', 'vehicle_id' => 'nullable|exists:transport_vehicles,id', 'monthly_fare' => 'nullable|numeric|min:0', 'waiver_percentage' => 'nullable|numeric|between:0,100', 'discount_amount' => 'nullable|numeric|min:0', 'generate_invoice' => 'nullable|boolean', 'note' => 'nullable|string|max:500']);

        return DB::transaction(function () use ($v, $request) {
            $student = User::where('role', 'student')->lockForUpdate()->findOrFail($v['student_id']);
            if (StudentTransportAssignment::where('student_id', $student->id)->where('status', 'active')->exists()) {
                return back()->withInput()->with('error', 'Student already has an active transport assignment.');
            }
            $route = TransportRoute::where('status', 'active')->findOrFail($v['route_id']);
            $stop = ! empty($v['stop_id']) ? TransportRouteStop::where('route_id', $route->id)->findOrFail($v['stop_id']) : null;
            $vehicle = ! empty($v['vehicle_id']) ? TransportVehicle::lockForUpdate()->findOrFail($v['vehicle_id']) : $route->vehicles()->where('status', 'active')->lockForUpdate()->first();
            if (! $vehicle || $vehicle->status !== 'active' || (int) $vehicle->route_id !== (int) $route->id) {
                return back()->withInput()->with('error', 'Choose an active vehicle assigned to this route before assigning students.');
            }
            if ($vehicle->insurance_renewal_date && $vehicle->insurance_renewal_date->isBefore(today())) {
                return back()->withInput()->with('error', 'Vehicle insurance has expired.');
            }
            if ($vehicle->fitness_certificate_expiry && $vehicle->fitness_certificate_expiry->isBefore(today())) {
                return back()->withInput()->with('error', 'Vehicle fitness certificate has expired.');
            }
            $count = $vehicle->activeStudentAssignments()->count();
            if ($count >= $vehicle->capacity) {
                return back()->withInput()->with('error', 'Selected vehicle has reached its passenger capacity.');
            }
            $base = isset($v['monthly_fare']) ? (float) $v['monthly_fare'] : (float) (($stop && $stop->fare !== null) ? $stop->fare : $route->fare);
            $discount = (float) ($v['discount_amount'] ?? 0);
            $waiver = (float) ($v['waiver_percentage'] ?? 0);
            if ($discount + ($base * $waiver / 100) > $base) {
                return back()->withInput()->with('error', 'Combined waiver and discount cannot exceed the base monthly fare.');
            }
            $a = StudentTransportAssignment::create(['student_id' => $student->id, 'route_id' => $route->id, 'stop_id' => $stop?->id, 'vehicle_id' => $vehicle->id, 'status' => 'active', 'start_date' => now()->toDateString(), 'monthly_fare' => $base, 'waiver_percentage' => $v['waiver_percentage'] ?? 0, 'discount_amount' => $discount, 'note' => $v['note'] ?? null]);
            $fare = $a->effective_fare;
            $month = now()->format('Y-m');
            if ($request->boolean('generate_invoice', true) && $fare > 0 && ! FeeInvoice::where('student_id', $student->id)->where('category', 'transport')->where('billing_month', $month)->exists()) {
                FeeInvoice::create(['invoice_number' => sprintf('TRN-%s-%06d', $month, $student->id), 'student_id' => $student->id, 'title' => "Transport Fee - {$route->route_title} (".now()->format('F Y').')', 'category' => 'transport', 'amount' => $base, 'discount' => round($base - $fare, 2), 'tax' => 0, 'total_due' => $fare, 'paid_amount' => 0, 'status' => 'unpaid', 'due_date' => now()->addDays(15)->toDateString(), 'billing_type' => 'monthly_auto', 'billing_month' => $month, 'notes' => "Transport fee for {$student->name} - {$route->route_title}"]);
            }
            TransportAuditLog::log('created', 'student_assignment', $a->id, "Student {$student->name} assigned to {$route->route_title}; monthly fare {$fare}.");

            return redirect()->route('school.transport.index', ['tab' => 'students'])->with('success', 'Student assigned to transport.');
        });
    }

    public function bulkAssignStudents(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $v = $request->validate(['student_ids' => 'required|array|min:1|max:200', 'student_ids.*' => 'required|integer|distinct|exists:users,id', 'route_id' => 'required|exists:transport_routes,id', 'stop_id' => 'nullable|exists:transport_route_stops,id', 'vehicle_id' => 'required|exists:transport_vehicles,id', 'waiver_percentage' => 'nullable|numeric|between:0,100', 'discount_amount' => 'nullable|numeric|min:0']);

        return DB::transaction(function () use ($v) {
            $route = TransportRoute::where('status', 'active')->findOrFail($v['route_id']);
            $stop = ! empty($v['stop_id']) ? TransportRouteStop::where('route_id', $route->id)->findOrFail($v['stop_id']) : null;
            $vehicle = TransportVehicle::lockForUpdate()->findOrFail($v['vehicle_id']);
            if ($vehicle->status !== 'active' || (int) $vehicle->route_id !== (int) $route->id) {
                return back()->withInput()->with('error', 'Vehicle must be active and assigned to the selected route.');
            }
            $users = User::whereIn('id', $v['student_ids'])->where('role', 'student')->lockForUpdate()->get();
            if ($users->count() !== count($v['student_ids'])) {
                return back()->withInput()->with('error', 'Every selected account must be a valid student.');
            }
            $fare = (float) (($stop && $stop->fare !== null) ? $stop->fare : $route->fare);
            $discount = (float) ($v['discount_amount'] ?? 0);
            if ($discount > $fare) {
                return back()->withInput()->with('error', 'Discount cannot exceed the monthly fare.');
            }
            $already = StudentTransportAssignment::whereIn('student_id', $v['student_ids'])->where('status', 'active')->pluck('student_id')->all();
            $eligible = $users->reject(fn ($u) => in_array($u->id, $already, true));
            if ($vehicle->activeStudentAssignments()->count() + $eligible->count() > (int) $vehicle->capacity) {
                return back()->withInput()->with('error', 'Bulk assignment would exceed vehicle capacity.');
            }
            foreach ($eligible as $student) {
                StudentTransportAssignment::create(['student_id' => $student->id, 'route_id' => $route->id, 'stop_id' => $stop?->id, 'vehicle_id' => $vehicle->id, 'status' => 'active', 'start_date' => now()->toDateString(), 'monthly_fare' => $fare, 'waiver_percentage' => $v['waiver_percentage'] ?? 0, 'discount_amount' => $discount, 'note' => 'Bulk assigned']);
            }
            TransportAuditLog::log('bulk_assigned', 'student_assignment', null, 'Bulk assigned '.$eligible->count().' students to route '.$route->route_title.'; skipped '.count($already).' existing active assignments.');

            return redirect()->route('school.transport.index', ['tab' => 'students'])->with('success', 'Assigned '.$eligible->count().' students; skipped '.count($already).' already assigned.');
        });
    }

    public function optOutStudent(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];

        return DB::transaction(function () use ($id, $reason) {
            $a = StudentTransportAssignment::lockForUpdate()->findOrFail($id);
            if ($a->status !== 'active') {
                return back()->with('error', 'Only active assignments can be opted out.');
            }
            $a->update(['status' => 'opted_out', 'end_date' => today()->toDateString(), 'opt_out_reason' => $reason]);

            // Recalculate/cancel pending unpaid transport fee invoices
            $currentMonth = now()->format('Y-m');
            FeeInvoice::where('student_id', $a->student_id)
                ->where('category', 'transport')
                ->where('status', 'unpaid')
                ->where('paid_amount', '<=', 0)
                ->where('billing_month', '>=', $currentMonth)
                ->delete();

            TransportAuditLog::log('opted_out', 'student_assignment', $a->id, "Student {$a->student?->name} opted out of route {$a->route?->route_title}. Reason: {$reason}");

            return back()->with('success', 'Student transport assignment ended and uncollected transport invoices removed.');
        });
    }

    public function optInStudent(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        return DB::transaction(function () use ($id) {
            $a = StudentTransportAssignment::lockForUpdate()->findOrFail($id);
            User::whereKey($a->student_id)->lockForUpdate()->firstOrFail();
            if ($a->status === 'active') {
                return back()->with('error', 'This assignment is already active.');
            }
            if (StudentTransportAssignment::where('student_id', $a->student_id)->where('status', 'active')->exists()) {
                return back()->with('error', 'Student already has another active transport assignment.');
            }
            $route = TransportRoute::whereKey($a->route_id)->where('status', 'active')->first();
            $vehicle = $a->vehicle_id ? TransportVehicle::lockForUpdate()->find($a->vehicle_id) : null;
            if (! $route || ! $vehicle || $vehicle->status !== 'active' || (int) $vehicle->route_id !== (int) $route->id) {
                return back()->with('error', 'Assigned route or vehicle is no longer active and valid. Reassign before reactivating.');
            }
            if ($vehicle->activeStudentAssignments()->count() >= (int) $vehicle->capacity) {
                return back()->with('error', 'Vehicle capacity has been reached.');
            }
            $a->update(['status' => 'active', 'end_date' => null, 'opt_out_reason' => null]);
            TransportAuditLog::log('status_changed', 'student_assignment', $a->id, "Student {$a->student?->name} reactivated on route {$route->route_title}.");

            return back()->with('success', 'Student transport reactivated.');
        });
    }

    public function destroyAssignment(int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $a = StudentTransportAssignment::findOrFail($id);
        if ($a->status === 'active') {
            return back()->with('error', 'Opt out or reassign the student instead of deleting an active assignment.');
        }
        if (FeeInvoice::where('student_id', $a->student_id)->where('category', 'transport')->exists() || TransportTripPassenger::where('student_id', $a->student_id)->exists()) {
            return back()->with('error', 'Assignment has financial or trip history and cannot be deleted.');
        }
        $student = $a->student?->name ?? 'Student';
        $id = $a->id;
        $a->delete();
        TransportAuditLog::log('deleted', 'student_assignment', $id, "Historical assignment for {$student} deleted.");

        return back()->with('success', 'Transport assignment removed.');
    }

    // ==========================================
    // 6. TRANSPORT FEES GENERATION
    // ==========================================

    public function generateMonthlyFees(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $v = $request->validate(['billing_month' => 'required|date_format:Y-m', 'route_id' => 'nullable|exists:transport_routes,id', 'due_date' => 'required|date']);
        $month = $v['billing_month'];
        $due = $v['due_date'];

        return DB::transaction(function () use ($v, $month, $due) {
            $q = StudentTransportAssignment::with(['student', 'route', 'stop'])->where('status', 'active')->where(function ($x) use ($month) {
                $x->whereNull('start_date')->orWhereDate('start_date', '<=', date('Y-m-t', strtotime($month.'-01')));
            })->where(function ($x) use ($month) {
                $x->whereNull('end_date')->orWhereDate('end_date', '>=', $month.'-01');
            });
            if (! empty($v['route_id'])) {
                $q->where('route_id', $v['route_id']);
            }
            $generated = 0;
            $skipped = 0;
            foreach ($q->lockForUpdate()->get() as $a) {
                if (! $a->student || $a->effective_fare <= 0) {
                    $skipped++;

                    continue;
                }
                $exists = FeeInvoice::where('student_id', $a->student_id)->where('category', 'transport')->where('billing_month', $month)->exists();
                if ($exists) {
                    $skipped++;

                    continue;
                }
                $base = (float) $a->monthly_fare;
                $fare = $a->effective_fare;
                FeeInvoice::create(['invoice_number' => sprintf('TRN-%s-%06d', $month, $a->student_id), 'student_id' => $a->student_id, 'title' => 'Transport Fee - '.($a->route?->route_title ?? 'Route').' ('.date('F Y', strtotime($month.'-01')).')', 'category' => 'transport', 'amount' => $base, 'discount' => max(0, round($base - $fare, 2)), 'tax' => 0, 'total_due' => $fare, 'paid_amount' => 0, 'status' => 'unpaid', 'due_date' => $due, 'billing_type' => 'monthly_auto', 'billing_month' => $month, 'notes' => 'Monthly transport charge for '.$month]);
                $generated++;
            }
            TransportAuditLog::log('fee_generated', 'fee', null, "Generated {$generated} transport invoices for {$month}; skipped {$skipped}.");

            return redirect()->route('school.transport.index', ['tab' => 'students'])->with('success', "Generated {$generated} invoices for {$month}; skipped {$skipped}.");
        });
    }

    // ==========================================
    // 7. TRIPS & DAILY FLEET DISPATCH
    // ==========================================

    public function storeTrip(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $v = $request->validate(['trip_date' => 'required|date|before_or_equal:today', 'trip_type' => 'required|in:pickup,drop', 'route_id' => 'required|exists:transport_routes,id', 'vehicle_id' => 'nullable|exists:transport_vehicles,id', 'driver_id' => 'nullable|exists:transport_staff,id', 'notes' => 'nullable|string|max:500']);

        return DB::transaction(function () use ($v) {
            $route = TransportRoute::where('status', 'active')->findOrFail($v['route_id']);
            $vehicle = ! empty($v['vehicle_id']) ? TransportVehicle::lockForUpdate()->findOrFail($v['vehicle_id']) : $route->vehicles()->where('status', 'active')->lockForUpdate()->first();
            if (! $vehicle) {
                return back()->withInput()->with('error', 'No active vehicle is assigned to this route. Assign a vehicle before dispatch.');
            }
            $driver = ! empty($v['driver_id']) ? TransportStaff::lockForUpdate()->findOrFail($v['driver_id']) : $vehicle->driver;
            if ($vehicle->status !== 'active' || (int) $vehicle->route_id !== (int) $route->id) {
                return back()->withInput()->with('error', 'Vehicle must be active and assigned to the selected route.');
            }
            if (! $driver) {
                return back()->withInput()->with('error', 'Assign a licensed driver to this vehicle before dispatch.');
            }
            if ($driver->role !== 'driver' || $driver->status !== 'active' || ! $driver->license_number || ($driver->license_expiry && $driver->license_expiry->isBefore(today()))) {
                return back()->withInput()->with('error', 'Driver must be active with a valid licence.');
            }
            if ((int) $vehicle->driver_id !== (int) $driver->id) {
                return back()->withInput()->with('error', 'Selected driver is not assigned to this vehicle.');
            }
            if (($vehicle->insurance_renewal_date && $vehicle->insurance_renewal_date->isBefore(today())) || ($vehicle->fitness_certificate_expiry && $vehicle->fitness_certificate_expiry->isBefore(today()))) {
                return back()->withInput()->with('error', 'Vehicle compliance documents have expired.');
            }
            if (TransportTrip::where('trip_date', $v['trip_date'])->where('vehicle_id', $vehicle->id)->whereIn('status', ['scheduled', 'in_transit'])->exists()) {
                return back()->withInput()->with('error', 'Vehicle already has an unfinished trip on this date.');
            }
            if (TransportTrip::where('trip_date', $v['trip_date'])->where('driver_id', $driver->id)->whereIn('status', ['scheduled', 'in_transit'])->exists()) {
                return back()->withInput()->with('error', 'Driver already has an unfinished trip on this date.');
            }
            $assignments = StudentTransportAssignment::where('route_id', $route->id)->where('vehicle_id', $vehicle->id)->where('status', 'active')->get();
            if ($assignments->count() > $vehicle->capacity) {
                return back()->withInput()->with('error', 'Assigned passenger count exceeds vehicle capacity. Resolve assignments before dispatch.');
            }
            $trip = TransportTrip::create(['trip_date' => $v['trip_date'], 'trip_type' => $v['trip_type'], 'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'status' => 'in_transit', 'started_at' => now(), 'notes' => $v['notes'] ?? null]);
            foreach ($assignments as $a) {
                TransportTripPassenger::firstOrCreate(['trip_id' => $trip->id, 'student_id' => $a->student_id], ['stop_id' => $a->stop_id, 'status' => 'pending']);
            }
            TransportAuditLog::log('trip_started', 'trip', $trip->id, "Trip {$trip->id} dispatched with {$assignments->count()} assigned passengers.");

            return redirect()->route('school.transport.index', ['tab' => 'trips'])->with('success', 'Trip dispatched with passenger manifest.');
        });
    }

    public function endTrip(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        return DB::transaction(function () use ($id) {
            $trip = TransportTrip::lockForUpdate()->findOrFail($id);
            if ($trip->status !== 'in_transit') {
                return back()->with('error', 'Only an in-transit trip can be completed.');
            }
            if ($trip->passengers()->where('status', 'pending')->exists()) {
                return back()->with('error', 'Resolve every pending passenger status before closing this trip.');
            }
            if ($trip->trip_type === 'drop' && $trip->passengers()->where('status', 'boarded')->exists()) {
                return back()->with('error', 'Complete passenger drop-off statuses before closing this trip.');
            }
            $trip->update(['status' => 'completed', 'ended_at' => now()]);
            TransportAuditLog::log('trip_ended', 'trip', $trip->id, "Trip {$trip->id} completed.");

            return back()->with('success', 'Trip completed.');
        });
    }

    public function updatePassengerStatus(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();
        $status = $request->validate(['status' => 'required|in:pending,boarded,dropped,absent'])['status'];

        return DB::transaction(function () use ($id, $status) {
            $passenger = TransportTripPassenger::with('trip')->lockForUpdate()->findOrFail($id);
            if ($passenger->trip->status !== 'in_transit') {
                return back()->with('error', 'Passenger manifest is locked because the trip is not in transit.');
            }
            if ($passenger->trip->trip_type === 'pickup' && $status === 'dropped') {
                return back()->with('error', 'Pickup trips cannot record a drop-off status.');
            }
            if ($passenger->trip->trip_type === 'drop' && $status === 'boarded') {
                return back()->with('error', 'Drop trips cannot record a pickup boarding status.');
            }
            $passenger->update(['status' => $status, 'confirmed_at' => $status === 'pending' ? null : now()]);
            TransportAuditLog::log('passenger_status_changed', 'trip_passenger', $passenger->id, "Passenger {$passenger->student_id} marked {$status} on trip {$passenger->trip_id}.");

            return back()->with('success', 'Passenger status updated.');
        });
    }

    // ==========================================
    // 8. REPORTS
    // ==========================================

    public function reportRouteWise(): View
    {
        $this->authorizeAdmin();
        $routes = TransportRoute::with([
            'vehicles.driver',
            'stops',
            'activeStudentAssignments.student.currentClass',
            'activeStudentAssignments.stop',
        ])->get();

        return view('school.transport.reports.route-wise', compact('routes'));
    }

    public function reportVehicleWise(): View
    {
        $this->authorizeAdmin();
        $vehicles = TransportVehicle::with([
            'route',
            'driver',
            'activeStudentAssignments.student.currentClass',
        ])->get();

        return view('school.transport.reports.vehicle-wise', compact('vehicles'));
    }

    public function reportDriverWise(): View
    {
        $this->authorizeAdmin();
        $drivers = TransportStaff::with(['vehicle.route', 'route'])
            ->where('role', 'driver')
            ->get();

        return view('school.transport.reports.driver-wise', compact('drivers'));
    }

    /**
     * Helper to enforce administrative permissions.
     */
    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized access to Transport Fleet controls.');
        }
    }
}
