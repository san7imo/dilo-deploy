<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptExternalArtistInvitationRequest;
use App\Services\ExternalArtistInvitationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExternalArtistInvitationController extends Controller
{
    public function show(string $token, ExternalArtistInvitationService $invitationService): Response
    {
        $invitation = $invitationService->findByToken($token);

        if (!$invitation) {
            return Inertia::render('Auth/AcceptExternalArtistInvitation', [
                'state' => 'invalid',
            ]);
        }

        if ($invitation->isAccepted()) {
            return Inertia::render('Auth/AcceptExternalArtistInvitation', [
                'state' => 'accepted',
            ]);
        }

        if ($invitation->isRevoked() || $invitation->isExpired()) {
            return Inertia::render('Auth/AcceptExternalArtistInvitation', [
                'state' => 'expired',
            ]);
        }

        return Inertia::render('Auth/AcceptExternalArtistInvitation', [
            'state' => 'pending',
            'invitation' => [
                'email' => $invitation->email,
                'invitee_name' => $invitation->invitee_name,
                'track_title' => $invitation->track?->title,
                'expires_at' => $invitation->expires_at?->toDateTimeString(),
            ],
            'token' => $token,
        ]);
    }

    public function accept(
        AcceptExternalArtistInvitationRequest $request,
        string $token,
        ExternalArtistInvitationService $invitationService
    ): RedirectResponse {
        $invitation = $invitationService->findByToken($token);

        if (!$invitation) {
            return back()->withErrors([
                'invitation' => 'La invitación no existe o ya no es válida.',
            ]);
        }

        $invitationService->acceptInvitation($invitation, $request->validated());

        return redirect()
            ->route('login')
            ->with('status', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
    }
}
