<?php

namespace App\Http\Middleware;

use App\Services\ManualAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckManualAuth
{
    public function __construct(
        private readonly ManualAuthenticator $authenticator
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $this->authenticator->user($request);

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
