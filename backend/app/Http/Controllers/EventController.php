<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');
        $events = Event::whereDate('start_time', $date)
            ->with('reminders') 
            ->get();
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:50',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $event = Event::create($validatedData);
        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:50',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $event->update($validatedData);
        return response()->json($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(null, 204);
    }
    public function getMonthEvents(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        if (!$startDate || !$endDate) {
            return response()->json(['message' => 'Start date and end date are required'], 400);
        }
        
        $events = Event::where('user_id', auth()->id())
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereDate('start_time', '>=', $startDate)
                      ->whereDate('start_time', '<=', $endDate);
            })
            ->with('reminders')
            ->get();
            
        return response()->json($events);
    }
    public function show(Event $event)
    {
        // Check authorization
        if ($event->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Explicitly load the reminders relationship
        $event->load('reminders');
        
        // Debug output
        \Log::info('Event retrieved with ' . $event->reminders->count() . ' reminders');
        
        return response()->json($event);
    }
    
}