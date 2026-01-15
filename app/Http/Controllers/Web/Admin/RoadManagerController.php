<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoadManagerRequest;
use App\Http\Requests\UpdateRoadManagerRequest;
use App\Models\User;
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
            'roadManager' => $roadmanager->only(['id', 'name', 'email', 'email_verified_at']),
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
}
