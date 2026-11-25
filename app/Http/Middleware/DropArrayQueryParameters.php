<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every query parameter in the application is a single value. Nested ones
 * (?q[]=x) only come from malformed links and would break string handling.
 */
class DropArrayQueryParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->query->replace(array_filter($request->query->all(), fn (mixed $value): bool => ! is_array($value)));

        return $next($request);
    }
}
