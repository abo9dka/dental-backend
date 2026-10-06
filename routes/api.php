<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ReceptionistController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DoctorWorkingHourController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/patient/password', [AuthController::class, 'changePassword']);
    Route::get(
        '/doctors/{doctorId}/available-slots',
        [AppointmentController::class, 'availableSlots']
    );
    Route::get(
        '/notifications',
        [NotificationController::class, 'index']
    );

    Route::get(
        '/notifications/unread',
        [NotificationController::class, 'unread']
    );

    Route::put(
        '/notifications/{id}/read',
        [NotificationController::class, 'markAsRead']
    );

    Route::put(
        '/notifications/read-all',
        [NotificationController::class, 'markAllAsRead']
    );
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);


    /*
    |--------------------------------------------------------------------------
    | Doctors
    |--------------------------------------------------------------------------
    */

    Route::get('/doctors', [DoctorController::class, 'index']);


    /*
    |--------------------------------------------------------------------------
    | Patient Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:patient')->group(function () {

        // Profile
        Route::get(
            '/patient/profile',
            [PatientController::class, 'profile']
        );

        Route::put(
            '/patient/profile',
            [PatientController::class, 'updateProfile']
        );


        // Appointments
        Route::get(
            '/patient/appointments',
            [AppointmentController::class, 'index']
        );

        Route::post(
            '/patient/appointments',
            [AppointmentController::class, 'store']
        );

        Route::get(
            '/patient/appointments/{id}',
            [AppointmentController::class, 'show']
        );

        Route::delete(
            '/patient/appointments/{id}',
            [AppointmentController::class, 'destroy']
        );


        // Medical Records
        Route::get(
            '/patient/medical-records',
            [MedicalRecordController::class, 'patientRecords']
        );


        // Prescriptions
        Route::get(
            '/patient/prescriptions',
            [PrescriptionController::class, 'patientPrescriptions']
        );

        Route::get(
            '/patient/prescriptions/{id}',
            [PrescriptionController::class, 'showForPatient']
        );
    });


    /*
    |--------------------------------------------------------------------------
    | Doctor Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:doctor')->group(function () {

        Route::get(
            '/doctor/profile',
            [DoctorController::class, 'profile']
        );

        Route::put(
            '/doctor/profile',
            [DoctorController::class, 'updateProfile']
        );

        Route::get(
            '/doctor/working-hours',
            [DoctorWorkingHourController::class, 'index']
        );

        Route::post(
            '/doctor/working-hours',
            [DoctorWorkingHourController::class, 'store']
        );

        Route::put(
            '/doctor/working-hours/{id}',
            [DoctorWorkingHourController::class, 'update']
        );

        Route::delete(
            '/doctor/working-hours/{id}',
            [DoctorWorkingHourController::class, 'destroy']
        );
        Route::put(
            '/doctor/prescriptions/{id}',
            [PrescriptionController::class, 'update']
        );

        Route::delete(
            '/doctor/prescriptions/{id}',
            [PrescriptionController::class, 'destroy']
        );

        Route::put(
            '/doctor/medical-records/{id}',
            [MedicalRecordController::class, 'update']
        );

        Route::delete(
            '/doctor/medical-records/{id}',
            [MedicalRecordController::class, 'destroy']
        );
        // Appointments
        Route::get(
            '/doctor/appointments',
            [DoctorController::class, 'appointments']
        );

        Route::put(
            '/doctor/appointments/{id}/status',
            [DoctorController::class, 'updateAppointmentStatus']
        );


        // Medical Records
        Route::get(
            '/doctor/medical-records',
            [MedicalRecordController::class, 'doctorRecords']
        );

        Route::post(
            '/doctor/medical-records',
            [MedicalRecordController::class, 'store']
        );


        // Prescriptions
        Route::get(
            '/doctor/prescriptions',
            [PrescriptionController::class, 'doctorPrescriptions']
        );

        Route::post(
            '/doctor/prescriptions',
            [PrescriptionController::class, 'store']
        );
        Route::get(
            '/doctor/dashboard',
            [DashboardController::class, 'doctorDashboard']
        );
        Route::get(
            '/doctor/patients',
            [DoctorController::class, 'patients']
        );
    });

    Route::middleware('role:receptionist')->group(function () {

        Route::get(
            '/receptionist/dashboard',
            [DashboardController::class, 'index']
        );

        Route::get(
            '/receptionist/patients',
            [ReceptionistController::class, 'patients']
        );

        Route::get(
            '/receptionist/patients/{id}',
            [ReceptionistController::class, 'showPatient']
        );

        Route::get(
            '/receptionist/appointments',
            [ReceptionistController::class, 'appointments']
        );

        Route::put(
            '/receptionist/appointments/{id}/status',
            [ReceptionistController::class, 'updateAppointmentStatus']
        );

        Route::get(
            '/receptionist/patients',
            [ReceptionistController::class, 'patients']
        );

        Route::get(
            '/receptionist/appointments',
            [ReceptionistController::class, 'appointments']
        );

        Route::put(
            '/receptionist/appointments/{id}/status',
            [ReceptionistController::class, 'updateAppointmentStatus']
        );
        Route::get(
            '/receptionist/dashboard',
            [DashboardController::class, 'index']
        );
    });
});
