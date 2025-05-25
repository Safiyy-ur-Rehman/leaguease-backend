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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('jersey_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('position')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('bio')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('nationality')->nullable();
            $table->string('height')->nullable(); // in cm
            $table->string('weight')->nullable(); // in kg
            $table->json('statistics')->nullable(); // Store player statistics as JSON
            $table->boolean('is_active')->default(true);
            $table->boolean('is_captain')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
}; 