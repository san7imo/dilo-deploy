<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackRequest;
use App\Http\Requests\UpdateTrackRequest;
use App\Models\{Artist, Release, RoyaltyStatementLine, Track, TrackSplitAgreement, TrackSplitParticipant};
use App\Services\TrackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TrackController extends Controller
{
    protected TrackService $trackService;

    public function __construct(TrackService $trackService)
    {
        $this->trackService = $trackService;
    }

    /** Listado de pistas */
    public function index(Request $request)
    {
        $tracks = $this->trackService->getAll(10);

        return Inertia::render('Admin/Tracks/Index', [
            'tracks' => $tracks,
        ]);
    }

    /** Formulario de creación */
    public function create()
    {
        return Inertia::render('Admin/Tracks/Create', [
            'releases' => Release::orderBy('release_date', 'desc')->get(['id', 'title']),
            'artists'  => Artist::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Guardar nueva pista */
    public function store(StoreTrackRequest $request)
    {
        $data = $request->validated();

        // Manejo de archivo de portada
        if ($request->hasFile('cover')) {
            $data['cover_file'] = $request->file('cover');
        } elseif ($request->hasFile('cover_file')) {
            $data['cover_file'] = $request->file('cover_file');
        }

        Log::info('🎵 [TrackController] Datos enviados al servicio', [
            'keys' => array_keys($data),
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->trackService->create($data);

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista creada correctamente');
    }

    /** Formulario de edición */
    public function edit(Track $track)
    {
        return Inertia::render('Admin/Tracks/Edit', [
            'track'    => $track->load(['release', 'artists']),
            'releases' => Release::orderBy('release_date', 'desc')->get(['id', 'title']),
            'artists'  => Artist::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Actualizar pista */
    public function update(UpdateTrackRequest $request, Track $track)
    {
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover_file'] = $request->file('cover');
        } elseif ($request->hasFile('cover_file')) {
            $data['cover_file'] = $request->file('cover_file');
        }

        Log::info('✏️ [TrackController] Datos enviados al servicio (update)', [
            'track_id' => $track->id,
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->trackService->update($track, $data);

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista actualizada correctamente');
    }

    /** Eliminar pista */
    public function destroy(Track $track)
    {
        $this->trackService->delete($track);

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista eliminada correctamente');
    }

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.content');

        $tracks = Track::onlyTrashed()
            ->with(['release:id,title', 'artists:id,name'])
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Track $track): array {
                $activeSplitAgreements = TrackSplitAgreement::query()
                    ->where('track_id', $track->id)
                    ->count();

                $activeRoyaltyLines = RoyaltyStatementLine::query()
                    ->where('track_id', $track->id)
                    ->count();

                $blockedReason = null;
                if ($activeSplitAgreements > 0) {
                    $blockedReason = "Tiene {$activeSplitAgreements} acuerdos de split activos.";
                } elseif ($activeRoyaltyLines > 0) {
                    $blockedReason = "Tiene {$activeRoyaltyLines} líneas de regalías activas.";
                }

                return [
                    'id' => $track->id,
                    'primary' => $track->title,
                    'secondary' => trim(($track->release?->title ?? '-') . ' · ' . $track->artists->pluck('name')->implode(', '), ' ·'),
                    'deleted_at' => $track->deleted_at,
                    'can_force_delete' => $blockedReason === null,
                    'force_delete_blocked_reason' => $blockedReason,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($tracks);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Pistas',
            'items' => $tracks,
            'restoreRoute' => 'admin.tracks.restore',
            'forceDeleteRoute' => 'admin.tracks.force-delete',
            'backRoute' => 'admin.tracks.index',
        ]);
    }

    public function restore(int $trackId)
    {
        Gate::authorize('trash.manage.content');

        $track = Track::onlyTrashed()->findOrFail($trackId);
        $release = Release::withTrashed()->find($track->release_id);
        if (!$release || $release->trashed()) {
            return back()->withErrors([
                'track' => 'No se puede restaurar la pista porque su lanzamiento está eliminado. Restaura primero el lanzamiento.',
            ]);
        }

        DB::transaction(function () use ($track): void {
            $track->restore();
        });

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista restaurada correctamente');
    }

    public function forceDelete(int $trackId)
    {
        Gate::authorize('trash.manage.content');

        $track = Track::withTrashed()->findOrFail($trackId);

        if (!$track->trashed()) {
            return back()->withErrors([
                'track' => 'Solo puedes eliminar permanentemente pistas en papelera.',
            ]);
        }

        $activeSplitAgreements = TrackSplitAgreement::query()
            ->where('track_id', $track->id)
            ->count();

        if ($activeSplitAgreements > 0) {
            return back()->withErrors([
                'track' => "No se puede eliminar permanentemente la pista: tiene {$activeSplitAgreements} acuerdos de split activos.",
            ]);
        }

        $activeRoyaltyLines = RoyaltyStatementLine::query()
            ->where('track_id', $track->id)
            ->count();

        if ($activeRoyaltyLines > 0) {
            return back()->withErrors([
                'track' => "No se puede eliminar permanentemente la pista: tiene {$activeRoyaltyLines} líneas de regalías activas.",
            ]);
        }

        DB::transaction(function () use ($track): void {
            TrackSplitAgreement::onlyTrashed()
                ->where('track_id', $track->id)
                ->get()
                ->each(function (TrackSplitAgreement $agreement): void {
                    TrackSplitParticipant::withTrashed()
                        ->where('track_split_agreement_id', $agreement->id)
                        ->get()
                        ->each
                        ->forceDelete();

                    if (!empty($agreement->contract_path)) {
                        $disk = Storage::disk('contracts_private');
                        if ($disk->exists($agreement->contract_path)) {
                            $disk->delete($agreement->contract_path);
                        }
                    }

                    $agreement->forceDelete();
                });

            $track->artists()->detach();
            $track->forceDelete();
        });

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista eliminada permanentemente');
    }
}
