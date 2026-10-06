<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() === true;
    }

    public function rules(): array
    {
        $doctor = $this->user()?->doctor;

        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where(function ($query) use ($doctor) {
                    $query->whereExists(function ($subQuery) use ($doctor) {
                        $subQuery
                            ->selectRaw('1')
                            ->from('appointments')
                            ->whereColumn(
                                'appointments.patient_id',
                                'patients.id'
                            )
                            ->where(
                                'appointments.doctor_id',
                                $doctor?->id
                            );
                    });
                }),
            ],

            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where(function ($query) use ($doctor) {
                    $query->where(
                        'doctor_id',
                        $doctor?->id
                    );
                }),
            ],

            'diagnosis' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'treatment' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $patientId = $this->input('patient_id');
            $appointmentId = $this->input('appointment_id');

            if (!$patientId || !$appointmentId) {
                return;
            }

            $appointment = \App\Models\Appointment::find($appointmentId);

            if (
                $appointment &&
                (int) $appointment->patient_id !== (int) $patientId
            ) {
                $validator->errors()->add(
                    'appointment_id',
                    'The selected appointment does not belong to the selected patient.'
                );
            }
        });
    }
}
