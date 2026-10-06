<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Requests\UpdateDoctorProfileRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $doctors = Doctor::with('user:id,name,email')
            ->get();

        return $this->successResponse(
            DoctorResource::collection($doctors),
            'Doctors retrieved successfully.'
        );
    }

    public function appointments(Request $request)
    {
        $doctor = $request->user()->doctor;

        $query = $doctor->appointments()
            ->with([
                'patient.user:id,name,email',
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
    | Filter by Patient
    |--------------------------------------------------------------------------
    */

        if ($request->filled('patient_id')) {
            $query->where(
                'patient_id',
                $request->patient_id
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
            ->latest('appointment_date')
            ->latest('appointment_time')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            \App\Http\Resources\AppointmentResource::collection(
                $appointments
            ),
            'Appointments retrieved successfully.'
        );
    }

    public function updateAppointmentStatus(
        UpdateAppointmentStatusRequest $request,
        $id
    ) {
        $doctor = $request->user()->doctor;

        $validated = $request->validated();

        $appointment = $doctor->appointments()->find($id);

        if (!$appointment) {
            return $this->errorResponse(
                'Appointment not found.',
                404
            );
        }

        $appointment->update([
            'status' => $validated['status'],
        ]);

        $appointment->load([
            'patient.user:id,name,email',
            'doctor.user:id,name,email',
        ]);

        $patientUser = $appointment->patient->user;

        if ($validated['status'] === 'confirmed') {
            Notification::create([
                'user_id' => $patientUser->id,
                'title' => 'Appointment Confirmed',
                'message' => 'Your appointment has been confirmed by the doctor.',
            ]);
        }

        if ($validated['status'] === 'cancelled') {
            Notification::create([
                'user_id' => $patientUser->id,
                'title' => 'Appointment Cancelled',
                'message' => 'Your appointment has been cancelled.',
            ]);
        }

        if ($validated['status'] === 'completed') {
            Notification::create([
                'user_id' => $patientUser->id,
                'title' => 'Appointment Completed',
                'message' => 'Your appointment has been completed.',
            ]);
        }

        return $this->successResponse(
            new AppointmentResource($appointment),
            'Appointment status updated successfully.'
        );
    }

    public function profile(Request $request)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return $this->errorResponse(
                'Doctor profile not found.',
                404
            );
        }

        $doctor->load([
            'user:id,name,email',
            'workingHours',
        ]);

        return $this->successResponse(
            new DoctorResource($doctor),
            'Doctor profile retrieved successfully.'
        );
    }

    public function updateProfile(UpdateDoctorProfileRequest $request)
    {
        $user = $request->user();

        $doctor = $user->doctor;

        if (!$doctor) {
            return $this->errorResponse(
                'Doctor profile not found.',
                404
            );
        }

        $validated = $request->validated();

        if (isset($validated['name'])) {
            $user->update([
                'name' => $validated['name'],
            ]);
        }

        $doctorData = collect($validated)
            ->except('name')
            ->toArray();

        if (!empty($doctorData)) {
            $doctor->update($doctorData);
        }

        $doctor->load([
            'user:id,name,email',
            'workingHours',
        ]);

        return $this->successResponse(
            new DoctorResource($doctor),
            'Doctor profile updated successfully.'
        );
    }

    public function patients(Request $request)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return $this->errorResponse(
                'Doctor profile not found.',
                404
            );
        }

        $patients = \App\Models\Patient::query()
            ->whereHas('appointments', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->with([
                'user:id,name,email',
            ])
            ->withCount([
                'appointments as appointments_count' => function ($query) use ($doctor) {
                    $query->where('doctor_id', $doctor->id);
                },
            ])
            ->get();

        return $this->successResponse(
            $patients,
            'Patients retrieved successfully.'
        );
    }
}
