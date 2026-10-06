<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,

                    'user' => [
                        'id' => $this->patient->user?->id,
                        'name' => $this->patient->user?->name,
                        'email' => $this->patient->user?->email,
                    ],

                    'phone' => $this->patient->phone,
                ];
            }),

            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,

                    'user' => [
                        'id' => $this->doctor->user?->id,
                        'name' => $this->doctor->user?->name,
                    ],

                    'specialization' => $this->doctor->specialization,
                ];
            }),

            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,

            'type' => $this->type,
            'duration' => $this->duration,

            'status' => $this->status,

            'reason' => $this->reason,
            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
