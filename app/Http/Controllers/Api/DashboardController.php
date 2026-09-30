<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $customerRoleId = Role::where('name', 'Customer')->value('id');

        return response()->json([
            'message' => 'Dashboard data berhasil diambil.',
            'data' => [
                'total_events' => Event::count(),
                'total_artists' => \App\Models\Artist::count(),
                'total_customers' => User::where('role_id', $customerRoleId)->count(),
                'total_orders' => Order::count(),
                'total_tickets_sold' => Ticket::count(),
                'total_revenue' => Order::where('status', 'paid')->sum('total_amount'),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'pending_payments' => \App\Models\Payment::where('status', 'pending')
                    ->whereNotNull('proof')
                    ->count(),
            ],
        ]);
    }
}
