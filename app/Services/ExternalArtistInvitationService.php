<?php

namespace App\Services;

use App\Mail\ExternalArtistInvitationMail;
use App\Models\Artist;
use App\Models\ExternalArtistInvitation;
use App\Models\Track;
use App\Models\TrackSplitParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExternalArtistInvitationService
{
    public function __construct(
        private readonly ArtistCatalogService $artistCatalogService
    ) {
    }

    public function inviteForParticipants(Track $track, iterable $participants, ?User $inviter = null): array
    {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($participants as $participant) {
            $result = $this->inviteForParticipant($track, $participant, $inviter);

            if ($result === 'sent') {
                $sent++;
                continue;
            }

            if ($result === 'failed') {
                $failed++;
                continue;
            }

            $skipped++;
        }

        return [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    public function findByToken(string $token): ?ExternalArtistInvitation
    {
        $hash = hash('sha256', trim($token));

        return ExternalArtistInvitation::query()
            ->with(['track:id,title', 'participant:id,track_split_agreement_id,payee_email,name'])
            ->where('token_hash', $hash)
            ->first();
    }

    public function acceptInvitation(ExternalArtistInvitation $invitation, array $payload): User
    {
        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => 'Esta invitación ya no está disponible.',
            ]);
        }

        $email = $this->normalizeEmail($invitation->email);
        $activeUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($activeUser) {
            throw ValidationException::withMessages([
                'email' => 'Este correo ya está registrado.',
            ]);
        }

        return DB::transaction(function () use ($payload, $invitation, $email) {
            $user = User::create([
                'name' => trim((string) $payload['name']),
                'stage_name' => trim((string) $payload['stage_name']),
                'email' => $email,
                'phone' => !empty($payload['phone']) ? trim((string) $payload['phone']) : null,
                'identification_type' => trim((string) $payload['identification_type']),
                'identification_number' => trim((string) $payload['identification_number']),
                'additional_information' => !empty($payload['additional_information'])
                    ? trim((string) $payload['additional_information'])
                    : null,
                'password' => $payload['password'],
            ]);

            $user->assignRole('external_artist');

            $linkedArtist = null;
            $artistId = data_get($invitation->metadata, 'artist_id');
            if ($artistId) {
                $linkedArtist = Artist::query()->find($artistId);
            }

            $this->artistCatalogService->createOrAttachExternalArtist(
                displayName: $this->artistCatalogService->resolveDisplayName(
                    $payload['stage_name'] ?? null,
                    $invitation->invitee_name ?? null,
                    $payload['name'] ?? null
                ),
                user: $user,
                artist: $linkedArtist
            );

            TrackSplitParticipant::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(payee_email) = ?', [$email])
                ->update(['user_id' => $user->id]);

            $invitation->update([
                'accepted_user_id' => $user->id,
                'accepted_at' => now(),
            ]);

            return $user;
        });
    }

    public function inviteStandalone(string $inviteeName, string $email, ?User $inviter = null): void
    {
        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail === null) {
            throw ValidationException::withMessages([
                'email' => 'Debes ingresar un correo válido.',
            ]);
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => 'Este correo ya está registrado.',
            ]);
        }

        $token = Str::random(64);
        $expiresAt = now()->addDays(7);
        $cleanInviteeName = trim((string) $inviteeName);

        $invitation = DB::transaction(function () use ($normalizedEmail, $token, $expiresAt, $inviter, $cleanInviteeName): ExternalArtistInvitation {
            $existingArtistId = ExternalArtistInvitation::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->orderByDesc('id')
                ->get()
                ->map(fn (ExternalArtistInvitation $invitation) => data_get($invitation->metadata, 'artist_id'))
                ->filter()
                ->map(fn ($artistId) => (int) $artistId)
                ->first();

            ExternalArtistInvitation::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $artist = $this->artistCatalogService->createOrAttachExternalArtist(
                displayName: $cleanInviteeName !== '' ? $cleanInviteeName : 'artista-externo',
                artist: $existingArtistId ? Artist::query()->find($existingArtistId) : null
            );

            return ExternalArtistInvitation::create([
                'email' => $normalizedEmail,
                'token_hash' => hash('sha256', $token),
                'invited_by' => $inviter?->id,
                'invitee_name' => $cleanInviteeName,
                'expires_at' => $expiresAt,
                'metadata' => [
                    'source' => 'admin_artists_module',
                    'invitation_type' => 'standalone_external_artist',
                    'artist_id' => $artist->id,
                ],
            ]);
        });

        $url = route('external-artists.invitations.show', ['token' => $token]);

        try {
            Mail::to($normalizedEmail)->send(new ExternalArtistInvitationMail(
                invitationUrl: $url,
                trackTitle: null,
                inviteeName: $cleanInviteeName !== '' ? $cleanInviteeName : null,
                expiresAtText: $expiresAt->format('d/m/Y H:i')
            ));
        } catch (\Throwable $exception) {
            $invitation->update(['revoked_at' => now()]);

            Log::error('No se pudo enviar invitación manual de artista externo', [
                'email' => $normalizedEmail,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'No se pudo enviar la invitación. Intenta nuevamente.',
            ]);
        }
    }

    /**
     * @return array{mode:string, email:string}
     */
    public function resendForExistingArtist(Artist $artist, string $email, ?User $inviter = null): array
    {
        if ($artist->artist_origin !== 'external') {
            throw ValidationException::withMessages([
                'artist' => 'Solo puedes reenviar acceso a artistas externos.',
            ]);
        }

        if (method_exists($artist, 'trashed') && $artist->trashed()) {
            throw ValidationException::withMessages([
                'artist' => 'No puedes reenviar acceso a un artista eliminado.',
            ]);
        }

        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail === null) {
            throw ValidationException::withMessages([
                'email' => 'Debes ingresar un correo válido.',
            ]);
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->when($artist->user_id, fn ($query) => $query->where('id', '!=', $artist->user_id))
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => 'El correo ya está registrado en otra cuenta activa.',
            ]);
        }

        if ($artist->user_id) {
            return $this->updateExistingArtistAccess($artist, $normalizedEmail);
        }

        return $this->resendPendingArtistInvitation($artist, $normalizedEmail, $inviter);
    }

    /**
     * @return array{mode:string, email:string}
     */
    private function resendPendingArtistInvitation(Artist $artist, string $email, ?User $inviter = null): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(7);

        $invitation = DB::transaction(function () use ($artist, $email, $token, $expiresAt, $inviter): ExternalArtistInvitation {
            ExternalArtistInvitation::query()
                ->where('metadata->artist_id', $artist->id)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return ExternalArtistInvitation::query()->create([
                'email' => $email,
                'token_hash' => hash('sha256', $token),
                'invited_by' => $inviter?->id,
                'invitee_name' => $artist->name,
                'expires_at' => $expiresAt,
                'metadata' => [
                    'source' => 'admin_artists_module',
                    'invitation_type' => 'standalone_external_artist',
                    'artist_id' => $artist->id,
                    'resent_for_existing_artist' => true,
                ],
            ]);
        });

        $url = route('external-artists.invitations.show', ['token' => $token]);

        try {
            Mail::to($email)->send(new ExternalArtistInvitationMail(
                invitationUrl: $url,
                trackTitle: null,
                inviteeName: $artist->name,
                expiresAtText: $expiresAt->format('d/m/Y H:i')
            ));
        } catch (\Throwable $exception) {
            $invitation->update(['revoked_at' => now()]);

            Log::error('No se pudo reenviar invitación de artista externo existente', [
                'artist_id' => $artist->id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'No se pudo enviar la invitación. Intenta nuevamente.',
            ]);
        }

        return [
            'mode' => 'invitation',
            'email' => $email,
        ];
    }

    /**
     * @return array{mode:string, email:string}
     */
    private function updateExistingArtistAccess(Artist $artist, string $email): array
    {
        $user = DB::transaction(function () use ($artist, $email): User {
            $user = User::query()->findOrFail($artist->user_id);

            $user->email = $email;
            if (!$user->hasRole('external_artist')) {
                $user->assignRole('external_artist');
            }

            if ($user->hasRole('artist')) {
                $user->removeRole('artist');
            }

            if ($user->isDirty()) {
                $user->save();
            }

            $artist->update([
                'artist_origin' => 'external',
                'has_public_profile' => false,
            ]);

            return $user->fresh();
        });

        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('No se pudo enviar reset de contraseña a artista externo', [
                'artist_id' => $artist->id,
                'user_id' => $user->id,
                'email' => $email,
                'status' => $status,
            ]);

            throw ValidationException::withMessages([
                'email' => 'El correo fue actualizado, pero no se pudo enviar el enlace de acceso.',
            ]);
        }

        return [
            'mode' => 'access',
            'email' => $email,
        ];
    }

    private function inviteForParticipant(Track $track, TrackSplitParticipant $participant, ?User $inviter = null): string
    {
        $email = $this->normalizeEmail($participant->payee_email);

        if ($participant->artist_id || $participant->user_id || $email === null) {
            return 'skipped';
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existingUser) {
            $participant->update(['user_id' => $existingUser->id]);
            return 'skipped';
        }

        $token = Str::random(64);
        $expiresAt = now()->addDays(7);

        $invitation = DB::transaction(function () use ($inviter, $participant, $email, $token, $track, $expiresAt): ExternalArtistInvitation {
            ExternalArtistInvitation::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('track_id', $track->id)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return ExternalArtistInvitation::create([
                'email' => $email,
                'token_hash' => hash('sha256', $token),
                'track_id' => $track->id,
                'track_split_participant_id' => $participant->id,
                'invited_by' => $inviter?->id,
                'invitee_name' => $participant->name,
                'expires_at' => $expiresAt,
                'metadata' => [
                    'participant_role' => $participant->role,
                    'percentage' => (float) $participant->percentage,
                ],
            ]);
        });

        $url = route('external-artists.invitations.show', ['token' => $token]);

        try {
            Mail::to($email)->send(new ExternalArtistInvitationMail(
                invitationUrl: $url,
                trackTitle: $track->title,
                inviteeName: $participant->name,
                expiresAtText: $expiresAt->format('d/m/Y H:i')
            ));

            return 'sent';
        } catch (\Throwable $exception) {
            $invitation->update(['revoked_at' => now()]);

            Log::error('No se pudo enviar invitación de artista externo', [
                'email' => $email,
                'track_id' => $track->id,
                'error' => $exception->getMessage(),
            ]);

            return 'failed';
        }
    }

    private function normalizeEmail(?string $email): ?string
    {
        $value = trim((string) $email);

        if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return Str::lower($value);
    }
}
