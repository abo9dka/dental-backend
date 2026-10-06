<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorWorkingHour;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DoctorWorkingHourController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $doctor = $request->user()->doctor;

        $workingHours = $doctor->workingHours()
            ->orderByRaw("
                FIELD(
                    day_of_week,
                    'saturday',
                    'sunday',
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday'
                )
            ")
            ->get();

        return $this->successResponse(
            $workingHours,
            'Working hours retrieved successfully.'
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => [
                'required',
                'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $doctor = $request->user()->doctor;

        $existing = $doctor->workingHours()
            ->where(
                'day_of_week',
                $validated['day_of_week']
            )
            ->first();

        if ($existing) {
            return $this->errorResponse(
                'Working hours for this day already exist.',
                409
            );
        }

        $workingHour = $doctor->workingHours()->create([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->successResponse(
            $workingHour,
            'Working hours created successfully.',
            201
        );
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'day_of_week' => [
                'sometimes',
                'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            ],
            'start_time' => [
                'sometimes',
                'date_format:H:i',
            ],
            'end_time' => [
                'sometimes',
                'date_format:H:i',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $doctor = $request->user()->doctor;

        $workingHour = $doctor->workingHours()->find($id);

        if (!$workingHour) {
            return $this->errorResponse(
                'Working hours not found.',
                404
            );
        }

        $dayOfWeek = $validated['day_of_week']
            ?? $workingHour->day_of_week;

        $startTime = $validated['start_time']
            ?? $workingHour->start_time;

        $endTime = $validated['end_time']
            ?? $workingHour->end_time;

        if ($endTime <= $startTime) {
            return $this->errorResponse(
                'End time must be after start time.',
                422
            );
        }

        if ($dayOfWeek !== $workingHour->day_of_week) {
            $existing = $doctor->workingHours()
                ->where('day_of_week', $dayOfWeek)
                ->where('id', '!=', $workingHour->id)
                ->first();

            if ($existing) {
                return $this->errorResponse(
                    'Working hours for this day already exist.',
                    409
                );
            }
        }

        $workingHour->update([
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => $validated['is_active']
                ?? $workingHour->is_active,
        ]);

        return $this->successResponse(
            $workingHour->fresh(),
            'Working hours updated successfully.'
        );
    }

    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctor;

        $workingHour = $doctor->workingHours()->find($id);

        if (!$workingHour) {
            return $this->errorResponse(
                'Working hours not found.',
                404
            );
        }

        $workingHour->delete();

        return $this->successResponse(
            null,
            'Working hours deleted successfully.'
        );
    }
}
