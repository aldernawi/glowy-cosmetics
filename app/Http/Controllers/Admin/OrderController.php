<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Admin\Concerns\ReadsDateFilters;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ReadsDateFilters;

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
        $orders->when($this->dateFilter($request, 'from'), fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($this->dateFilter($request, 'to'), fn ($query, string $to) => $query->whereDate('created_at', '<=', $to));

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

    public function updatePayment(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $request->validate(['payment_status' => ['required', 'in:unpaid,paid']]);
        try {
            $orders->updatePayment($order, PaymentStatus::from($request->string('payment_status')->toString()));
        } catch (DomainException) {
            return back()->withErrors(['payment_status' => 'لا يمكن تعليم طلب ملغي أو مرتجع كمدفوع.']);
        }

        return to_route('admin.orders.index')->with('status', 'تم تحديث حالة الدفع.');
    }
}
