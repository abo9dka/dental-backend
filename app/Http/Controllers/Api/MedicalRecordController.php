<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\UpdateMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Models\MedicalRecord;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    use ApiResponse;

    public function update(UpdateMedicalRecordRequest $request, $id)
    {
        $doctor = $request->user()->doctor;

        $record = $doctor->medicalRecords()->find($id);

        if (!$record) {
            return $this->errorResponse(
                'Medical record not found.',
                404
            );
        }

        $validated = $request->validated();

        $record->update($validated);

        $record->fresh();

        $record->load([
            'patient.user:id,name',
            'doctor.user:id,name',
            'appointment:id,appointment_date,appointment_time',
        ]);

        return $this->successResponse(
            new MedicalRecordResource($record),
            'Medical record updated successfully.'
        );
    }

    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctor;

        $record = $doctor->medicalRecords()->find($id);

        if (!$record) {
            return $this->errorResponse(
                'Medical record not found.',
                404
            );
        }

        $record->delete();

        return $this->successResponse(
            null,
            'Medical record deleted successfully.'
        );
    }

    public function patientRecords(Request $request)
    {
        $patient = $request->user()->patient;

        $query = $patient->medicalRecords()
            ->with([
                'doctor.user:id,name',
                'appointment:id,appointment_date,appointment_time,type',
            ]);

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
    | Filter by Date
    |--------------------------------------------------------------------------
    */

        if ($request->filled('date')) {
            $query->whereDate(
                'created_at',
                $request->date
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Search Diagnosis
    |--------------------------------------------------------------------------
    */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'diagnosis',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'treatment',
                        'like',
                        "%{$search}%"
                    );
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

        $records = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            \App\Http\Resources\MedicalRecordResource::collection(
                $records
            ),
            'Medical records retrieved successfully.'
        );
    }
    public function store(StoreMedicalRecordRequest $request)
    {
        $doctor = $request->user()->doctor;

        $validated = $request->validated();

        $record = MedicalRecord::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $doctor->id,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? null,
            'treatment' => $validated['treatment'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $record->load([
            'patient.user:id,name',
            'doctor.user:id,name',
            'appointment:id,appointment_date,appointment_time',
        ]);

        return $this->successResponse(
            new MedicalRecordResource($record),
            'Medical record created successfully.',
            201
        );
    }

    public function doctorRecords(Request $request)
    {
        $doctor = $request->user()->doctor;

        $records = $doctor->medicalRecords()
            ->with([
                'patient.user:id,name',
                'doctor.user:id,name',
                'appointment:id,appointment_date,appointment_time',
            ])
            ->latest()
            ->get();

        return $this->successResponse(
            MedicalRecordResource::collection($records),
            'Medical records retrieved successfully.'
        );
    }
}
