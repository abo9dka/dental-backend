<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->foreignId('prescription_id')
                ->after('id')
                ->constrained('prescriptions')
                ->cascadeOnDelete();

            $table->string('medicine_name')
                ->after('prescription_id');

            $table->string('dosage')
                ->nullable()
                ->after('medicine_name');

            $table->string('frequency')
                ->nullable()
                ->after('dosage');

            $table->string('duration')
                ->nullable()
                ->after('frequency');

            $table->text('instructions')
                ->nullable()
                ->after('duration');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropForeign(['prescription_id']);

            $table->dropColumn([
                'prescription_id',
                'medicine_name',
                'dosage',
                'frequency',
                'duration',
                'instructions',
            ]);
        });
    }
};
