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
        Schema::create('league_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            
            // Team Admin Permissions
            $table->boolean('block_team_admins')->default(false);
            $table->boolean('allow_stats_entry')->default(true);
            $table->boolean('allow_division_matches')->default(true);
            
            // Home Team Permissions
            $table->boolean('home_team_change_date')->default(false);
            $table->boolean('home_team_change_time')->default(false);
            $table->boolean('home_team_change_status')->default(false);
            $table->boolean('home_team_change_venue')->default(false);
            
            // Away Team Permissions
            $table->boolean('away_team_change_date')->default(false);
            $table->boolean('away_team_change_time')->default(false);
            $table->boolean('away_team_change_status')->default(false);
            $table->boolean('away_team_change_venue')->default(false);
            
            // Approval Settings
            $table->boolean('require_match_approval')->default(false);
            $table->boolean('auto_approve_results')->default(false);
            
            // Match Officials
            $table->boolean('allow_officials_marking')->default(true);
            $table->boolean('require_officials')->default(false);
            
            // Player Settings
            $table->boolean('suspend_on_red_card')->default(true);
            $table->integer('yellow_cards_for_suspension')->default(3);
            
            // Point System
            $table->integer('points_for_win')->default(3);
            $table->integer('points_for_draw')->default(1);
            $table->integer('points_for_loss')->default(0);
            
            // Terminology and Custom Settings
            $table->json('custom_terminology')->nullable();
            $table->json('additional_settings')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('league_settings');
    }
}; 