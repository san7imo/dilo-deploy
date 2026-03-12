<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentManagerRequest;
use App\Http\Requests\UpdateContentManagerRequest;
use App\Models\RoyaltyStatement;
use App\Models\TrackSplitAgreement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ContentManagerController extends Controller
{
    public function index()
    {
        $contentManagers = User::role('contentmanager')
            ->orderBy('name')
            ->paginate(10);

        return Inertia::render('Admin/ContentManagers/Index', [
            'contentManagers' => $contentManagers,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ContentManagers/Create');
    }

    public function store(StoreContentManagerRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'email_verified_at' => !empty($data['email_verified']) ? now() : null,
        ]);

        $user->assignRole('contentmanager');

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Gestor de contenido creado correctamente');
    }

    public function edit(User $content_manager)
    {
        if (!$content_manager->hasRole('contentmanager')) {
            abort(404);
        }

        return Inertia::render('Admin/ContentManagers/Edit', [
            'contentManager' => $content_manager->only(['id', 'name', 'email', 'phone', 'email_verified_at']),
        ]);
    }

    public function update(UpdateContentManagerRequest $request, User $content_manager)
    {
        if (!$content_manager->hasRole('contentmanager')) {
            abort(404);
        }

        $data = $request->validated();

        $content_manager->name = $data['name'];
        $content_manager->email = $data['email'];
        $content_manager->phone = $data['phone'] ?? null;

        if (!empty($data['email_verified'])) {
            $content_manager->email_verified_at = $content_manager->email_verified_at ?? now();
        } else {
            $content_manager->email_verified_at = null;
        }

        if (!empty($data['password'])) {
            $content_manager->password = Hash::make($data['password']);
        }

        $content_manager->save();

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Gestor de contenido actualizado correctamente');
    }

    public function destroy(User $content_manager)
    {
        if (!$content_manager->hasRole('contentmanager')) {
            abort(404);
        }

        $content_manager->delete();

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Gestor de contenido eliminado correctamente');
    }

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.team');

        $contentManagers = User::onlyTrashed()
            ->role('contentmanager')
            ->orderBy('name')
            ->paginate(10)
            ->through(function (User $user): array {
                $createdStatements = RoyaltyStatement::withTrashed()
                    ->where('created_by', $user->id)
                    ->count();

                $createdSplitAgreements = TrackSplitAgreement::withTrashed()
                    ->where('created_by', $user->id)
                    ->count();

                $blockedReason = null;
                if ($createdStatements > 0) {
                    $blockedReason = "Tiene {$createdStatements} royalty statements asociados como creador.";
                } elseif ($createdSplitAgreements > 0) {
                    $blockedReason = "Tiene {$createdSplitAgreements} contratos de split asociados como creador.";
                }

                return [
                    'id' => $user->id,
                    'primary' => $user->name,
                    'secondary' => $user->email,
                    'deleted_at' => $user->deleted_at,
                    'can_force_delete' => $blockedReason === null,
                    'force_delete_blocked_reason' => $blockedReason,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($contentManagers);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Content Managers',
            'items' => $contentManagers,
            'restoreRoute' => 'admin.content-managers.restore',
            'forceDeleteRoute' => 'admin.content-managers.force-delete',
            'backRoute' => 'admin.team.index',
        ]);
    }

    public function restore(int $contentManagerId)
    {
        Gate::authorize('trash.manage.team');

        $contentManager = User::withTrashed()->findOrFail($contentManagerId);

        if (!$contentManager->hasRole('contentmanager') || !$contentManager->trashed()) {
            abort(404);
        }

        DB::transaction(function () use ($contentManager): void {
            $contentManager->restore();
        });

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Gestor de contenido restaurado correctamente');
    }

    public function forceDelete(int $contentManagerId)
    {
        Gate::authorize('trash.manage.team');

        $contentManager = User::withTrashed()->findOrFail($contentManagerId);

        if (!$contentManager->hasRole('contentmanager') || !$contentManager->trashed()) {
            abort(404);
        }

        $createdStatements = RoyaltyStatement::withTrashed()
            ->where('created_by', $contentManager->id)
            ->count();

        if ($createdStatements > 0) {
            return back()->withErrors([
                'content_manager' => "No se puede eliminar permanentemente este usuario: tiene {$createdStatements} royalty statements asociados como creador.",
            ]);
        }

        $createdSplitAgreements = TrackSplitAgreement::withTrashed()
            ->where('created_by', $contentManager->id)
            ->count();

        if ($createdSplitAgreements > 0) {
            return back()->withErrors([
                'content_manager' => "No se puede eliminar permanentemente este usuario: tiene {$createdSplitAgreements} contratos de split asociados como creador.",
            ]);
        }

        DB::transaction(function () use ($contentManager): void {
            $contentManager->forceDelete();
        });

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Gestor de contenido eliminado permanentemente');
    }
}
