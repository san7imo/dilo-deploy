<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $roadManagers = User::role('roadmanager')
            ->select('id', 'name', 'email', 'phone', 'email_verified_at')
            ->orderBy('name')
            ->paginate(10, ['id', 'name', 'email', 'phone', 'email_verified_at'], 'road_page')
            ->withQueryString();

        $contentManagers = User::role('contentmanager')
            ->select('id', 'name', 'email', 'phone', 'email_verified_at')
            ->orderBy('name')
            ->paginate(10, ['id', 'name', 'email', 'phone', 'email_verified_at'], 'content_page')
            ->withQueryString();

        $collaborators = Collaborator::query()
            ->orderBy('account_holder')
            ->paginate(10, ['*'], 'collaborator_page')
            ->withQueryString();

        return Inertia::render('Admin/Team/Index', [
            'roadManagers' => $roadManagers,
            'contentManagers' => $contentManagers,
            'collaborators' => $collaborators,
            'canManageRoadManagers' => (bool) $user?->hasRole('admin') || (bool) $user?->hasRole('contentmanager'),
            'canManageContentManagers' => (bool) $user?->hasRole('admin'),
            'canManageCollaborators' => (bool) $user?->hasRole('admin'),
        ]);
    }
}
