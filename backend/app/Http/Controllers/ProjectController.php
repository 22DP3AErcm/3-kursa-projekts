<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invitation;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects for the authenticated user.
     */
    public function index()
    {
        // Get both owned projects and projects where user is a member
        $user = Auth::user();
        
        // Get projects created by the user
        $ownedProjects = $user->createdProjects;
        
        // Get projects the user is a member of
        $memberProjects = $user->projects;
        
        // Merge the two collections (avoiding duplicates)
        $allProjects = $ownedProjects->merge($memberProjects)->unique('id');
        
        return response()->json($allProjects);
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // Add creator as a member with owner role
        $project->members()->attach(Auth::id(), ['role' => 'owner']);

        return response()->json($project, 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        // Check if user has access to this project
        if ($project->user_id !== Auth::id() && !$project->members->contains(Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Load blocks and items
        $project->load(['blocks.items']);
        
        return response()->json($project);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        // Check if user has access to update this project
        if ($project->user_id !== Auth::id() && 
            !$project->members->where('id', Auth::id())->where('pivot.role', 'owner')->count()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($request->only(['title', 'description']));

        return response()->json($project);
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        // ONLY the original creator/owner can delete the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['message' => 'Only the project owner can delete this project'], 403);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Get all members of a project
     */
    public function getMembers(Project $project)
    {
        try {
            // Check if user has access to this project
            if (!$this->checkProjectAccess($project)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Get the project owner
            $owner = User::find($project->user_id);
            
            if (!$owner) {
                return response()->json(['message' => 'Project owner not found'], 404);
            }
            
            // Get members WITHOUT the owner, ensuring uniqueness by user ID
            $members = $project->members()
                ->where('users.id', '!=', $project->user_id)
                ->select('users.*', 'project_user.role') // Select specific columns
                ->distinct('users.id') // Ensure uniqueness by user ID
                ->get();
            
            // Format the owner data
            $ownerData = [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'pivot' => [
                    'role' => 'owner'
                ]
            ];
            
            // Add owner first, then other members (with uniqueness check)
            $allMembers = collect([$ownerData])->merge($members->unique('id'));
            
            return response()->json($allMembers);
        } catch (\Exception $e) {
            \Log::error('Error in getMembers: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching members: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove a member from the project
     */
    public function removeMember(Project $project, User $user)
    {
        $currentUser = Auth::user();
        
        // Can't remove the project owner
        if ($user->id === $project->user_id) {
            return response()->json(['message' => 'Cannot remove the project owner'], 403);
        }

        // Check if current user is the owner or a co-owner
        $isOwner = $project->user_id === $currentUser->id;
        $isCoOwner = $project->members()
            ->where('user_id', $currentUser->id)
            ->where('role', 'co-owner')
            ->exists();
            
        // Only owner and co-owners can remove other members
        if (!$isOwner && !$isCoOwner && $currentUser->id !== $user->id) {
            return response()->json(['message' => 'Only the project owner or co-owners can remove members'], 403);
        }

        // Remove the user from the project
        $project->members()->detach($user->id);

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You have left the project']);
        } else {
            return response()->json(['message' => 'Member removed successfully']);
        }
    }

    /**
     * Leave the project (for members)
     */
    public function leaveProject(Project $project)
    {
        $user = Auth::user();
        
        // Owners can't leave their own project
        if ($project->user_id === $user->id) {
            return response()->json(['message' => 'Project owners cannot leave their project. Transfer ownership first or delete the project.'], 400);
        }
        
        // Check if user is a member
        if (!$project->members->contains($user->id)) {
            return response()->json(['message' => 'You are not a member of this project'], 400);
        }
        
        // Remove the user from the project
        $project->members()->detach($user->id);
        
        return response()->json(['message' => 'You have left the project successfully']);
    }

    /**
     * Helper method to check if user has access to project
     */
    private function checkProjectAccess(Project $project)
    {
        $user = Auth::user();
        
        // User is the owner or a member of the project
        return $project->user_id === $user->id || $project->members->contains($user->id);
    }

    /**
     * Invite a user to the project
     */
    public function inviteUser(Request $request, Project $project)
    {
        // Check if the authenticated user is the owner or a co-owner
        $user = Auth::user();
        $isOwner = $project->user_id === $user->id;
        $isCoOwner = $project->members()
            ->where('user_id', $user->id)
            ->where('role', 'co-owner')
            ->exists();
            
        if (!$isOwner && !$isCoOwner) {
            return response()->json(['message' => 'Only the project owner or co-owners can invite members'], 403);
        }

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|in:member,viewer,co-owner',
        ]);

        // Only the original owner can create co-owners
        if ($request->role === 'co-owner' && !$isOwner) {
            return response()->json(['message' => 'Only the project owner can assign co-owner role'], 403);
        }

        // Look up the user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found. Please make sure they have registered first.'], 404);
        }

        // Prevent inviting the owner (just in case)
        if ($user->id === $project->user_id) {
            return response()->json(['message' => 'Cannot invite the owner to their own project'], 400);
        }

        // Check if user is already a member
        if ($project->members->contains($user->id)) {
            return response()->json(['message' => 'User is already a member of this project'], 400);
        }

        // Check if there's already a pending invitation
        $existingInvitation = Invitation::where('project_id', $project->id)
            ->where('invitee_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingInvitation) {
            return response()->json(['message' => 'An invitation has already been sent to this user'], 400);
        }

        // Create invitation
        Invitation::create([
            'project_id' => $project->id,
            'inviter_id' => Auth::id(),
            'invitee_id' => $user->id,
            'role' => $request->role,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Invitation sent successfully']);
    }
}