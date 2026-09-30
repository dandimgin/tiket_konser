<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventArtistController extends Controller
{
    public function index(Event $event)
    {
        $event->load('artists');

        return response()->json([
            'message' => 'Daftar artist pada event berhasil diambil.',
            'data' => $event->artists,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'artist_id' => ['required', 'exists:artists,id'],
        ]);

        if ($event->artists()->where('artist_id', $data['artist_id'])->exists()) {
            return response()->json([
                'message' => 'Artist sudah terdaftar pada event ini.',
            ], 422);
        }

        $event->artists()->attach($data['artist_id']);

        return response()->json([
            'message' => 'Artist berhasil ditambahkan ke event.',
            'data' => $event->load('artists')->artists,
        ], 201);
    }

    public function destroy(Event $event, int $artistId)
    {
        if (!$event->artists()->where('artist_id', $artistId)->exists()) {
            return response()->json([
                'message' => 'Artist tidak ditemukan pada event ini.',
            ], 404);
        }

        $event->artists()->detach($artistId);

        return response()->json([
            'message' => 'Artist berhasil dihapus dari event.',
        ]);
    }
}
