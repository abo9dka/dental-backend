<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() === true;
    }

    public function rules(): array
    {
        return [
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
}
