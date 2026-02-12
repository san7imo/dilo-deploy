<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Models\Collaborator;
use Inertia\Inertia;

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
}
