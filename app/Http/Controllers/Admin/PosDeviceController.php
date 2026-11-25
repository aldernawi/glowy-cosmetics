<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\User;
use App\Services\PosDeviceService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PosDeviceController extends Controller
{
    public function index(): View
    {
        return view('admin.pos.device', [
            'active' => PosDevice::query()->with('user')->whereNull('revoked_at')->latest('id')->first(),
            'cashiers' => User::permission(['pos.use', 'admin.access'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, PosDeviceService $devices): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'replace' => ['nullable', 'boolean'],
        ], ['name.*' => 'أدخلي اسمًا للجهاز.', 'user_id.*' => 'اختاري الكاشير.']);

        try {
            [, $token] = $devices->link(User::query()->findOrFail($validated['user_id']), $validated['name'], $request->boolean('replace'));
        } catch (DomainException) {
            return back()->withInput()->withErrors(['device' => 'تعذر الربط. إن كان هناك جهاز مربوط فأكدي استبداله، وتأكدي أن المستخدم يملك صلاحية البيع.']);
        }

        return redirect()->route('pos.app')->withCookie($devices->cookie($token, $request->isSecure()));
    }

    public function destroy(PosDevice $device): RedirectResponse
    {
        $device->update(['revoked_at' => $device->revoked_at ?? now()]);

        return to_route('admin.pos.device.index')->with('status', 'أُلغي ربط الجهاز.');
    }
}
