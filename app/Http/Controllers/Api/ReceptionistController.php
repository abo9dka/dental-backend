<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReceptionistController extends Controller
{
    use ApiResponse;

    public function patients(Request $request)
    {
        $query = Patient::with('user:id,name,email');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
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

        $patients = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            $patients,
            'Patients retrieved successfully.'
        );
    }

    public function showPatient(Request $request, $id)
    {
        $patient = Patient::with([
            'user:id,name,email',
            'appointments.doctor.user:id,name',
            'medicalRecords',
            'prescriptions.items',
        ])->find($id);

        if (!$patient) {
            return $this->errorResponse(
                'Patient not found.',
                404
            );
        }

        return $this->successResponse(
            $patient,
            'Patient retrieved successfully.'
        );
    }

    public function appointments(Request $request)
    {
        $query = Appointment::with([
            'patient.user:id,name,email',
            'doctor.user:id,name',
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
            ->latest('appointment_date')
            ->latest('appointment_time')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            $appointments,
            'Appointments retrieved successfully.'
        );
    }

    public function updateAppointmentStatus(
        UpdateAppointmentStatusRequest $request,
        $id
    ) {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->errorResponse(
                'Appointment not found.',
                404
            );
        }

        $appointment->update([
            'status' => $request->validated()['status'],
        ]);

        $appointment->load([
            'patient.user:id,name,email',
            'doctor.user:id,name',
        ]);

        return $this->successResponse(
            $appointment,
            'Appointment status updated successfully.'
        );
    }
}
