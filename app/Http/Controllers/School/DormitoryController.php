<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryBedAssignment;
use App\Models\DormitoryRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DormitoryController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only administrators can manage residential facilities.');
    }

    public function index(): View
    {
        $this->ensureAdmin();
        $dormitories = Dormitory::with('rooms')->latest()->paginate(10, ['*'], 'dorms_page')->withQueryString();
        $rooms = DormitoryRoom::with('dormitory')->withCount(['bedAssignments as occupied_beds' => fn ($q) => $q->whereIn('status', ['reserved', 'active'])])->latest()->paginate(10, ['*'], 'rooms_page')->withQueryString();
        $assignments = DormitoryBedAssignment::with(['room.dormitory', 'student'])->latest()->paginate(15, ['*'], 'assignments_page')->withQueryString();
        $students = User::query()->where('role', 'student')->orderBy('name')->get(['id', 'name', 'admission_number', 'student_id_number']);
        $activeRooms = DormitoryRoom::where('is_active', true)->whereHas('dormitory', fn ($q) => $q->where('is_active', true))->orderBy('room_number')->get();

        $stats = [
            'total_dormitories' => Dormitory::count(),
            'total_rooms' => DormitoryRoom::count(),
            'total_beds' => (int) DormitoryRoom::where('is_active', true)->sum('number_of_beds'),
            'occupied_beds' => DormitoryBedAssignment::whereIn('status', ['reserved', 'active'])->whereDate('start_date', '<=', today())->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))->count(),
            'active_residents' => DormitoryBedAssignment::where('status', 'active')->count(),
        ];

        return view('school.dormitory.index', compact('dormitories', 'rooms', 'assignments', 'students', 'activeRooms', 'stats'));
    }

    public function storeDormitory(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'dormitory_name' => 'required|string|max:150',
            'type' => 'required|in:boys,girls,mixed',
            'address' => 'nullable|string|max:255',
            'intake_capacity' => 'required|integer|min:0|max:10000',
            'description' => 'nullable|string|max:500',
            'warden_name' => 'nullable|string|max:150',
            'warden_phone' => 'nullable|string|max:30',
        ]);
        $validated['is_active'] = true;
        Dormitory::create($validated);

        return back()->with('success', 'Dormitory created successfully.');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'dormitory_id' => 'required|integer|exists:dormitories,id',
            'room_number' => 'required|string|max:40',
            'room_type' => 'required|in:single,double,dorm',
            'number_of_beds' => 'required|integer|min:1|max:50',
            'cost_per_bed' => 'required|numeric|min:0|max:10000000',
            'description' => 'nullable|string|max:1000',
        ]);
        $dormitory = Dormitory::findOrFail($validated['dormitory_id']);
        abort_unless($dormitory->is_active, 422, 'Rooms cannot be added to an inactive dormitory.');
        $exists = DormitoryRoom::where('dormitory_id', $dormitory->id)->whereRaw('LOWER(room_number) = ?', [mb_strtolower(trim($validated['room_number']))])->exists();
        if ($exists) {
            throw ValidationException::withMessages(['room_number' => 'This room number already exists in the selected dormitory.']);
        }
        $validated['room_number'] = trim($validated['room_number']);
        $validated['is_active'] = true;
        DormitoryRoom::create($validated);

        return back()->with('success', 'Room added successfully.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'dormitory_room_id' => 'required|integer|exists:dormitory_rooms,id',
            'student_id' => 'required|integer|exists:users,id',
            'bed_number' => 'required|string|max:30',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'monthly_fee' => 'required|numeric|min:0|max:10000000',
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($validated) {
            $room = DormitoryRoom::with('dormitory')->lockForUpdate()->findOrFail($validated['dormitory_room_id']);
            $dormitory = Dormitory::lockForUpdate()->findOrFail($room->dormitory_id);
            $student = User::where('role', 'student')->lockForUpdate()->findOrFail($validated['student_id']);
            if (! $room->is_active || ! $dormitory->is_active) {
                throw ValidationException::withMessages(['dormitory_room_id' => 'The selected room or dormitory is inactive.']);
            }
            $bedNumber = trim($validated['bed_number']);
            if (! preg_match('/^[1-9][0-9]*$/', $bedNumber) || (int) $bedNumber > $room->number_of_beds) {
                throw ValidationException::withMessages(['bed_number' => 'Choose a bed number within this room’s configured capacity.']);
            }

            $overlap = fn ($q) => $q->whereDate('start_date', '<=', $validated['end_date'] ?? '9999-12-31')
                ->where(fn ($sub) => $sub->whereNull('end_date')->orWhereDate('end_date', '>=', $validated['start_date']))
                ->whereIn('status', ['reserved', 'active']);

            if (DormitoryBedAssignment::where('dormitory_room_id', $room->id)->where('bed_number', $bedNumber)->where($overlap)->exists()) {
                throw ValidationException::withMessages(['bed_number' => 'This bed is already reserved or occupied during the selected dates.']);
            }
            if (DormitoryBedAssignment::where('student_id', $student->id)->where($overlap)->exists()) {
                throw ValidationException::withMessages(['student_id' => 'This student already has an overlapping residential assignment.']);
            }
            $activeCount = DormitoryBedAssignment::where('dormitory_room_id', $room->id)->where($overlap)->count();
            if ($activeCount >= $room->number_of_beds) {
                throw ValidationException::withMessages(['dormitory_room_id' => 'The room has no available beds for the selected period.']);
            }
            $dormitoryResidents = DormitoryBedAssignment::whereHas('room', fn ($q) => $q->where('dormitory_id', $room->dormitory_id))->where($overlap)->count();
            if ($dormitory->intake_capacity > 0 && $dormitoryResidents >= $dormitory->intake_capacity) {
                throw ValidationException::withMessages(['dormitory_room_id' => 'The dormitory has reached its configured intake capacity.']);
            }

            DormitoryBedAssignment::create([
                ...$validated,
                'bed_number' => $bedNumber,
                'status' => 'reserved',
                'created_by' => Auth::id(),
            ]);
        }, 3);

        return back()->with('success', 'Bed reserved successfully. Check the student in on their arrival date.');
    }

    public function checkIn(int $id): RedirectResponse
    {
        return $this->changeAssignmentStatus($id, 'check_in');
    }

    public function checkOut(int $id): RedirectResponse
    {
        return $this->changeAssignmentStatus($id, 'check_out');
    }

    public function cancelAssignment(int $id): RedirectResponse
    {
        return $this->changeAssignmentStatus($id, 'cancel');
    }

    private function changeAssignmentStatus(int $id, string $action): RedirectResponse
    {
        $this->ensureAdmin();
        DB::transaction(function () use ($id, $action) {
            $assignment = DormitoryBedAssignment::lockForUpdate()->findOrFail($id);
            if ($action === 'check_in') {
                if ($assignment->status !== 'reserved' || $assignment->start_date->toDateString() > today()->toDateString() || ($assignment->end_date && $assignment->end_date->toDateString() < today()->toDateString())) {
                    throw ValidationException::withMessages(['assignment' => 'Only a current, reserved bed can be checked in.']);
                }
                $assignment->update(['status' => 'active', 'checked_in_at' => now()]);
            } elseif ($action === 'check_out') {
                if ($assignment->status !== 'active') {
                    throw ValidationException::withMessages(['assignment' => 'Only an active resident can be checked out.']);
                }
                $assignment->update(['status' => 'completed', 'checked_out_at' => now(), 'end_date' => today()]);
            } else {
                if (! in_array($assignment->status, ['reserved', 'active'], true)) {
                    throw ValidationException::withMessages(['assignment' => 'Only reserved or active assignments can be cancelled.']);
                }
                $assignment->update(['status' => 'cancelled', 'checked_out_at' => $assignment->status === 'active' ? now() : null, 'end_date' => today()]);
            }
        }, 3);

        return back()->with('success', 'Residential assignment updated.');
    }
}
