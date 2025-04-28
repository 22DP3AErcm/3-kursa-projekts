<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReminderController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $validatedData = $request->validate([
            'type' => 'required|in:email,sms',
            'minutes_before' => 'required|integer|min:0'
        ]);
        
        // Calculate the reminder time
        $remind_at = Carbon::parse($event->start_time)
            ->subMinutes($validatedData['minutes_before']);
            
        // Don't allow reminders in the past
        if ($remind_at->isPast()) {
            return response()->json([
                'message' => 'Cannot set reminder in the past'
            ], 422);
        }
        
        $reminder = new Reminder([
            'type' => $validatedData['type'],
            'minutes_before' => $validatedData['minutes_before'],
            'remind_at' => $remind_at,
            'sent' => false
        ]);
        
        $event->reminders()->save($reminder);
        
        return response()->json($reminder, 201);
    }
    
    public function destroy(Reminder $reminder)
    {
        $reminder->delete();
        return response()->json(null, 204);
    }
    
    public function getForEvent(Event $event)
    {
        return response()->json($event->reminders()->orderBy('minutes_before')->get());
    }
}