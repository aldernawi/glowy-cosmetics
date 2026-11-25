<?php

namespace App\Services;

use App\Models\PosDevice;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie;

class PosDeviceService
{
    public const COOKIE = 'glowy_pos_device';

    /** Browsers cap cookies at 400 days; every successful sync renews it. */
    private const COOKIE_MINUTES = 60 * 24 * 400;

    /** @return array{PosDevice, string} the device and its plain token, which is never stored. */
    public function link(User $cashier, string $name, bool $replaceActive = false): array
    {
        if (! $this->mayOperate($cashier)) {
            throw new DomainException('The selected user cannot operate the point of sale.');
        }

        return DB::transaction(function () use ($cashier, $name, $replaceActive): array {
            $active = PosDevice::query()->whereNull('revoked_at')->lockForUpdate()->get();

            if ($active->isNotEmpty() && ! $replaceActive) {
                throw new DomainException('Another device is already linked.');
            }

            $active->each->update(['revoked_at' => now()]);
            $token = bin2hex(random_bytes(32));

            return [PosDevice::query()->create(['user_id' => $cashier->id, 'name' => $name, 'token_hash' => hash('sha256', $token)]), $token];
        });
    }

    public function authenticate(mixed $token): ?PosDevice
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $device = PosDevice::query()->with('user')->where('token_hash', hash('sha256', $token))->whereNull('revoked_at')->first();

        return $device?->user !== null && $this->mayOperate($device->user) ? $device : null;
    }

    public function mayOperate(User $user): bool
    {
        return $user->canAny(['pos.use', 'admin.access']);
    }

    public function cookie(string $token, bool $secure): Cookie
    {
        return cookie(self::COOKIE, $token, self::COOKIE_MINUTES, '/pos/api', null, $secure, true, false, 'strict');
    }
}
