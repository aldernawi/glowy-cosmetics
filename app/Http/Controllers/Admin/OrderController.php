<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index', ['orders' => Order::query()->latest()->paginate(25)]);
    }

    public function update(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value'))]]);
        $orders->transition($order, OrderStatus::from($request->string('status')->toString()), $request->user());

        return to_route('admin.orders.index')->with('status', 'تم تحديث حالة الطلب.');
    }
}
