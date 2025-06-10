<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{

    /**
     * Get user activity summary using JOIN operations
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
                DB::raw('MAX(IFNULL(events.updated_at, IFNULL(projects.updated_at, "1970-01-01"))) as last_activity')
            )
            ->leftJoin('events', 'users.id', '=', 'events.user_id')
            ->leftJoin('projects', 'users.id', '=', 'projects.user_id')
            ->groupBy('users.id')
            ->orderBy('users.name')
            ->get();

        return response()->json($userActivity);
    }
}