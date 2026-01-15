<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentManagerRequest;
use App\Http\Requests\UpdateContentManagerRequest;
use App\Models\User;
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
            'contentManager' => $content_manager->only(['id', 'name', 'email', 'email_verified_at']),
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
}
