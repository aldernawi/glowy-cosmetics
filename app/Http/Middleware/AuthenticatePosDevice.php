<?php

namespace App\Http\Middleware;

use App\Services\PosDeviceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The register is offline far longer than a session lives, so it authenticates with its own
 * long-lived HttpOnly, SameSite=Strict cookie. Requiring a custom header as well means no
 * other site can trigger these endpoints without a CORS preflight, which is never allowed.
 */
class AuthenticatePosDevice
{
    public function __construct(private PosDeviceService $devices) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(PosDeviceService::COOKIE);
        $device = $request->header('X-Glowy-Pos') === '1' ? $this->devices->authenticate($token) : null;

        if ($device === null) {
            return response()->json(['code' => 'unlinked', 'message' => 'هذا الجهاز غير مربوط. اربطيه من لوحة الإدارة.'], 401);
        }

        $device->update(['last_seen_at' => now()]);
        $request->attributes->set('pos_device', $device);

        $response = $next($request);
        $response->headers->setCookie($this->devices->cookie($token, $request->isSecure()));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
