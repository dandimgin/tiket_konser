<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index()
    {
        $artists = Artist::all();

        return response()->json([
            'message' => 'Daftar artist berhasil diambil.',
            'data' => $artists,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:255'],
        ]);

        $artist = Artist::create($data);

        return response()->json([
            'message' => 'Artist berhasil ditambahkan.',
            'data' => $artist,
        ], 201);
    }

    public function show(Artist $artist)
    {
        return response()->json([
            'message' => 'Detail artist berhasil diambil.',
            'data' => $artist,
        ]);
    }

    public function update(Request $request, Artist $artist)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:255'],
        ]);

        $artist->update($data);

        return response()->json([
            'message' => 'Artist berhasil diperbarui.',
            'data' => $artist,
        ]);
    }

    public function destroy(Artist $artist)
    {
        $artist->delete();

        return response()->json([
            'message' => 'Artist berhasil dihapus.',
        ]);
    }
}
