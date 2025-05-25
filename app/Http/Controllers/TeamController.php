<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\League;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Team::query();
        
        // Filter by league if provided
        if ($request->has('league_id')) {
            $query->where('league_id', $request->league_id);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        
        $teams = $query->with('league')->paginate($request->per_page ?? 15);
        
        return response()->json($teams);
    }

    /**
     * Get teams by league ID
     */
    public function getTeamsByLeague(string $leagueId)
    {
        $league = League::findOrFail($leagueId);
        
        // Check if user has access to the league
        if (!$league->is_public && $league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $teams = Team::where('league_id', $leagueId)
                    ->orderBy('name')
                    ->get();
                    
        return response()->json($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'league_id' => 'required|exists:leagues,id',
            'name' => 'required|string|max:255',
            'home_venue' => 'nullable|string|max:255',
            'team_color' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if user has permission to add teams to this league
        $league = League::findOrFail($request->league_id);
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'league_manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Create a slug from the name
        $slug = Str::slug($request->name);
        $baseSlug = $slug;
        $count = 2;
        
        // Ensure the slug is unique
        while (Team::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        // Create team
        $team = Team::create([
            'league_id' => $request->league_id,
            'name' => $request->name,
            'slug' => $slug,
            'home_venue' => $request->home_venue,
            'team_color' => $request->team_color,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'website' => $request->website,
            'description' => $request->description,
            'logo_path' => $request->logo_path,
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'points' => 0,
            'is_active' => true,
        ]);

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'created',
            'team',
            $team->id,
            "Created team '{$team->name}' in league '{$league->name}'",
            null,
            $team->toArray()
        );

        return response()->json($team, 201);
    }
    
    /**
     * Create a team in a specific league
     */
    public function createTeamInLeague(Request $request, string $leagueId)
    {
        // Add league_id to request
        $request->merge(['league_id' => $leagueId]);
        
        // Pass to regular store method
        return $this->store($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $team = Team::with(['league', 'players'])->findOrFail($id);
        
        // Check if user has access to the team's league
        $league = $team->league;
        if (!$league->is_public && $league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $team = Team::findOrFail($id);
        
        // Check if user has permission to update this team
        $league = $team->league;
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'league_manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'home_venue' => 'nullable|string|max:255',
            'team_color' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Store old values for logging
        $oldValues = $team->toArray();
        
        // Update team
        $team->fill($request->all());
        $team->save();
        
        // Log activity
        ActivityLog::log(
            Auth::id(),
            'updated',
            'team',
            $team->id,
            "Updated team '{$team->name}'",
            $oldValues,
            $team->toArray()
        );

        return response()->json($team);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $team = Team::findOrFail($id);
        
        // Check if user has permission to delete this team
        $league = $team->league;
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'league_manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check if team has any matches
        $matchCount = $team->homeMatches()->count() + $team->awayMatches()->count();
        if ($matchCount > 0) {
            return response()->json([
                'message' => 'Cannot delete team with existing matches. Try setting it as inactive instead.'
            ], 422);
        }
        
        // Store team info for logging
        $teamInfo = $team->toArray();
        
        // Delete team (cascade will handle related players)
        $team->delete();
        
        // Log activity
        ActivityLog::log(
            Auth::id(),
            'deleted',
            'team',
            $id,
            "Deleted team '{$teamInfo['name']}' from league '{$league->name}'",
            $teamInfo,
            null
        );

        return response()->json(null, 204);
    }
} 