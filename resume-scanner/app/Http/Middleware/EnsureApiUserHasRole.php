<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUserHasRole
{
    private const ROLE_ALIASES = [
        'hr_manager' => 'admin',
        'hr-manager' => 'admin',
        'recruiter' => 'hr_officer',
    ];

    /**
     * @param array<int, string> $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response|JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $normalizedUserRole = strtolower(trim((string) $user->role));
        $normalizedUserRole = self::ROLE_ALIASES[$normalizedUserRole] ?? $normalizedUserRole;

        $normalizedAllowedRoles = array_map(
            static fn (string $role): string => strtolower(trim($role)),
            $roles
        );

        if (!in_array($normalizedUserRole, $normalizedAllowedRoles, true)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}
