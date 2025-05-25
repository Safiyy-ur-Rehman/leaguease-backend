<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = League::query();
        
        // Filter by sport type if provided
        if ($request->has('sport_type')) {
            $query->where('sport_type', $request->sport_type);
        }
        
        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        
        // Only show leagues that the user has access to if not admin
        if (!$request->user()->hasRole('admin')) {
            $query->where('user_id', $request->user()->id)
                  ->orWhere('is_public', true);
        }
        
        $leagues = $query->with('leagueSetting')->paginate($request->per_page ?? 10);
        
        return response()->json($leagues);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'sport_type' => 'required|string|max:255',
            'season_name' => 'required|string|max:255',
            'season_start_date' => 'required|date',
            'season_end_date' => 'required|date|after:season_start_date',
            'website_url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'logo_path' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a slug from the name
        $slug = Str::slug($request->name);
        $baseSlug = $slug;
        $count = 2;
        
        // Ensure the slug is unique
        while (League::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        // Create league
        $league = League::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'slug' => $slug,
            'country' => $request->country,
            'type' => $request->type,
            'sport_type' => $request->sport_type,
            'season_name' => $request->season_name,
            'season_start_date' => $request->season_start_date,
            'season_end_date' => $request->season_end_date,
            'website_url' => $request->website_url,
            'description' => $request->description,
            'is_public' => $request->is_public ?? true,
            'logo_path' => $request->logo_path,
            'settings' => $request->settings,
        ]);

        // Create default league settings
        LeagueSetting::create([
            'league_id' => $league->id,
            // Default settings can be customized per league type
            'points_for_win' => 3,
            'points_for_draw' => 1,
            'points_for_loss' => 0,
        ]);

        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'created',
            'league',
            $league->id,
            "Created league '{$league->name}'",
            null,
            $league->toArray()
        );

        return response()->json($league, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $league = League::with(['leagueSetting', 'teams'])->findOrFail($id);
        
        // Check if user has access to the league
        if (!$league->is_public && $league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($league);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $league = League::findOrFail($id);
        
        // Check if user has permission to update the league
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'country' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'sport_type' => 'sometimes|required|string|max:255',
            'season_name' => 'sometimes|required|string|max:255',
            'season_start_date' => 'sometimes|required|date',
            'season_end_date' => 'sometimes|required|date|after:season_start_date',
            'website_url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'logo_path' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Store old values for logging
        $oldValues = $league->toArray();
        
        // Update league
        $league->fill($request->all());
        $league->save();
        
        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'updated',
            'league',
            $league->id,
            "Updated league '{$league->name}'",
            $oldValues,
            $league->toArray()
        );

        return response()->json($league);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $league = League::findOrFail($id);
        
        // Check if user has permission to delete the league
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Store league info for logging
        $leagueInfo = $league->toArray();
        
        // Delete league (cascade will handle related records)
        $league->delete();
        
        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'deleted',
            'league',
            $id,
            "Deleted league '{$leagueInfo['name']}'",
            $leagueInfo,
            null
        );

        return response()->json(null, 204);
    }
    
    /**
     * Get league standings
     */
    public function standings(string $id)
    {
        $league = League::findOrFail($id);
        
        // Check if user has access to the league
        if (!$league->is_public && $league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $standings = $league->teams()
            ->where('is_active', true)
            ->orderBy('points', 'desc')
            ->orderBy('wins', 'desc')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'wins', 'losses', 'draws', 'points']);
            
        return response()->json($standings);
    }
} 