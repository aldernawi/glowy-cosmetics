<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('admin.customers.index', ['customers' => User::query()->withCount('orders')->doesntHave('roles')->latest()->paginate(24)]);
    }

    public function show(User $customer): View
    {
        abort_if($customer->hasAnyRole(['administrator', 'catalog-manager']), 404);

        return view('admin.customers.show', ['customer' => $customer->load(['orders.items'])]);
    }
}
