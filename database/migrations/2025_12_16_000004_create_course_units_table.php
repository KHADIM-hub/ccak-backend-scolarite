<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_units', function (Blueprint $table) {
            $table->uuid(column: 'id')->primary();
            $table->uuid('academic_program_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('semester_number');
            $table->unsignedSmallInteger('credits');
            $table->enum('type', ['OBLIGATOIRE', 'OPTIONNEL']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academic_program_id')->references('id')->on('academic_programs')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_units');
    }
};
