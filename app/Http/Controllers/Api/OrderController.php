<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role->name === 'Admin';

        $orders = $isAdmin
            ? Order::with('orderItems.ticketCategory', 'user')->latest()->get()
            : $user->orders()->with('orderItems.ticketCategory')->latest()->get();

        return response()->json([
            'message' => 'Daftar order berhasil diambil.',
            'data' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_category_id' => ['required', 'exists:ticket_categories,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        return DB::transaction(function () use ($request) {
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $category = TicketCategory::lockForUpdate()->findOrFail($item['ticket_category_id']);

                $available = $category->quota - $category->sold;
                if ($item['quantity'] > $available) {
                    abort(422, "Stok tidak cukup untuk kategori {$category->name}. Tersedia: {$available}.");
                }

                $subtotal = $category->price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'ticket_category_id' => $category->id,
                    'quantity' => $item['quantity'],
                    'price' => $category->price,
                    'subtotal' => $subtotal,
                ];

                $category->increment('sold', $item['quantity']);
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_code' => 'ORD-' . strtoupper(Str::random(10)),
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'ordered_at' => now(),
            ]);

            $order->orderItems()->createMany($orderItems);

            $order->payment()->create([
                'status' => 'pending',
            ]);

            $order->load('orderItems.ticketCategory', 'payment');

            return response()->json([
                'message' => 'Order berhasil dibuat.',
                'data' => $order,
            ], 201);
        });
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        $isAdmin = $user->role->name === 'Admin';

        if (!$isAdmin && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $order->load('orderItems.ticketCategory', 'payment', 'tickets.ticketCategory');

        return response()->json([
            'message' => 'Detail order berhasil diambil.',
            'data' => $order,
        ]);
    }
}
