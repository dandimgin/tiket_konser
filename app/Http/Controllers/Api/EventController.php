<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('artists', 'ticketCategories')->latest()->get();

        return response()->json([
            'message' => 'Daftar event berhasil diambil.',
            'data' => $events,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'poster' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'closed', 'finished'])],
        ]);

        $event = Event::create($data);

        return response()->json([
            'message' => 'Event berhasil ditambahkan.',
            'data' => $event,
        ], 201);
    }

    public function show(Event $event)
    {
        return response()->json([
            'message' => 'Detail event berhasil diambil.',
            'data' => $event->load('artists', 'ticketCategories'),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'event_date' => ['sometimes', 'required', 'date'],
            'poster' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'closed', 'finished'])],
        ]);

        $event->update($data);

        return response()->json([
            'message' => 'Event berhasil diperbarui.',
            'data' => $event,
        ]);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'message' => 'Event berhasil dihapus.',
        ]);
    }
}
