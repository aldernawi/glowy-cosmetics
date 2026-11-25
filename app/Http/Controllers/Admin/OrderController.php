<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()->latest();
        if ($request->filled('status')) {
            $orders->where('status', $request->string('status')->toString());
        }
        if ($request->filled('payment_status')) {
            $orders->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('q')) {
            $orders->where(fn ($query) => $query->where('number', 'like', '%'.$request->string('q').'%')->orWhere('customer_name', 'like', '%'.$request->string('q').'%')->orWhere('phone', 'like', '%'.$request->string('q').'%'));
        }
        if ($request->filled('from')) {
            $orders->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $orders->whereDate('created_at', '<=', $request->date('to'));
        }

        return view('admin.orders.index', ['orders' => $orders->paginate(25)->withQueryString(), 'statuses' => OrderStatus::cases()]);
    }

    public function show(Order $order, OrderService $orders): View
    {
        return view('admin.orders.show', ['order' => $order->load(['items.productVariant', 'statusHistory.user']), 'nextStatuses' => $orders->nextStatuses($order)]);
    }

    public function print(Order $order): View
    {
        return view('admin.orders.print', ['order' => $order->load('items')]);
    }

    public function update(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value'))]]);
        try {
            $orders->transition($order, OrderStatus::from($request->string('status')->toString()), $request->user());
        } catch (DomainException) {
            return back()->withErrors(['status' => 'انتقال حالة الطلب غير مسموح.']);
        }

        return to_route('admin.orders.index')->with('status', 'تم تحديث حالة الطلب.');
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['payment_status' => ['required', 'in:unpaid,paid']]);
        $order->update(['payment_status' => PaymentStatus::from($request->string('payment_status')->toString())]);

        return to_route('admin.orders.index')->with('status', 'تم تحديث حالة الدفع.');
    }
}
