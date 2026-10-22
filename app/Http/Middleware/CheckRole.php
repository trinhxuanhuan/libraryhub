<?php

namespace App\Http\Middleware;

use App\Services\ManualAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function __construct(
        private readonly ManualAuthenticator $authenticator
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $this->authenticator->user($request);

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! in_array($user->role->value, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
