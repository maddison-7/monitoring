<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        $role = strtolower(trim((string) ($user?->role ?? '')));

        $panelRoute = match ($role) {
            'admin' => 'panel.admin',
            'applicant' => 'applicant.dashboard',
            'hr_officer' => 'hr.dashboard',
            'recruiter' => 'hr.dashboard',
            'hr_manager', 'hr-manager' => 'panel.admin',
            default => 'home',
        };

        return redirect()->route($panelRoute);
    }
}
