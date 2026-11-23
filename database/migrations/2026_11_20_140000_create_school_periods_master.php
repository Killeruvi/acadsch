<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('school_periods')) {
            Schema::create('school_periods', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('period_number')->unique();
                $table->string('name', 80);
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_break')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Initial master data mirrors the original institutional routine; administrators can change it later.
        $periods = [
            [1, 'Period 1', '08:30:00', '09:15:00', false, 1],
            [2, 'Period 2', '09:15:00', '10:00:00', false, 2],
            [3, 'Period 3', '10:00:00', '10:45:00', false, 3],
            [4, 'Period 4', '10:45:00', '11:30:00', false, 4],
            [0, 'Recess / Lunch', '11:30:00', '12:15:00', true, 5],
            [5, 'Period 5', '12:15:00', '13:00:00', false, 6],
            [6, 'Period 6', '13:00:00', '13:45:00', false, 7],
            [7, 'Period 7', '13:45:00', '14:30:00', false, 8],
            [8, 'Period 8', '14:30:00', '15:15:00', false, 9],
        ];

        $legacyPeriods = collect();
        if (Schema::hasTable('school_period_timetables')) {
            $legacyPeriods = DB::table('school_period_timetables')
                ->select('period_number', DB::raw('MIN(start_time) AS start_time'), DB::raw('MAX(end_time) AS end_time'))
                ->groupBy('period_number')->get()->keyBy('period_number');
        }

        foreach ($periods as [$number, $name, $start, $end, $break, $order]) {
            $legacy = $legacyPeriods->get($number);
            DB::table('school_periods')->insertOrIgnore([
                'period_number' => $number,
                'name' => $name,
                'start_time' => $legacy?->start_time ?? $start,
                'end_time' => $legacy?->end_time ?? $end,
                'is_break' => $break,
                'is_active' => true,
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('school_period_timetables')) {
            foreach ($legacyPeriods as $legacy) {
                DB::table('school_periods')->insertOrIgnore([
                    'period_number' => $legacy->period_number,
                    'name' => $legacy->period_number == 0 ? 'Break' : 'Period '.$legacy->period_number,
                    'start_time' => $legacy->start_time,
                    'end_time' => $legacy->end_time,
                    'is_break' => $legacy->period_number == 0,
                    'is_active' => true,
                    'sort_order' => $legacy->period_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $master = DB::table('school_periods')->where('period_number', $legacy->period_number)->first();
                DB::table('school_period_timetables')->where('period_number', $legacy->period_number)->update([
                    'start_time' => $master->start_time,
                    'end_time' => $master->end_time,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_periods');
    }
};
