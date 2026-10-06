<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() === true;
    }

    public function rules(): array
    {
        return [
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.medicine_name' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.dosage' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.duration' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.instructions' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
