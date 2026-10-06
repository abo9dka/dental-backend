<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->user?->name,
                ];
            }),

            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->user?->name,
                    'specialization' => $this->doctor->specialization,
                ];
            }),

            'appointment' => $this->whenLoaded('appointment', function () {
                if (!$this->appointment) {
                    return null;
                }

                return [
                    'id' => $this->appointment->id,
                    'date' => $this->appointment->appointment_date,
                    'time' => $this->appointment->appointment_time,
                ];
            }),

            'notes' => $this->notes,

            'items' => PrescriptionItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
