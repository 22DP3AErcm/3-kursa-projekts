<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Display a listing of messages for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get messages where the user is the recipient
        $messages = Message::where('recipient_id', $user->id)
            ->with('sender:id,name,email')
            ->orderByDesc('created_at')
            ->get();
            
        // Add sender_name to each message for easier frontend display
        $messages->each(function ($message) {
            $message->sender_name = $message->sender->name;
            $message->sender_email = $message->sender->email;
            unset($message->sender); // Remove the full sender object to reduce payload
        });
        
        return response()->json($messages);
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required|email|exists:users,email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $recipient = User::where('email', $request->recipient_email)->first();
        
        if (!$recipient) {
            return response()->json(['message' => 'Recipient not found'], 404);
        }

        $message = Message::create([
            'sender_id' => Auth::id(),
            'recipient_id' => $recipient->id,
            'subject' => $request->subject,
            'body' => $request->body,
            'read' => false,
        ]);

        return response()->json($message, 201);
    }

    /**
     * Display the specified message.
     */
    public function show(Message $message)
    {
        // Check if user is sender or recipient
        if ($message->sender_id !== Auth::id() && $message->recipient_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Load sender if not already loaded
        if (!$message->relationLoaded('sender')) {
            $message->load('sender:id,name,email');
            $message->sender_name = $message->sender->name;
            $message->sender_email = $message->sender->email;
            unset($message->sender);
        }

        return response()->json($message);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Message $message)
    {
        // Check if user is the recipient
        if ($message->recipient_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->update(['read' => true]);

        return response()->json(['message' => 'Message marked as read']);
    }

    /**
     * Remove the specified message.
     */
    public function destroy(Message $message)
    {
        // Check if user is sender or recipient
        if ($message->sender_id !== Auth::id() && $message->recipient_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }
}