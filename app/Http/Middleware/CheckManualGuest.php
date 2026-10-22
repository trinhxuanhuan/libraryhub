<?php

namespace App\Http\Middleware;

use App\Services\ManualAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckManualGuest
{
    public function __construct(
        private readonly ManualAuthenticator $authenticator
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if ($this->authenticator->check($request)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
