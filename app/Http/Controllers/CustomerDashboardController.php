<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', ['ordersCount' => $request->user()->orders()->count(), 'recentOrders' => $request->user()->orders()->latest()->limit(3)->get()]);
    }
}
