<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use ApiResponse;

    public function profile(Request $request)
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return $this->errorResponse(
                'Patient profile not found.',
                404
            );
        }

        $patient->load([
            'user:id,name,email',
        ]);

        return $this->successResponse(
            new PatientResource($patient),
            'Patient profile retrieved successfully.'
        );
    }

    public function updateProfile(UpdatePatientRequest $request)
    {
        $user = $request->user();

        $patient = $user->patient;

        if (!$patient) {
            return $this->errorResponse(
                'Patient profile not found.',
                404
            );
        }

        $validated = $request->validated();

        if (isset($validated['name'])) {
            $user->update([
                'name' => $validated['name'],
            ]);
        }

        $patientData = collect($validated)
            ->except('name')
            ->toArray();

        if (!empty($patientData)) {
            $patient->update($patientData);
        }

        $patient->load([
            'user:id,name,email',
        ]);

        return $this->successResponse(
            new PatientResource($patient),
            'Profile updated successfully.'
        );
    }
}
