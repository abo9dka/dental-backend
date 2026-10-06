<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialization',
        'phone',
        'bio',
        'clinic_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
    public function workingHours()
    {
        return $this->hasMany(DoctorWorkingHour::class);
    }
}
