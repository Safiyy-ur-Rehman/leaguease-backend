<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::query()->with('user');
        
        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter by action if provided
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }
        
        // Filter by entity type if provided
        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        
        // Filter by entity ID if provided
        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }
        
        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Order by most recent first by default
        $query->orderBy('created_at', $request->order ?? 'desc');
        
        $logs = $query->paginate($request->per_page ?? 15);
        
        return response()->json($logs);
    }
    
    /**
     * Display the specified log.
     */
    public function show(string $id)
    {
        $log = ActivityLog::with('user')->findOrFail($id);
        
        return response()->json($log);
    }
    
    /**
     * Get unique action types for filtering
     */
    public function getActionTypes()
    {
        $actions = ActivityLog::distinct('action')->pluck('action');
        
        return response()->json($actions);
    }
    
    /**
     * Get unique entity types for filtering
     */
    public function getEntityTypes()
    {
        $entityTypes = ActivityLog::distinct('entity_type')->pluck('entity_type');
        
        return response()->json($entityTypes);
    }
} 