<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $roadManagers = User::role('roadmanager')
            ->select('id', 'name', 'email', 'email_verified_at')
            ->orderBy('name')
            ->get();

        $contentManagers = User::role('contentmanager')
            ->select('id', 'name', 'email', 'email_verified_at')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Team/Index', [
            'roadManagers' => $roadManagers,
            'contentManagers' => $contentManagers,
            'canManageRoadManagers' => (bool) $user?->hasRole('admin') || (bool) $user?->hasRole('contentmanager'),
            'canManageContentManagers' => (bool) $user?->hasRole('admin'),
        ]);
    }
}
