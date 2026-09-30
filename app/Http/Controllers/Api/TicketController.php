<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role->name === 'Admin';

        $tickets = $isAdmin
            ? Ticket::with('ticketCategory', 'order.user')->latest()->get()
            : Ticket::with('ticketCategory', 'order')
                ->whereHas('order', fn ($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get();

        return response()->json([
            'message' => 'Daftar tiket berhasil diambil.',
            'data' => $tickets,
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $isAdmin = $user->role->name === 'Admin';

        $ticket->load('ticketCategory', 'order');

        if (!$isAdmin && $ticket->order->user_id !== $user->id) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        return response()->json([
            'message' => 'Detail tiket berhasil diambil.',
            'data' => $ticket,
        ]);
    }
}
