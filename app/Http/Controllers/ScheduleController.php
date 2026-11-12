<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /** Неделя, содержащая переданную дату (или текущую), начиная с понедельника. */
    private function weekStart(?string $date): CarbonImmutable
    {
        $d = $date ? CarbonImmutable::parse($date) : CarbonImmutable::today();

        return $d->startOfWeek(CarbonImmutable::MONDAY);
    }

    public function bootstrap(): JsonResponse
    {
        return response()->json([
            'weekStart' => $this->weekStart(null)->toDateString(),
            'employees' => Employee::orderBy('id')->get(),
            'templates' => ShiftTemplate::orderBy('sort')->get(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $start = $this->weekStart($request->query('week'));
        $shifts = Shift::whereBetween('date', [$start->toDateString(), $start->addDays(6)->toDateString()])
            ->orderBy('id')
            ->get();

        return response()->json([
            'weekStart' => $start->toDateString(),
            'shifts' => $shifts,
        ]);
    }

    /**
     * Полное сохранение недели: клиент присылает массив смен
     * [{employee_id, date, start_time, end_time}].
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'weekStart' => ['required', 'date'],
            'shifts' => ['present', 'array'],
            'shifts.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shifts.*.date' => ['required', 'date'],
            'shifts.*.start_time' => ['required', 'date_format:H:i'],
            'shifts.*.end_time' => ['required', 'date_format:H:i'],
        ]);

        $start = CarbonImmutable::parse($data['weekStart']);
        $end = $start->addDays(6)->toDateString();

        Shift::whereBetween('date', [$start->toDateString(), $end])->delete();

        foreach ($data['shifts'] as $shift) {
            Shift::create([
                'employee_id' => $shift['employee_id'],
                'date' => $shift['date'],
                'start_time' => $shift['start_time'],
                'end_time' => $shift['end_time'],
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
