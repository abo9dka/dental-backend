<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $patientsCount = Patient::count();

        $appointmentsCount = Appointment::count();

        $pendingAppointments = Appointment::where(
            'status',
            'pending'
        )->count();

        $confirmedAppointments = Appointment::where(
            'status',
            'confirmed'
        )->count();

        $completedAppointments = Appointment::where(
            'status',
            'completed'
        )->count();

        return $this->successResponse([
            'patients_count' => $patientsCount,
            'appointments_count' => $appointmentsCount,
            'pending_appointments' => $pendingAppointments,
            'confirmed_appointments' => $confirmedAppointments,
            'completed_appointments' => $completedAppointments,
        ], 'Receptionist dashboard retrieved successfully.');
    }

    public function doctorDashboard(Request $request)
    {
        $doctor = $request->user()->doctor;

        $appointments = $doctor->appointments();

        $appointmentsCount = (clone $appointments)->count();

        $todayAppointments = (clone $appointments)
            ->whereDate('appointment_date', today())
            ->count();

        $pendingAppointments = (clone $appointments)
            ->where('status', 'pending')
            ->count();

        $confirmedAppointments = (clone $appointments)
            ->where('status', 'confirmed')
            ->count();

        $completedAppointments = (clone $appointments)
            ->where('status', 'completed')
            ->count();

        return $this->successResponse([
            'appointments_count' => $appointmentsCount,
            'today_appointments' => $todayAppointments,
            'pending_appointments' => $pendingAppointments,
            'confirmed_appointments' => $confirmedAppointments,
            'completed_appointments' => $completedAppointments,
        ], 'Doctor dashboard retrieved successfully.');
    }
}
