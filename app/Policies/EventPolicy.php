<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    /**
     * Ver información financiera del evento
     */
    public function viewFinancial(User $user, Event $event): bool
    {
        // Admin ve todo
        if ($user->hasRole('admin')) {
            return true;
        }

        // Road manager solo puede ver eventos asignados
        if ($user->hasRole('roadmanager')) {
            return $event->roadManagers()
                ->where('users.id', $user->id)
                ->exists();
        }

        // Artista solo si es el artista principal
        if (
            $user->hasRole('artist') &&
            $user->artist &&
            $event->main_artist_id === $user->artist->id
        ) {
            return true;
        }

        return false;
    }
}
