<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPatient() === true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'date_of_birth' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'gender' => [
                'sometimes',
                'nullable',
                'in:male,female',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'emergency_contact' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
