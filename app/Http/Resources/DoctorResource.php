<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],

            'specialization' => $this->specialization,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'clinic_address' => $this->clinic_address,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
