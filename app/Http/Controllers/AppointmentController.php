<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('appointments.index', [
            'appointments' => auth()->user()->appointments()->with('service')->latest('scheduled_at')->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('appointments.create', [
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
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
            'appointment' => $appointment->load('service'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Appointment $appointment)
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        return view('appointments.edit', [
            'appointment' => $appointment,
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === auth()->id(), 403);

        $validated = $request->validate((new StoreAppointmentRequest())->rules());
        $appointment->update($validated);

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
}
