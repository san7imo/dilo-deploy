<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Services\RoyaltyPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RoyaltyPayoutRequestController extends Controller
{
    public function store(Request $request, RoyaltyPayoutService $royaltyPayoutService)
    {
        if (!Schema::hasTable('royalty_payout_requests')) {
            return back()->withErrors([
                'payout_request' => 'El módulo de pagos aún no está disponible.',
            ]);
        }

        $access = $royaltyPayoutService->resolveAccessContext($request->user());
        $overview = $royaltyPayoutService->buildArtistRoyaltyOverview($access);

        if (($overview['open_requests_count'] ?? 0) > 0) {
            return back()->withErrors([
                'payout_request' => 'Ya tienes una solicitud de pago abierta. Espera su gestión en administración.',
            ]);
        }

        $availableUsd = (float) ($overview['available_to_request_usd'] ?? 0);
        $minimumUsd = (float) ($overview['minimum_threshold_usd'] ?? 50);

        if ($availableUsd < $minimumUsd) {
            return back()->withErrors([
                'payout_request' => 'Necesitas al menos USD 50.00 disponibles para solicitar pago.',
            ]);
        }

        $payoutRequest = $royaltyPayoutService->createPayoutRequest(
            $access,
            $request->user(),
            $availableUsd
        );

        $royaltyPayoutService->notifyAdminsAboutPayoutRequest($payoutRequest);

        return back()->with('success', 'Solicitud de pago enviada correctamente.');
    }
}
