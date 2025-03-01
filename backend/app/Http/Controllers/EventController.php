<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');
        $events = Event::whereDate('start_time', $date)->get();
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
}