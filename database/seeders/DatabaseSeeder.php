<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\League;
use App\Models\LeagueSetting;
use App\Models\Team;
use App\Models\Player;
use App\Models\GameMatch;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        
        // Create league manager
        $leagueManager = User::create([
            'name' => 'League Manager',
            'email' => 'league@example.com',
            'password' => Hash::make('password'),
            'role' => 'league_manager',
        ]);
        
        // Create team manager
        $teamManager = User::create([
            'name' => 'Team Manager',
            'email' => 'team@example.com',
            'password' => Hash::make('password'),
            'role' => 'team_manager',
        ]);
        
        // Create regular user
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
        
        // Create a test league
        $league = League::create([
            'user_id' => $leagueManager->id,
            'name' => 'Premier Football League',
            'slug' => 'premier-football-league',
            'country' => 'United Kingdom',
            'type' => 'sports',
            'sport_type' => 'football',
            'season_name' => '2025-2026',
            'season_start_date' => '2025-08-15',
            'season_end_date' => '2026-05-20',
            'website_url' => 'https://example.com/premier-league',
            'description' => 'The top tier football league in the UK',
            'is_public' => true,
            'is_active' => true,
        ]);
        
        // Create league settings
        LeagueSetting::create([
            'league_id' => $league->id,
            'points_for_win' => 3,
            'points_for_draw' => 1,
            'points_for_loss' => 0,
            'home_team_change_date' => true,
            'home_team_change_venue' => true,
        ]);
        
        // Create teams
        $team1 = Team::create([
            'league_id' => $league->id,
            'name' => 'Manchester United',
            'slug' => 'manchester-united',
            'home_venue' => 'Old Trafford',
            'team_color' => '#DA291C',
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'points' => 0,
        ]);
        
        $team2 = Team::create([
            'league_id' => $league->id,
            'name' => 'Liverpool FC',
            'slug' => 'liverpool-fc',
            'home_venue' => 'Anfield',
            'team_color' => '#C8102E',
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'points' => 0,
        ]);
        
        $team3 = Team::create([
            'league_id' => $league->id,
            'name' => 'Arsenal',
            'slug' => 'arsenal',
            'home_venue' => 'Emirates Stadium',
            'team_color' => '#EF0107',
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'points' => 0,
        ]);
        
        $team4 = Team::create([
            'league_id' => $league->id,
            'name' => 'Chelsea',
            'slug' => 'chelsea',
            'home_venue' => 'Stamford Bridge',
            'team_color' => '#034694',
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'points' => 0,
        ]);
        
        // Create some players for each team
        $this->createPlayersForTeam($team1);
        $this->createPlayersForTeam($team2);
        $this->createPlayersForTeam($team3);
        $this->createPlayersForTeam($team4);
        
        // Create some matches
        $this->createMatches($league, [$team1, $team2, $team3, $team4]);
    }
    
    /**
     * Create players for a team
     */
    private function createPlayersForTeam($team)
    {
        $positions = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward'];
        
        for ($i = 1; $i <= 11; $i++) {
            $position = $positions[rand(0, 3)];
            
            Player::create([
                'team_id' => $team->id,
                'first_name' => 'Player',
                'last_name' => $i . ' ' . $team->name,
                'jersey_number' => $i,
                'position' => $position,
                'is_active' => true,
                'is_captain' => ($i === 1), // First player is the captain
            ]);
        }
    }
    
    /**
     * Create matches between teams
     */
    private function createMatches($league, $teams)
    {
        $startDate = strtotime($league->season_start_date);
        $endDate = strtotime($league->season_end_date);
        
        // Create 6 matches (not a full schedule, just examples)
        $matchDates = [];
        for ($i = 0; $i < 6; $i++) {
            $randomTimestamp = rand($startDate, $endDate);
            $matchDates[] = date('Y-m-d H:i:s', $randomTimestamp);
        }
        
        sort($matchDates); // Sort dates chronologically
        
        // Match 1: Team 1 vs Team 2
        GameMatch::create([
            'league_id' => $league->id,
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'match_date' => $matchDates[0],
            'venue' => $teams[0]->home_venue,
            'status' => 'scheduled',
        ]);
        
        // Match 2: Team 3 vs Team 4
        GameMatch::create([
            'league_id' => $league->id,
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'match_date' => $matchDates[1],
            'venue' => $teams[2]->home_venue,
            'status' => 'scheduled',
        ]);
        
        // Match 3: Team 1 vs Team 3 (completed)
        GameMatch::create([
            'league_id' => $league->id,
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[2]->id,
            'match_date' => $matchDates[2],
            'venue' => $teams[0]->home_venue,
            'status' => 'completed',
            'home_team_score' => 2,
            'away_team_score' => 1,
        ]);
        
        // Match 4: Team 2 vs Team 4 (completed)
        GameMatch::create([
            'league_id' => $league->id,
            'home_team_id' => $teams[1]->id,
            'away_team_id' => $teams[3]->id,
            'match_date' => $matchDates[3],
            'venue' => $teams[1]->home_venue,
            'status' => 'completed',
            'home_team_score' => 1,
            'away_team_score' => 1,
        ]);
        
        // Match 5: Team 4 vs Team 1
        GameMatch::create([
            'league_id' => $league->id,
            'home_team_id' => $teams[3]->id,
            'away_team_id' => $teams[0]->id,
            'match_date' => $matchDates[4],
            'venue' => $teams[3]->home_venue,
            'status' => 'scheduled',
        ]);
        
        // Match 6: Team 2 vs Team 3
        GameMatch::create([
            'league_id' => $league->id,
            'home_team_id' => $teams[1]->id,
            'away_team_id' => $teams[2]->id,
            'match_date' => $matchDates[5],
            'venue' => $teams[1]->home_venue,
            'status' => 'scheduled',
        ]);
        
        // Update team standings based on completed matches
        $teams[0]->updateRecord(); // Team 1
        $teams[1]->updateRecord(); // Team 2
        $teams[2]->updateRecord(); // Team 3
        $teams[3]->updateRecord(); // Team 4
    }
}
