<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class AppController extends Controller
{
    /** The shell holds no private data, so it can be cached for offline start-up. */
    public function __invoke(): Response
    {
        return response()->view('pos.app')->withHeaders(['Cache-Control' => 'no-cache', 'X-Robots-Tag' => 'noindex']);
    }
}
