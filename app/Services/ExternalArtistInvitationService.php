<?php

namespace App\Services;

use App\Mail\ExternalArtistInvitationMail;
use App\Models\ExternalArtistInvitation;
use App\Models\Track;
use App\Models\TrackSplitParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExternalArtistInvitationService
{
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
            ExternalArtistInvitation::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return ExternalArtistInvitation::create([
                'email' => $normalizedEmail,
                'token_hash' => hash('sha256', $token),
                'invited_by' => $inviter?->id,
                'invitee_name' => $cleanInviteeName,
                'expires_at' => $expiresAt,
                'metadata' => [
                    'source' => 'admin_artists_module',
                    'invitation_type' => 'standalone_external_artist',
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
