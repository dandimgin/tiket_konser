<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        $isAdmin = $user->role->name === 'Admin';

        if (!$isAdmin && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        return response()->json([
            'message' => 'Detail payment berhasil diambil.',
            'data' => $order->payment,
        ]);
    }

    public function submitProof(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:100'],
            'proof' => ['required', 'string', 'max:255'],
        ]);

        $payment = $order->payment;

        if (!$payment) {
            return response()->json(['message' => 'Payment tidak ditemukan.'], 404);
        }

        // Auto verify if using mock payment gateway
        if ($data['proof'] === 'MOCK_GATEWAY_SUCCESS') {
            DB::transaction(function () use ($order, $payment, $data) {
                $payment->update([
                    'payment_method' => $data['payment_method'],
                    'proof' => $data['proof'],
                    'status' => 'verified',
                    'paid_at' => now(),
                ]);
                $order->update(['status' => 'paid']);
                foreach ($order->orderItems as $item) {
                    for ($i = 0; $i < $item->quantity; $i++) {
                        Ticket::create([
                            'order_id' => $order->id,
                            'ticket_category_id' => $item->ticket_category_id,
                            'ticket_code' => 'TIK-' . strtoupper(Str::random(10)),
                            'status' => 'active',
                        ]);
                    }
                }
            });
            return response()->json([
                'message' => 'Payment gateway disimulasikan.',
                'data' => $payment,
            ]);
        }

        $payment->update([
            'payment_method' => $data['payment_method'],
            'proof' => $data['proof'],
        ]);

        return response()->json([
            'message' => 'Bukti pembayaran berhasil dikirim.',
            'data' => $payment, 
        ]);
    }

    public function verify(Order $order)
    {
        $payment = $order->payment;

        if (!$payment) {
            return response()->json(['message' => 'Payment tidak ditemukan.'], 404);
        }

        if ($payment->status === 'verified') {
            return response()->json(['message' => 'Payment sudah diverifikasi.'], 422);
        }

        DB::transaction(function () use ($order, $payment) {
            $payment->update([
                'status' => 'verified',
                'paid_at' => now(),
            ]);

            $order->update(['status' => 'paid']);

            foreach ($order->orderItems as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    Ticket::create([
                        'order_id' => $order->id,
                        'ticket_category_id' => $item->ticket_category_id,
                        'ticket_code' => 'TIK-' . strtoupper(Str::random(10)),
                        'status' => 'active',
                    ]);
                }
            }
        });

        $order->load('payment', 'tickets.ticketCategory');

        return response()->json([
            'message' => 'Payment berhasil diverifikasi dan tiket telah diterbitkan.',
            'data' => $order,
        ]);
    }

    public function reject(Order $order)
    {
        $payment = $order->payment;

        if (!$payment) {
            return response()->json(['message' => 'Payment tidak ditemukan.'], 404);
        }

        if ($payment->status === 'verified') {
            return response()->json(['message' => 'Payment yang sudah diverifikasi tidak dapat ditolak.'], 422);
        }

        $payment->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Payment telah ditolak.',
            'data' => $payment,
        ]);
    }

    public function indexAdmin()
    {
        $payments = Order::with('payment', 'user')
            ->whereHas('payment')
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'user' => $order->user->name,
                    'total_amount' => $order->total_amount,
                    'payment' => $order->payment,
                ];
            });

        return response()->json([
            'message' => 'Daftar payment berhasil diambil.',
            'data' => $payments,
        ]);
    }
}
