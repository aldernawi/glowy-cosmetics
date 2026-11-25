<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function success(Request $request): View
    {
        $number = $request->session()->get('last_order_number');
        abort_unless($number, 404);

        return view('storefront.orders.success', ['order' => Order::query()->where('number', $number)->firstOrFail()]);
    }

    public function index(Request $request): View
    {
        return view('storefront.orders.index', ['orders' => Order::query()->where('user_id', $request->user()->id)->latest()->paginate(20)]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('storefront.orders.show', ['order' => $order->load(['items', 'statusHistory'])]);
    }
}
