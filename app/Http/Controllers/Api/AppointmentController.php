<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    /*
    |--------------------------------------------------------------------------
    | Get Appointment Duration
    |--------------------------------------------------------------------------
    */

    private function getDuration(string $type): int
    {
        return match ($type) {
            'consultation' => 15,
            'cleaning' => 60,
            'orthodontics' => 30,
            'whitening' => 45,
            'treatment' => 20,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Fixed Appointment Slots
    |--------------------------------------------------------------------------
    */

    private function getFixedSlots(string $type): array
    {
        $duration = $this->getDuration($type);

        $start = Carbon::createFromTime(13, 0, 0);
        $end = Carbon::createFromTime(16, 0, 0);

        $slots = [];

        while (true) {
            $slotStart = $start->copy();

            $slotEnd = $slotStart->copy()
                ->addMinutes($duration);

            if ($slotEnd > $end) {
                break;
            }

            $slots[] = [
                'time' => $slotStart->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
            ];

            $start->addMinutes($duration);
        }

        return $slots;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Patient Appointments
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $patient = $request->user()->patient;

        $query = $patient->appointments()
            ->with([
                'doctor.user:id,name,email',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Filter by Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Date
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date')) {
            $query->whereDate(
                'appointment_date',
                $request->date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Type
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Doctor
        |--------------------------------------------------------------------------
        */

        if ($request->filled('doctor_id')) {
            $query->where(
                'doctor_id',
                $request->doctor_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(
            (int) $request->get('per_page', 10),
            50
        );

        $appointments = $query
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            AppointmentResource::collection($appointments),
            'Appointments retrieved successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Appointment
    |--------------------------------------------------------------------------
    */

    public function store(StoreAppointmentRequest $request)
    {
        $patient = $request->user()->patient;

        $validated = $request->validated();

        $doctor = Doctor::find(
            $validated['doctor_id']
        );

        if (!$doctor) {
            return $this->errorResponse(
                'Doctor not found.',
                404
            );
        }

        $duration = $this->getDuration(
            $validated['type']
        );

        /*
        |--------------------------------------------------------------------------
        | Check Fixed Slot
        |--------------------------------------------------------------------------
        */

        $fixedSlots = $this->getFixedSlots(
            $validated['type']
        );

        $selectedSlot = collect($fixedSlots)
            ->firstWhere(
                'time',
                substr($validated['appointment_time'], 0, 5)
            );

        if (!$selectedSlot) {
            return $this->errorResponse(
                'The selected appointment time is not a valid slot for this appointment type.',
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Check Date
        |--------------------------------------------------------------------------
        */

        $date = Carbon::parse(
            $validated['appointment_date']
        );

        $dayOfWeek = strtolower(
            $date->format('l')
        );

        /*
        |--------------------------------------------------------------------------
        | Check Doctor Working Hours
        |--------------------------------------------------------------------------
        */

        $workingHour = $doctor->workingHours()
            ->where(
                'day_of_week',
                $dayOfWeek
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$workingHour) {
            return $this->errorResponse(
                'Doctor is not available on this day.',
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Appointment Time
        |--------------------------------------------------------------------------
        */

        $appointmentStart = Carbon::createFromFormat(
            'H:i',
            substr(
                $validated['appointment_time'],
                0,
                5
            )
        );

        $appointmentEnd = $appointmentStart->copy()
            ->addMinutes($duration);

        /*
        |--------------------------------------------------------------------------
        | Working Hours
        |--------------------------------------------------------------------------
        */

        $workingStart = Carbon::createFromFormat(
            'H:i:s',
            $workingHour->start_time
        );

        $workingEnd = Carbon::createFromFormat(
            'H:i:s',
            $workingHour->end_time
        );

        if (
            $appointmentStart < $workingStart ||
            $appointmentEnd > $workingEnd
        ) {
            return $this->errorResponse(
                'The appointment exceeds the doctor working hours.',
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Check Doctor Availability
        |--------------------------------------------------------------------------
        */

        $doctorAppointments = Appointment::where(
            'doctor_id',
            $doctor->id
        )
            ->where(
                'appointment_date',
                $validated['appointment_date']
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'confirmed',
                ]
            )
            ->get();

        foreach ($doctorAppointments as $existingAppointment) {
            $existingStart = Carbon::createFromFormat(
                'H:i:s',
                $existingAppointment->appointment_time
            );

            $existingEnd = $existingStart->copy()
                ->addMinutes(
                    $existingAppointment->duration
                );

            $overlap =
                $appointmentStart < $existingEnd &&
                $appointmentEnd > $existingStart;

            if ($overlap) {
                return $this->errorResponse(
                    'This appointment time overlaps with another appointment.',
                    409
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Check Patient Availability
        |--------------------------------------------------------------------------
        */

        $patientAppointments = $patient->appointments()
            ->where(
                'appointment_date',
                $validated['appointment_date']
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'confirmed',
                ]
            )
            ->get();

        foreach ($patientAppointments as $existingAppointment) {
            $existingStart = Carbon::createFromFormat(
                'H:i:s',
                $existingAppointment->appointment_time
            );

            $existingEnd = $existingStart->copy()
                ->addMinutes(
                    $existingAppointment->duration
                );

            $overlap =
                $appointmentStart < $existingEnd &&
                $appointmentEnd > $existingStart;

            if ($overlap) {
                return $this->errorResponse(
                    'You already have another appointment at this time.',
                    409
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create Appointment
        |--------------------------------------------------------------------------
        */

        $appointment = $patient->appointments()->create([
            'doctor_id' => $validated['doctor_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'type' => $validated['type'],
            'duration' => $duration,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Notification
        |--------------------------------------------------------------------------
        */

        Notification::create([
            'user_id' => $request->user()->id,
            'title' => 'Appointment Request Received',
            'message' => 'Your appointment request has been received and is waiting for confirmation.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Load Relationships
        |--------------------------------------------------------------------------
        */

        $appointment->load([
            'patient.user:id,name,email',
            'doctor.user:id,name,email',
        ]);

        return $this->successResponse(
            new AppointmentResource($appointment),
            'Appointment booked successfully.',
            201
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Show Appointment
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, $id)
    {
        $patient = $request->user()->patient;

        $appointment = $patient->appointments()
            ->with([
                'patient.user:id,name,email',
                'doctor.user:id,name,email',
            ])
            ->find($id);

        if (!$appointment) {
            return $this->errorResponse(
                'Appointment not found.',
                404
            );
        }

        return $this->successResponse(
            new AppointmentResource($appointment),
            'Appointment retrieved successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Appointment
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, $id)
    {
        $patient = $request->user()->patient;

        $appointment = $patient->appointments()
            ->find($id);

        if (!$appointment) {
            return $this->errorResponse(
                'Appointment not found.',
                404
            );
        }

        if (
            in_array(
                $appointment->status,
                [
                    'completed',
                    'cancelled',
                ]
            )
        ) {
            return $this->errorResponse(
                'This appointment cannot be cancelled.',
                422
            );
        }

        $appointment->update([
            'status' => 'cancelled',
        ]);

        return $this->successResponse(
            null,
            'Appointment cancelled successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Available Fixed Slots
    |--------------------------------------------------------------------------
    */

    public function availableSlots(
        Request $request,
        $doctorId
    ) {
        $validated = $request->validate([
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'type' => [
                'required',
                'in:consultation,cleaning,orthodontics,whitening,treatment',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Doctor
        |--------------------------------------------------------------------------
        */

        $doctor = Doctor::find($doctorId);

        if (!$doctor) {
            return $this->errorResponse(
                'Doctor not found.',
                404
            );
        }

        $date = $validated['date'];

        $type = $validated['type'];

        $duration = $this->getDuration($type);

        /*
        |--------------------------------------------------------------------------
        | Day Of Week
        |--------------------------------------------------------------------------
        */

        $dayOfWeek = strtolower(
            Carbon::parse($date)->format('l')
        );

        /*
        |--------------------------------------------------------------------------
        | Doctor Working Hours
        |--------------------------------------------------------------------------
        */

        $workingHour = $doctor->workingHours()
            ->where(
                'day_of_week',
                $dayOfWeek
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$workingHour) {
            return $this->successResponse([
                'doctor_id' => $doctor->id,
                'date' => $date,
                'type' => $type,
                'duration' => $duration,
                'available_slots' => [],
            ], 'Doctor is not available on this day.');
        }

        /*
        |--------------------------------------------------------------------------
        | Get Fixed Slots
        |--------------------------------------------------------------------------
        */

        $fixedSlots = $this->getFixedSlots($type);

        /*
        |--------------------------------------------------------------------------
        | Existing Appointments
        |--------------------------------------------------------------------------
        */

        $appointments = Appointment::where(
            'doctor_id',
            $doctor->id
        )
            ->where(
                'appointment_date',
                $date
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'confirmed',
                ]
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Check Availability
        |--------------------------------------------------------------------------
        */

        $slots = [];

        foreach ($fixedSlots as $slot) {

            $slotStart = Carbon::createFromFormat(
                'H:i',
                $slot['time']
            );

            $slotEnd = Carbon::createFromFormat(
                'H:i',
                $slot['end_time']
            );

            /*
            |--------------------------------------------------------------------------
            | Check Working Hours
            |--------------------------------------------------------------------------
            */

            $workingStart = Carbon::createFromFormat(
                'H:i:s',
                $workingHour->start_time
            );

            $workingEnd = Carbon::createFromFormat(
                'H:i:s',
                $workingHour->end_time
            );

            $available =
                $slotStart >= $workingStart &&
                $slotEnd <= $workingEnd;

            /*
            |--------------------------------------------------------------------------
            | Check Existing Appointments
            |--------------------------------------------------------------------------
            */

            if ($available) {

                foreach ($appointments as $appointment) {

                    $existingStart = Carbon::createFromFormat(
                        'H:i:s',
                        $appointment->appointment_time
                    );

                    $existingEnd = $existingStart->copy()
                        ->addMinutes(
                            $appointment->duration
                        );

                    $overlap =
                        $slotStart < $existingEnd &&
                        $slotEnd > $existingStart;

                    if ($overlap) {
                        $available = false;
                        break;
                    }
                }
            }

            $slots[] = [
                'time' => $slot['time'],
                'end_time' => $slot['end_time'],
                'available' => $available,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return $this->successResponse([
            'doctor_id' => $doctor->id,
            'date' => $date,
            'type' => $type,
            'duration' => $duration,
            'working_hours' => [
                'start' => substr(
                    $workingHour->start_time,
                    0,
                    5
                ),
                'end' => substr(
                    $workingHour->end_time,
                    0,
                    5
                ),
            ],
            'available_slots' => $slots,
        ], 'Available slots retrieved successfully.');
    }
}
