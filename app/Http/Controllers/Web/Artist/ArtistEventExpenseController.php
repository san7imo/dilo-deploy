<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtistEventExpenseRequest;
use App\Http\Requests\UpdateArtistEventExpenseRequest;
use App\Models\Event;
use App\Models\ArtistEventExpense;
use App\Services\ArtistEventExpenseService;
use Illuminate\Http\Request;

class ArtistEventExpenseController extends Controller
{
    /**
     * Registrar un gasto personal del artista (solo admin o roadmanager)
     */
    public function store(
        StoreArtistEventExpenseRequest $request,
        Event $event,
        ArtistEventExpenseService $service
    ) {
        $data = $request->validated();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
        }

        $artistId = $data['artist_id'];

        $service->create($event, $artistId, $request->user(), $data);

        return back()->with('success', 'Gasto personal del artista registrado correctamente');
    }

    /**
     * Actualizar un gasto personal del artista (solo admin o roadmanager)
     */
    public function update(
        UpdateArtistEventExpenseRequest $request,
        ArtistEventExpense $artistExpense,
        ArtistEventExpenseService $service
    ) {
        $data = $request->validated();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
        }

        $service->update($artistExpense, $data);

        return back()->with('success', 'Gasto personal actualizado correctamente');
    }

    /**
     * Aprobar un gasto personal del artista (solo admin)
     */
    public function approve(
        Request $request,
        ArtistEventExpense $artistExpense,
        ArtistEventExpenseService $service
    ) {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Solo los administradores pueden aprobar gastos.');
        }

        $service->approve($artistExpense);

        return back()->with('success', 'Gasto personal aprobado correctamente');
    }

    /**
     * Eliminar un gasto personal del artista (solo admin o roadmanager)
     */
    public function destroy(
        Request $request,
        ArtistEventExpense $artistExpense,
        ArtistEventExpenseService $service
    ) {
        $user = $request->user();

        // Admin puede eliminar cualquier gasto
        if (!$user->hasRole('admin')) {
            // Road manager solo puede eliminar gastos del evento que maneja
            if (
                !$user->hasRole('roadmanager') ||
                !$artistExpense->event->roadManagers()->where('users.id', $user->id)->exists()
            ) {
                abort(403, 'No tienes permiso para eliminar este gasto.');
            }
        }

        $service->delete($artistExpense);

        return back()->with('success', 'Gasto personal eliminado correctamente');
    }
}
