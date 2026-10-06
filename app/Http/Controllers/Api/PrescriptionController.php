<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Requests\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    use ApiResponse;

    public function update(UpdatePrescriptionRequest $request, $id)
    {
        $doctor = $request->user()->doctor;

        $prescription = $doctor->prescriptions()->find($id);

        if (!$prescription) {
            return $this->errorResponse(
                'Prescription not found.',
                404
            );
        }

        $validated = $request->validated();

        DB::transaction(function () use (
            $prescription,
            $validated
        ) {
            $prescription->update([
                'notes' => $validated['notes'] ?? null,
            ]);

            $prescription->items()->delete();

            foreach ($validated['items'] as $item) {
                $prescription->items()->create([
                    'medicine_name' => $item['medicine_name'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                ]);
            }
        });

        $prescription->load([
            'patient.user:id,name',
            'doctor.user:id,name',
            'appointment:id,appointment_date,appointment_time',
            'items',
        ]);

        return $this->successResponse(
            new PrescriptionResource($prescription),
            'Prescription updated successfully.'
        );
    }

    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctor;

        $prescription = $doctor->prescriptions()->find($id);

        if (!$prescription) {
            return $this->errorResponse(
                'Prescription not found.',
                404
            );
        }

        $prescription->delete();

        return $this->successResponse(
            null,
            'Prescription deleted successfully.'
        );
    }

    public function patientPrescriptions(Request $request)
    {
        $patient = $request->user()->patient;

        $query = $patient->prescriptions()
            ->with([
                'patient.user:id,name',
                'doctor.user:id,name',
                'appointment:id,appointment_date,appointment_time,type',
                'items',
            ]);

        // Filter by doctor
        if ($request->filled('doctor_id')) {
            $query->where(
                'doctor_id',
                $request->doctor_id
            );
        }

        // Filter by appointment
        if ($request->filled('appointment_id')) {
            $query->where(
                'appointment_id',
                $request->appointment_id
            );
        }

        // Search by medicine name
        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('items', function ($q) use ($search) {
                $q->where(
                    'medicine_name',
                    'like',
                    "%{$search}%"
                );
            });
        }

        // Pagination
        $perPage = min(
            (int) $request->get('per_page', 10),
            50
        );

        $prescriptions = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            \App\Http\Resources\PrescriptionResource::collection(
                $prescriptions
            ),
            'Prescriptions retrieved successfully.'
        );
    }
    public function store(StorePrescriptionRequest $request)
    {
        $doctor = $request->user()->doctor;

        $validated = $request->validated();

        $prescription = DB::transaction(function () use (
            $doctor,
            $validated
        ) {
            $prescription = Prescription::create([
                'patient_id' => $validated['patient_id'],
                'doctor_id' => $doctor->id,
                'appointment_id' => $validated['appointment_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $prescription->items()->create([
                    'medicine_name' => $item['medicine_name'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                ]);
            }

            return $prescription;
        });

        $prescription->load([
            'patient.user:id,name',
            'doctor.user:id,name',
            'appointment:id,appointment_date,appointment_time',
            'items',
        ]);

        return $this->successResponse(
            new PrescriptionResource($prescription),
            'Prescription created successfully.',
            201
        );
    }

    public function doctorPrescriptions(Request $request)
    {
        $doctor = $request->user()->doctor;

        $query = $doctor->prescriptions()
            ->with([
                'patient.user:id,name',
                'doctor.user:id,name',
                'appointment:id,appointment_date,appointment_time,type',
                'items',
            ]);

        // Filter by patient
        if ($request->filled('patient_id')) {
            $query->where(
                'patient_id',
                $request->patient_id
            );
        }

        // Filter by appointment
        if ($request->filled('appointment_id')) {
            $query->where(
                'appointment_id',
                $request->appointment_id
            );
        }

        // Search by medicine name
        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('items', function ($q) use ($search) {
                $q->where(
                    'medicine_name',
                    'like',
                    "%{$search}%"
                );
            });
        }

        // Pagination
        $perPage = min(
            (int) $request->get('per_page', 10),
            50
        );

        $prescriptions = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            \App\Http\Resources\PrescriptionResource::collection(
                $prescriptions
            ),
            'Prescriptions retrieved successfully.'
        );
    }

    public function showForPatient(Request $request, $id)
    {
        $patient = $request->user()->patient;

        $prescription = $patient->prescriptions()
            ->with([
                'patient.user:id,name',
                'doctor.user:id,name',
                'appointment:id,appointment_date,appointment_time',
                'items',
            ])
            ->find($id);

        if (!$prescription) {
            return $this->errorResponse(
                'Prescription not found.',
                404
            );
        }

        return $this->successResponse(
            new PrescriptionResource($prescription),
            'Prescription retrieved successfully.'
        );
    }
}
