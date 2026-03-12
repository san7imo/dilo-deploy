<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompositionRequest;
use App\Http\Requests\UpdateCompositionRequest;
use App\Models\Composition;
use App\Models\CompositionSplitSet;
use App\Models\Track;
use App\Services\Compositions\CompositionCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompositionController extends Controller
{
    public function index()
    {
        return redirect()
            ->route('admin.tracks.index')
            ->with('info', 'La gestión de composiciones ahora se realiza desde Tracks.');
    }

    public function create()
    {
        $tracks = Track::query()
            ->select('id', 'title', 'isrc')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/Compositions/Create', [
            'tracks' => $tracks,
        ]);
    }

    public function store(
        StoreCompositionRequest $request,
        CompositionCatalogService $catalogService
    )
    {
        $catalogService->create(
            $request->validated(),
            (int) $request->user()->id
        );

        return redirect()
            ->route('admin.compositions.index')
            ->with('success', 'Composición creada correctamente.');
    }

    public function edit(Composition $composition)
    {
        $tracks = Track::query()
            ->select('id', 'title', 'isrc')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/Compositions/Edit', [
            'composition' => $composition->load('tracks:id,title,isrc', 'registrations'),
            'tracks' => $tracks,
        ]);
    }

    public function update(
        UpdateCompositionRequest $request,
        Composition $composition,
        CompositionCatalogService $catalogService
    )
    {
        $catalogService->update(
            $composition,
            $request->validated(),
            (int) $request->user()->id
        );

        return redirect()
            ->route('admin.compositions.index')
            ->with('success', 'Composición actualizada correctamente.');
    }

    public function destroy(Composition $composition)
    {
        $composition->delete();

        return redirect()
            ->route('admin.compositions.index')
            ->with('success', 'Composición enviada a papelera.');
    }

    public function trash(Request $request)
    {
        $compositions = Composition::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Composition $composition): array {
                $activeSplitAgreements = CompositionSplitSet::query()
                    ->where('composition_id', $composition->id)
                    ->count();

                return [
                    'id' => $composition->id,
                    'primary' => $composition->title,
                    'secondary' => $composition->iswc ?? '-',
                    'deleted_at' => $composition->deleted_at,
                    'can_force_delete' => $activeSplitAgreements === 0,
                    'force_delete_blocked_reason' => $activeSplitAgreements > 0
                        ? "Tiene {$activeSplitAgreements} acuerdos de split de composición activos."
                        : null,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($compositions);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Composiciones',
            'items' => $compositions,
            'restoreRoute' => 'admin.compositions.restore',
            'forceDeleteRoute' => 'admin.compositions.force-delete',
            'backRoute' => 'admin.compositions.index',
        ]);
    }

    public function restore(int $compositionId)
    {
        $composition = Composition::onlyTrashed()->findOrFail($compositionId);

        DB::transaction(function () use ($composition): void {
            $composition->restore();
        });

        return redirect()
            ->route('admin.compositions.index')
            ->with('success', 'Composición restaurada correctamente.');
    }

    public function forceDelete(int $compositionId)
    {
        $composition = Composition::withTrashed()->findOrFail($compositionId);

        if (!$composition->trashed()) {
            return back()->withErrors([
                'composition' => 'Solo puedes eliminar permanentemente composiciones en papelera.',
            ]);
        }

        $activeSplitAgreements = CompositionSplitSet::query()
            ->where('composition_id', $composition->id)
            ->count();

        if ($activeSplitAgreements > 0) {
            return back()->withErrors([
                'composition' => "No se puede eliminar permanentemente: tiene {$activeSplitAgreements} acuerdos de split de composición activos.",
            ]);
        }

        DB::transaction(function () use ($composition): void {
            $composition->tracks()->detach();
            $composition->registrations()->delete();
            $composition->forceDelete();
        });

        return redirect()
            ->route('admin.compositions.index')
            ->with('success', 'Composición eliminada permanentemente.');
    }
}
