<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketCategory;
use Illuminate\Http\Request;

class TicketCategoryController extends Controller
{
    public function index(Event $event)
    {
        return response()->json([
            'message' => 'Daftar kategori tiket berhasil diambil.',
            'data' => $event->ticketCategories,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'quota' => ['required', 'integer', 'min:1'],
        ]);

        $category = $event->ticketCategories()->create($data);

        return response()->json([
            'message' => 'Kategori tiket berhasil ditambahkan.',
            'data' => $category,
        ], 201);
    }

    public function show(Event $event, TicketCategory $ticketCategory)
    {
        if ($ticketCategory->event_id !== $event->id) {
            return response()->json(['message' => 'Kategori tiket tidak ditemukan pada event ini.'], 404);
        }

        return response()->json([
            'message' => 'Detail kategori tiket berhasil diambil.',
            'data' => $ticketCategory,
        ]);
    }

    public function update(Request $request, Event $event, TicketCategory $ticketCategory)
    {
        if ($ticketCategory->event_id !== $event->id) {
            return response()->json(['message' => 'Kategori tiket tidak ditemukan pada event ini.'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'quota' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if (isset($data['quota']) && $data['quota'] < $ticketCategory->sold) {
            return response()->json([
                'message' => 'Quota tidak boleh kurang dari jumlah tiket yang sudah terjual.',
            ], 422);
        }

        $ticketCategory->update($data);

        return response()->json([
            'message' => 'Kategori tiket berhasil diperbarui.',
            'data' => $ticketCategory,
        ]);
    }

    public function destroy(Event $event, TicketCategory $ticketCategory)
    {
        if ($ticketCategory->event_id !== $event->id) {
            return response()->json(['message' => 'Kategori tiket tidak ditemukan pada event ini.'], 404);
        }

        $ticketCategory->delete();

        return response()->json([
            'message' => 'Kategori tiket berhasil dihapus.',
        ]);
    }
}
