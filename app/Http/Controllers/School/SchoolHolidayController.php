<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicHoliday;
use App\Models\AcademySetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolHolidayController extends Controller
{
    /**
     * Display the Academic Holiday & Festival Calendar view.
     * Visible to all roles: Admin, Teacher, Student, Parent (Rule 15).
     */
    public function index(Request $request): View
    {
        $request->validate(['year' => ['nullable', 'integer', 'min:2000', 'max:2100']]);
        $activeSession = (string) AcademySetting::get('academic_session', '');
        preg_match('/^(\d{4})/', $activeSession, $matches);
        $defaultYear = isset($matches[1]) ? (int) $matches[1] : (int) now()->format('Y');
        $year = (int) $request->query('year', $defaultYear);
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        // A multi-day break can begin in the previous year and still overlap this year.
        $holidays = AcademicHoliday::query()
            ->whereDate('holiday_date', '<=', $yearEnd->toDateString())
            ->where(function ($query) use ($yearStart) {
                $query->whereDate('end_date', '>=', $yearStart->toDateString())
                    ->orWhere(function ($singleDay) use ($yearStart) {
                        $singleDay->whereNull('end_date')->whereDate('holiday_date', '>=', $yearStart->toDateString());
                    });
            })
            ->orderBy('holiday_date')
            ->paginate(18)
            ->withQueryString();

        $weekendDays = config('academic_calendar.weekend_days', config('attendance.weekend_days', ['Sunday']));
        $totalDays = $yearStart->daysInYear;
        $weekendCount = 0;
        $holidayInstructionalDates = [];
        for ($date = $yearStart->copy(); $date->lte($yearEnd); $date->addDay()) {
            if (in_array($date->format('l'), $weekendDays, true)) {
                $weekendCount++;
            }
        }
        AcademicHoliday::query()
            ->where('is_instructional_off', true)
            ->whereDate('holiday_date', '<=', $yearEnd->toDateString())
            ->where(function ($query) use ($yearStart) {
                $query->whereDate('end_date', '>=', $yearStart->toDateString())
                    ->orWhere(function ($singleDay) use ($yearStart) {
                        $singleDay->whereNull('end_date')->whereDate('holiday_date', '>=', $yearStart->toDateString());
                    });
            })->get()->each(function ($holiday) use ($yearStart, $yearEnd, &$holidayInstructionalDates) {
                $start = Carbon::parse($holiday->holiday_date)->max($yearStart);
                $end = Carbon::parse($holiday->end_date ?? $holiday->holiday_date)->min($yearEnd);
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $holidayInstructionalDates[$date->toDateString()] = true;
                }
            });

        $instructionalOffDays = count($holidayInstructionalDates);
        $weekendHolidayOverlap = 0;
        foreach (array_keys($holidayInstructionalDates) as $dateString) {
            if (in_array(Carbon::parse($dateString)->format('l'), $weekendDays, true)) {
                $weekendHolidayOverlap++;
            }
        }
        $calendarStats = [
            'total_days' => $totalDays,
            'weekend_days' => $weekendCount,
            'holiday_days' => $instructionalOffDays,
            'instructional_days' => max(0, $totalDays - $weekendCount - $instructionalOffDays + $weekendHolidayOverlap),
        ];

        return view('school.holidays.index', compact('holidays', 'year', 'calendarStats', 'activeSession'));
    }

    /**
     * Store a custom holiday/festival (Administrator authority only).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Administrator credentials required to add academic holidays.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:holiday_date',
            'type' => 'required|string|in:national,festival,vacation,institutional,restricted',
            'badge_icon' => 'nullable|string|max:16',
            'description' => 'nullable|string|max:500',
            'is_instructional_off' => 'nullable|boolean',
        ]);

        $validated['is_instructional_off'] = $request->boolean('is_instructional_off');
        $validated['academic_year'] = $this->resolveAcademicYear($validated['holiday_date']);
        $this->ensureNoInstructionalOverlap($validated, null);

        AcademicHoliday::create($validated);

        return redirect()->route('school.holidays.index', ['year' => date('Y', strtotime($validated['holiday_date']))])
            ->with('success', "Academic holiday / festival '{$validated['name']}' added to the institutional calendar.");
    }

    /**
     * Update an existing holiday/festival.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Administrator credentials required to update academic holidays.');
        }

        $holiday = AcademicHoliday::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:holiday_date',
            'type' => 'required|string|in:national,festival,vacation,institutional,restricted',
            'badge_icon' => 'nullable|string|max:16',
            'description' => 'nullable|string|max:500',
            'is_instructional_off' => 'nullable|boolean',
        ]);

        $validated['is_instructional_off'] = $request->boolean('is_instructional_off');
        $validated['academic_year'] = $this->resolveAcademicYear($validated['holiday_date']);
        $this->ensureNoInstructionalOverlap($validated, $holiday->id);

        $holiday->update($validated);

        return redirect()->route('school.holidays.index', ['year' => date('Y', strtotime($validated['holiday_date']))])
            ->with('success', "Academic holiday '{$holiday->name}' updated successfully.");
    }

    /**
     * Remove an academic holiday.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Administrator credentials required to delete academic holidays.');
        }

        $holiday = AcademicHoliday::findOrFail($id);
        $name = $holiday->name;
        $holiday->delete();

        return redirect()->route('school.holidays.index')
            ->with('success', "Holiday '{$name}' has been removed from the academic calendar.");
    }

    private function resolveAcademicYear(string $date): string
    {
        $configured = trim((string) AcademySetting::get('academic_session', ''));
        if (preg_match('/^\d{4}-\d{4}$/', $configured)) {
            $start = (int) substr($configured, 0, 4);
            $end = (int) substr($configured, 5, 4);
            $year = (int) Carbon::parse($date)->format('Y');
            if ($year === $start || $year === $end) {
                return $configured;
            }
        }
        $year = (int) Carbon::parse($date)->format('Y');

        return $year.'-'.($year + 1);
    }

    private function ensureNoInstructionalOverlap(array $data, ?int $ignoreId): void
    {
        if (empty($data['is_instructional_off'])) {
            return;
        }
        $start = $data['holiday_date'];
        $end = $data['end_date'] ?? $start;
        $overlap = AcademicHoliday::query()
            ->where('is_instructional_off', true)
            ->whereDate('holiday_date', '<=', $end)
            ->where(function ($query) use ($start, $end) {
                $query->whereDate('end_date', '>=', $start)
                    ->orWhere(function ($singleDay) use ($start, $end) {
                        $singleDay->whereNull('end_date')->whereBetween('holiday_date', [$start, $end]);
                    });
            })
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'holiday_date' => 'This date range overlaps another instructional closure. Edit the existing closure or choose a non-overlapping range.',
            ]);
        }
    }
}
