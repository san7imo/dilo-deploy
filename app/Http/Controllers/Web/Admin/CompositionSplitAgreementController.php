<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompositionSplitAgreementRequest;
use App\Models\Artist;
use App\Models\Composition;
use App\Models\CompositionSplitSet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Services\Compositions\CompositionSplitSetService;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CompositionSplitAgreementController extends Controller
{
    public function index(Request $request, Composition $composition)
    {
        $agreements = CompositionSplitSet::query()
            ->where('composition_id', $composition->id)
            ->withCount([
                'participants',
                'participants as writers_count' => fn($query) => $query->where('share_pool', 'writer'),
                'participants as publishers_count' => fn($query) => $query->where('share_pool', 'publisher'),
                'participants as mechanical_payees_count' => fn($query) => $query->where('share_pool', 'mechanical_payee'),
            ])
            ->orderByDesc('version')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Compositions/Splits/Index', [
            'composition' => $composition,
            'agreements' => $agreements,
            'back_url' => $request->query('back'),
        ]);
    }

    public function create(Request $request, Composition $composition)
    {
        $externalArtists = collect();
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $externalArtists = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'external_artist')
                        ->where('guard_name', 'web');
                })
                ->orderByRaw("COALESCE(NULLIF(stage_name, ''), name)")
                ->get(['id', 'name', 'stage_name', 'email']);
        }

        return Inertia::render('Admin/Compositions/Splits/Create', [
            'composition' => $composition,
            'artists' => Artist::orderBy('name')->get(['id', 'name']),
            'externalArtists' => $externalArtists,
            'back_url' => $request->query('back'),
        ]);
    }

    public function store(
        StoreCompositionSplitAgreementRequest $request,
        Composition $composition,
        CompositionSplitSetService $splitSetService
    ) {
        $data = $request->validated();
        $contract = $request->file('contract');

        $originalFilename = $contract->getClientOriginalName();
        $hash = hash_file('sha256', $contract->getRealPath());
        $now = now();
        $directory = sprintf(
            'composition-splits/%s/%s/%s',
            $composition->id,
            $now->format('Y'),
            $now->format('m')
        );

        $storedPath = Storage::disk('contracts_private')
            ->putFileAs($directory, $contract, $originalFilename);

        if (!$storedPath) {
            return back()
                ->withErrors(['contract' => 'No se pudo guardar el contrato.'])
                ->withInput();
        }

        try {
            $splitSetService->createVersionedSet(
                $composition,
                $data,
                [
                    'path' => $storedPath,
                    'original_filename' => $originalFilename,
                    'hash' => $hash,
                ],
                (int) $request->user()->id
            );
        } catch (ValidationException $exception) {
            Storage::disk('contracts_private')->delete($storedPath);
            throw $exception;
        } catch (\Throwable $exception) {
            Storage::disk('contracts_private')->delete($storedPath);
            throw $exception;
        }

        return redirect()
            ->route('admin.compositions.splits.index', $composition->id)
            ->with('success', 'Split de composición creado correctamente.');
    }

    public function download(Composition $composition, CompositionSplitSet $agreement)
    {
        if ($agreement->composition_id !== $composition->id) {
            abort(404);
        }

        $disk = Storage::disk('contracts_private');
        if (!$disk->exists($agreement->contract_path)) {
            abort(404);
        }

        return $disk->download($agreement->contract_path, $agreement->contract_original_filename);
    }
}
