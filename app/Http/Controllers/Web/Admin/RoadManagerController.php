<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoadManagerRequest;
use App\Http\Requests\UpdateRoadManagerRequest;
use App\Models\RoyaltyStatement;
use App\Models\TrackSplitAgreement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RoadManagerController extends Controller
{
    public function index()
    {
        $roadManagers = User::role('roadmanager')
            ->orderBy('name')
            ->paginate(10);

        return Inertia::render('Admin/RoadManagers/Index', [
            'roadManagers' => $roadManagers,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/RoadManagers/Create');
    }

    public function store(StoreRoadManagerRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'email_verified_at' => !empty($data['email_verified']) ? now() : null,
        ]);

        $user->assignRole('roadmanager');

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Road manager creado correctamente');
    }

    public function edit(User $roadmanager)
    {
        if (!$roadmanager->hasRole('roadmanager')) {
            abort(404);
        }

        return Inertia::render('Admin/RoadManagers/Edit', [
            'roadManager' => $roadmanager->only(['id', 'name', 'email', 'phone', 'email_verified_at']),
        ]);
    }

    public function update(UpdateRoadManagerRequest $request, User $roadmanager)
    {
        if (!$roadmanager->hasRole('roadmanager')) {
            abort(404);
        }

        $data = $request->validated();

        $roadmanager->name = $data['name'];
        $roadmanager->email = $data['email'];
        $roadmanager->phone = $data['phone'] ?? null;
        if (!empty($data['email_verified'])) {
            $roadmanager->email_verified_at = $roadmanager->email_verified_at ?? now();
        } else {
            $roadmanager->email_verified_at = null;
        }

        if (!empty($data['password'])) {
            $roadmanager->password = Hash::make($data['password']);
        }

        $roadmanager->save();

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Road manager actualizado correctamente');
    }

    public function destroy(User $roadmanager)
    {
        if (!$roadmanager->hasRole('roadmanager')) {
            abort(404);
        }

        $roadmanager->delete();

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Road manager eliminado correctamente');
    }

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.team');

        $roadManagers = User::onlyTrashed()
            ->role('roadmanager')
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
            return response()->json($roadManagers);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Road Managers',
            'items' => $roadManagers,
            'restoreRoute' => 'admin.roadmanagers.restore',
            'forceDeleteRoute' => 'admin.roadmanagers.force-delete',
            'backRoute' => 'admin.team.index',
        ]);
    }

    public function restore(int $roadmanagerId)
    {
        Gate::authorize('trash.manage.team');

        $roadManager = User::withTrashed()->findOrFail($roadmanagerId);

        if (!$roadManager->hasRole('roadmanager') || !$roadManager->trashed()) {
            abort(404);
        }

        DB::transaction(function () use ($roadManager): void {
            $roadManager->restore();
        });

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Road manager restaurado correctamente');
    }

    public function forceDelete(int $roadmanagerId)
    {
        Gate::authorize('trash.manage.team');

        $roadManager = User::withTrashed()->findOrFail($roadmanagerId);

        if (!$roadManager->hasRole('roadmanager') || !$roadManager->trashed()) {
            abort(404);
        }

        $createdStatements = RoyaltyStatement::withTrashed()
            ->where('created_by', $roadManager->id)
            ->count();

        if ($createdStatements > 0) {
            return back()->withErrors([
                'roadmanager' => "No se puede eliminar permanentemente este usuario: tiene {$createdStatements} royalty statements asociados como creador.",
            ]);
        }

        $createdSplitAgreements = TrackSplitAgreement::withTrashed()
            ->where('created_by', $roadManager->id)
            ->count();

        if ($createdSplitAgreements > 0) {
            return back()->withErrors([
                'roadmanager' => "No se puede eliminar permanentemente este usuario: tiene {$createdSplitAgreements} contratos de split asociados como creador.",
            ]);
        }

        DB::transaction(function () use ($roadManager): void {
            $roadManager->forceDelete();
        });

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Road manager eliminado permanentemente');
    }
}
