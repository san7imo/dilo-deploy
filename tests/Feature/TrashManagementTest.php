<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Collaborator;
use App\Models\Event;
use App\Models\EventPayment;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrashManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_manager_cannot_restore_artist_from_trash(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $contentManager = User::factory()->create();
        $contentManager->assignRole($this->role('contentmanager'));

        $artist = Artist::create(['name' => 'Restore Target Artist']);
        $artist->delete();

        $this->actingAs($contentManager)
            ->patch(route('admin.artists.restore', $artist->id))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.artists.restore', $artist->id))
            ->assertRedirect(route('admin.artists.index'));

        $this->assertNull($artist->fresh()?->deleted_at);
    }

    public function test_admin_can_force_delete_artist_from_trash(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $artist = Artist::create(['name' => 'Force Delete Artist']);
        $artist->delete();

        $this->actingAs($admin)
            ->delete(route('admin.artists.force-delete', $artist->id))
            ->assertRedirect(route('admin.artists.index'));

        $this->assertDatabaseMissing('artists', ['id' => $artist->id]);
    }

    public function test_restore_fails_when_unique_name_conflicts_with_active_artist(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $trashedArtist = Artist::create(['name' => 'Nombre Unico']);
        $trashedArtistId = $trashedArtist->id;
        $trashedArtist->delete();

        Artist::create(['name' => 'Nombre Unico']);

        $this->actingAs($admin)
            ->from(route('admin.artists.trash'))
            ->patch(route('admin.artists.restore', $trashedArtistId))
            ->assertRedirect(route('admin.artists.trash'))
            ->assertSessionHasErrors('name');

        $this->assertNotNull(Artist::withTrashed()->find($trashedArtistId)?->deleted_at);
    }

    public function test_release_force_delete_is_blocked_when_has_active_tracks(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $artist = Artist::create(['name' => 'Artist for Release Force Delete']);
        $release = Release::create([
            'artist_id' => $artist->id,
            'title' => 'Release with Active Track',
        ]);
        $release->delete();

        Track::create([
            'release_id' => $release->id,
            'title' => 'Active Track',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.releases.trash'))
            ->delete(route('admin.releases.force-delete', $release->id))
            ->assertRedirect(route('admin.releases.trash'))
            ->assertSessionHasErrors('release');

        $this->assertNotNull(Release::withTrashed()->find($release->id)?->deleted_at);
    }

    public function test_release_restore_is_blocked_when_related_artist_is_trashed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $artist = Artist::create(['name' => 'Trashed Artist']);
        $release = Release::create([
            'artist_id' => $artist->id,
            'title' => 'Release linked to trashed artist',
        ]);
        $release->delete();
        $artist->delete();

        $this->actingAs($admin)
            ->from(route('admin.releases.trash'))
            ->patch(route('admin.releases.restore', $release->id))
            ->assertRedirect(route('admin.releases.trash'))
            ->assertSessionHasErrors('release');

        $this->assertNotNull(Release::withTrashed()->find($release->id)?->deleted_at);
    }

    public function test_soft_deleting_artist_does_not_delete_related_release_or_track(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $artist = Artist::create(['name' => 'Artist With Catalog']);
        $release = Release::create([
            'artist_id' => $artist->id,
            'title' => 'Release Preserved',
        ]);
        $track = Track::create([
            'release_id' => $release->id,
            'title' => 'Track Preserved',
        ]);
        $track->artists()->sync([$artist->id]);

        $this->actingAs($admin)
            ->delete(route('admin.artists.destroy', $artist))
            ->assertRedirect(route('admin.artists.index'));

        $this->assertSoftDeleted('artists', ['id' => $artist->id]);
        $this->assertDatabaseHas('releases', ['id' => $release->id]);
        $this->assertDatabaseHas('tracks', ['id' => $track->id]);
        $this->assertDatabaseHas('track_artist', [
            'artist_id' => $artist->id,
            'track_id' => $track->id,
        ]);
    }

    public function test_external_artist_cannot_be_sent_to_trash_when_has_active_release(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $artist = Artist::create([
            'name' => 'External With Release',
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        Release::create([
            'artist_id' => $artist->id,
            'title' => 'Release Blocks Delete',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.artists.index'))
            ->delete(route('admin.artists.destroy', $artist))
            ->assertRedirect(route('admin.artists.index'))
            ->assertSessionHasErrors('artist');

        $this->assertNull($artist->fresh()?->deleted_at);
    }

    public function test_external_artist_cannot_be_sent_to_trash_when_has_active_track_association(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $mainArtist = Artist::create(['name' => 'Main Artist']);
        $externalArtist = Artist::create([
            'name' => 'External Collaborator',
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $release = Release::create([
            'artist_id' => $mainArtist->id,
            'title' => 'Release For Collaboration',
        ]);

        $track = Track::create([
            'release_id' => $release->id,
            'title' => 'Collaborative Track',
        ]);
        $track->artists()->sync([$mainArtist->id, $externalArtist->id]);

        $this->actingAs($admin)
            ->from(route('admin.artists.index'))
            ->delete(route('admin.artists.destroy', $externalArtist))
            ->assertRedirect(route('admin.artists.index'))
            ->assertSessionHasErrors('artist');

        $this->assertNull($externalArtist->fresh()?->deleted_at);
    }

    public function test_trashed_external_artist_is_not_recreated_by_sync(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $user = User::factory()->create([
            'name' => 'External Legal Name',
            'stage_name' => 'External Stage Name',
        ]);
        $user->assignRole($this->role('external_artist'));

        $artist = Artist::create([
            'name' => 'External Stage Name',
            'user_id' => $user->id,
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);
        $artist->delete();

        $this->actingAs($admin)
            ->get(route('admin.artists.index'))
            ->assertOk();

        $this->assertSame(1, Artist::withTrashed()->where('user_id', $user->id)->count());
        $this->assertSoftDeleted('artists', ['id' => $artist->id]);
        $this->assertSame(0, Artist::query()->where('user_id', $user->id)->count());
    }

    public function test_collaborator_force_delete_is_blocked_when_has_active_payments(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $collaborator = Collaborator::create([
            'account_holder' => 'Colaborador Bloqueado',
            'holder_id' => 'CC123',
            'account_type' => 'Ahorros',
            'bank' => 'Banco Test',
            'address' => 'Calle 1',
            'account_number' => '00012345',
            'country' => 'CO',
        ]);

        $event = Event::create([
            'title' => 'Evento Test Colaborador',
        ]);

        EventPayment::create([
            'event_id' => $event->id,
            'collaborator_id' => $collaborator->id,
            'payment_date' => now()->toDateString(),
            'amount_original' => 100,
            'currency' => 'USD',
            'exchange_rate_to_base' => 1,
            'amount_base' => 100,
            'payment_method' => 'transfer',
            'is_advance' => false,
        ]);

        $collaborator->delete();

        $this->actingAs($admin)
            ->from(route('admin.collaborators.trash'))
            ->delete(route('admin.collaborators.force-delete', $collaborator->id))
            ->assertRedirect(route('admin.collaborators.trash'))
            ->assertSessionHasErrors('collaborator');

        $this->assertNotNull(Collaborator::withTrashed()->find($collaborator->id)?->deleted_at);
    }

    private function role(string $name): Role
    {
        return Role::findOrCreate($name, 'web');
    }
}
