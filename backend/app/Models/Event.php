<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');
        return Auth::user()->events()->whereDate('time', $date)->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'time' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $event = Auth::user()->events()->create($request->all());

        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'time' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $event->update($request->all());

        return response()->json($event);
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->json(null, 204);
    }
}