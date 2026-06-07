<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    private const ROLE_ALIASES = [
        'hr_manager' => 'admin',
        'hr-manager' => 'admin',
        'recruiter' => 'hr_officer',
    ];

    /**
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response|RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $normalizedUserRole = strtolower(trim((string) $user->role));
        $normalizedUserRole = self::ROLE_ALIASES[$normalizedUserRole] ?? $normalizedUserRole;
        $normalizedAllowedRoles = array_map(
            static fn (string $role): string => strtolower(trim($role)),
            $roles
        );

        if (!in_array($normalizedUserRole, $normalizedAllowedRoles, true)) {
            abort(403, 'You are not allowed to access this panel.');
        }

        return $next($request);
    }
}
