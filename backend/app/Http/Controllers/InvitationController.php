<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    /**
     * Display a listing of pending invitations for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();
        
        $invitations = Invitation::where('invitee_id', $user->id)
            ->where('status', 'pending')
            ->with(['project:id,title', 'inviter:id,name'])
            ->get();
            
        return response()->json($invitations);
    }

    /**
     * Respond to an invitation.
     */
    public function respond(Request $request, Invitation $invitation)
    {
        // Check if user is the invitee
        if ($invitation->invitee_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'response' => 'required|in:accept,decline',
        ]);

        if ($request->response === 'accept') {
            // Add user to project with specified role
            $invitation->project->members()->attach($invitation->invitee_id, [
                'role' => $invitation->role
            ]);
            
            $invitation->update(['status' => 'accepted']);
            return response()->json(['message' => 'Invitation accepted successfully']);
        } else {
            $invitation->update(['status' => 'declined']);
            return response()->json(['message' => 'Invitation declined']);
        }
    }
}