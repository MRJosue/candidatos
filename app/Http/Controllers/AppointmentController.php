<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $calendarMonth = $this->resolveCalendarMonth($request->query('month'));
        $calendarStart = $calendarMonth->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
        $calendarEnd = $calendarMonth->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);
        $calendarAppointmentsByDate = $request->user()
            ->appointments()
            ->with(['talent', 'vacancy.company', 'vacancy.position'])
            ->whereBetween('scheduled_at', [$calendarStart, $calendarEnd])
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->scheduled_at->toDateString());

        return view('appointments.index', [
            'appointments' => $request->user()
                ->appointments()
                ->with(['talent', 'vacancy.company', 'vacancy.position'])
                ->latest('scheduled_at')
                ->paginate(20),
            'calendarAppointmentsByDate' => $calendarAppointmentsByDate,
            'calendarMonth' => $calendarMonth,
            'calendarWeeks' => $this->calendarWeeks($calendarStart, $calendarEnd),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('appointments.create', [
            ...$this->formOptions($request),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $data = $request->validated();
        $data['timezone'] = $data['timezone'] ?? config('app.timezone');
        $data['status'] = 'scheduled';

        $appointment = $request->user()->appointments()->create($data);

        return redirect()->route('appointments.show', $appointment)->with('status', 'Cita agendada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        return view('appointments.show', [
            'appointment' => $appointment->load(['talent', 'vacancy.company', 'vacancy.position']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        return view('appointments.edit', [
            'appointment' => $appointment,
            ...$this->formOptions($request),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreAppointmentRequest $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        $appointment->update($request->validated());

        return redirect()->route('appointments.show', $appointment)->with('status', 'Cita actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        $appointment->update(['status' => 'cancelled']);

        return redirect()->route('appointments.index')->with('status', 'Cita cancelada.');
    }

    private function formOptions(Request $request): array
    {
        return [
            'talents' => $request->user()
                ->talents()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'vacancies' => $request->user()
                ->vacancies()
                ->with(['company', 'position'])
                ->orderBy('title')
                ->get(),
        ];
    }

    private function resolveCalendarMonth(?string $month): CarbonImmutable
    {
        if ($month) {
            try {
                return CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
            } catch (\Throwable) {
                //
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    private function calendarWeeks(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $weeks = collect();
        $week = collect();

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $week->push($day);

            if ($week->count() === 7) {
                $weeks->push($week);
                $week = collect();
            }
        }

        return $weeks;
    }
}
