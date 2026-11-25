<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait ReadsDateFilters
{
    /** An unreadable date is treated as an empty filter. */
    private function dateFilter(Request $request, string $key): ?string
    {
        $value = $request->string($key)->toString();

        return Carbon::canBeCreatedFromFormat($value, 'Y-m-d') ? $value : null;
    }
}
