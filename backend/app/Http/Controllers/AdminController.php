<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get all users
     */
    public function getUsers()
    {
        return response()->json(User::all());
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'telephone' => 'sometimes|string|max:20',
            'is_admin' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Get system stats
     */
    public function getStats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_events' => Event::count(),
            'total_projects' => Project::count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get user activity
     */
    public function getUserActivity()
    {
        // Using JOIN to combine users with their events and projects
        $userActivity = DB::table('users')
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                DB::raw('COUNT(DISTINCT events.id) as event_count'),
                DB::raw('COUNT(DISTINCT projects.id) as project_count'),
                DB::raw('MAX(COALESCE(events.updated_at, projects.updated_at, "1970-01-01")) as last_activity')
            )
            ->leftJoin('events', 'users.id', '=', 'events.user_id')
            ->leftJoin('projects', 'users.id', '=', 'projects.user_id')
            ->groupBy('users.id')
            ->orderBy('users.name')
            ->get();

        return response()->json($userActivity);
    }
}