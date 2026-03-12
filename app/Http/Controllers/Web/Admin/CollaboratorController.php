<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Models\Collaborator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Models\EventPayment;

class CollaboratorController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/Collaborators/Create');
    }

    public function store(StoreCollaboratorRequest $request)
    {
        Collaborator::create($request->validated());

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Colaborador creado correctamente');
    }

    public function edit(Collaborator $collaborator)
    {
        return Inertia::render('Admin/Collaborators/Edit', [
            'collaborator' => $collaborator,
        ]);
    }

    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator)
    {
        $collaborator->update($request->validated());

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Colaborador actualizado correctamente');
    }

    public function destroy(Collaborator $collaborator)
    {
        $collaborator->delete();

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Colaborador eliminado correctamente');
    }

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.team');

        $collaborators = Collaborator::onlyTrashed()
            ->select('id', 'account_holder', 'bank', 'account_number', 'deleted_at')
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Collaborator $collaborator): array {
                $activePayments = EventPayment::query()
                    ->where('collaborator_id', $collaborator->id)
                    ->count();

                return [
                    'id' => $collaborator->id,
                    'primary' => $collaborator->account_holder,
                    'secondary' => trim(($collaborator->bank ?? '-') . ' · ' . ($collaborator->account_number ?? '-'), ' ·'),
                    'deleted_at' => $collaborator->deleted_at,
                    'can_force_delete' => $activePayments === 0,
                    'force_delete_blocked_reason' => $activePayments > 0
                        ? "Tiene {$activePayments} pagos activos asociados."
                        : null,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($collaborators);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Colaboradores',
            'items' => $collaborators,
            'restoreRoute' => 'admin.collaborators.restore',
            'forceDeleteRoute' => 'admin.collaborators.force-delete',
            'backRoute' => 'admin.team.index',
        ]);
    }

    public function restore(int $collaboratorId)
    {
        Gate::authorize('trash.manage.team');

        $collaborator = Collaborator::onlyTrashed()->findOrFail($collaboratorId);

        DB::transaction(function () use ($collaborator): void {
            $collaborator->restore();
        });

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Colaborador restaurado correctamente');
    }

    public function forceDelete(int $collaboratorId)
    {
        Gate::authorize('trash.manage.team');

        $collaborator = Collaborator::withTrashed()->findOrFail($collaboratorId);

        if (!$collaborator->trashed()) {
            return back()->withErrors([
                'collaborator' => 'Solo puedes eliminar permanentemente colaboradores en papelera.',
            ]);
        }

        $activePayments = EventPayment::query()
            ->where('collaborator_id', $collaborator->id)
            ->count();

        if ($activePayments > 0) {
            return back()->withErrors([
                'collaborator' => "No se puede eliminar permanentemente el colaborador: tiene {$activePayments} pagos activos asociados.",
            ]);
        }

        DB::transaction(function () use ($collaborator): void {
            $collaborator->forceDelete();
        });

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Colaborador eliminado permanentemente');
    }
}
