<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_working_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->cascadeOnDelete();

            $table->enum('day_of_week', [
                'saturday',
                'sunday',
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
            ]);

            $table->time('start_time');
            $table->time('end_time');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'doctor_id',
                'day_of_week',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_working_hours');
    }
};
