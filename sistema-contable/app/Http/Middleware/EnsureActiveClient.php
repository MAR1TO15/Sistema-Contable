<?php

namespace App\Http\Middleware;

use App\Services\ActiveClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveClient
{
    public function __construct(private ActiveClient $activeClient) {}

    /**
     * Send the user to pick a client before entering an accounting screen,
     * and remember where they were going.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->activeClient->get() === null) {
            return redirect()->guest(route('workspace.index'));
        }

        return $next($request);
    }
}
