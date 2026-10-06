<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() === true
            || $this->user()?->isReceptionist() === true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'in:pending,confirmed,cancelled,completed',
            ],
        ];
    }
}
