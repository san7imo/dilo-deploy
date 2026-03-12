<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizerRequest;
use App\Http\Requests\UpdateOrganizerRequest;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrganizerController extends Controller
{
    public function index()
    {
        $organizers = Organizer::query()
            ->withCount('events')
            ->orderBy('company_name')
            ->paginate(10);

        return Inertia::render('Admin/Organizers/Index', [
            'organizers' => $organizers,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Organizers/Create');
    }

    public function store(StoreOrganizerRequest $request)
    {
        Organizer::create($request->validated());

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Empresario creado correctamente.');
    }

    public function edit(Organizer $organizer)
    {
        return Inertia::render('Admin/Organizers/Edit', [
            'organizer' => $organizer,
        ]);
    }

    public function update(UpdateOrganizerRequest $request, Organizer $organizer)
    {
        $organizer->update($request->validated());

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Empresario actualizado correctamente.');
    }

    public function destroy(Organizer $organizer)
    {
        $organizer->delete();

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Empresario enviado a papelera.');
    }

    public function trash(Request $request)
    {
        $organizers = Organizer::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Organizer $organizer): array {
                $activeEvents = Event::query()
                    ->where('organizer_id', $organizer->id)
                    ->whereNull('deleted_at')
                    ->count();

                return [
                    'id' => $organizer->id,
                    'primary' => $organizer->company_name,
                    'secondary' => trim(($organizer->contact_name ?? '-') . ' · ' . ($organizer->email ?? '-'), ' ·'),
                    'deleted_at' => $organizer->deleted_at,
                    'can_force_delete' => $activeEvents === 0,
                    'force_delete_blocked_reason' => $activeEvents > 0
                        ? "Tiene {$activeEvents} eventos activos asociados."
                        : null,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($organizers);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Empresarios',
            'items' => $organizers,
            'restoreRoute' => 'admin.organizers.restore',
            'forceDeleteRoute' => 'admin.organizers.force-delete',
            'backRoute' => 'admin.organizers.index',
        ]);
    }

    public function restore(int $organizerId)
    {
        $organizer = Organizer::onlyTrashed()->findOrFail($organizerId);

        DB::transaction(function () use ($organizer): void {
            $organizer->restore();
        });

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Empresario restaurado correctamente.');
    }

    public function forceDelete(int $organizerId)
    {
        $organizer = Organizer::withTrashed()->findOrFail($organizerId);

        if (!$organizer->trashed()) {
            return back()->withErrors([
                'organizer' => 'Solo puedes eliminar permanentemente empresarios que estén en papelera.',
            ]);
        }

        $activeEvents = Event::query()
            ->where('organizer_id', $organizer->id)
            ->whereNull('deleted_at')
            ->count();

        if ($activeEvents > 0) {
            return back()->withErrors([
                'organizer' => "No se puede eliminar permanentemente: tiene {$activeEvents} eventos activos asociados.",
            ]);
        }

        DB::transaction(function () use ($organizer): void {
            $organizer->forceDelete();
        });

        return redirect()
            ->route('admin.organizers.index')
            ->with('success', 'Empresario eliminado permanentemente.');
    }
}
