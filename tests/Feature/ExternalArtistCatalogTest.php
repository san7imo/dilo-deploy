<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\ExternalArtistInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExternalArtistCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_inviting_external_artist_creates_catalog_artist_without_public_profile(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $this->actingAs($admin)
            ->post(route('admin.artists.external-invitations.store'), [
                'name' => 'Artista Invitado',
                'email' => 'externo@example.com',
            ])
            ->assertRedirect(route('admin.artists.index'));

        $artist = Artist::query()->where('name', 'Artista Invitado')->first();

        $this->assertNotNull($artist);
        $this->assertSame('external', $artist->artist_origin);
        $this->assertFalse((bool) $artist->has_public_profile);
        $this->assertNull($artist->user_id);

        $invitation = ExternalArtistInvitation::query()
            ->where('email', 'externo@example.com')
            ->first();

        $this->assertNotNull($invitation);
        $this->assertSame($artist->id, data_get($invitation->metadata, 'artist_id'));
    }

    public function test_accepting_external_artist_invitation_links_external_catalog_artist(): void
    {
        $artist = Artist::query()->create([
            'name' => 'Pendiente Externo',
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $token = 'external-token';
        $invitation = ExternalArtistInvitation::query()->create([
            'email' => 'externo@example.com',
            'token_hash' => hash('sha256', $token),
            'invitee_name' => 'Pendiente Externo',
            'expires_at' => now()->addDay(),
            'metadata' => [
                'artist_id' => $artist->id,
            ],
        ]);

        $this->post(route('external-artists.invitations.accept', $token), [
            'name' => 'Nombre Legal',
            'stage_name' => 'Nombre Artistico',
            'identification_type' => 'passport',
            'identification_number' => 'AB123456',
            'phone' => '+57 300 000 0000',
            'additional_information' => 'Pago por transferencia.',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $user = User::query()->where('email', 'externo@example.com')->firstOrFail();
        $artist->refresh();
        $invitation->refresh();

        $this->assertTrue($user->hasRole('external_artist'));
        $this->assertSame($user->id, $artist->user_id);
        $this->assertSame('Nombre Artistico', $artist->name);
        $this->assertSame('external', $artist->artist_origin);
        $this->assertFalse((bool) $artist->has_public_profile);
        $this->assertNotNull($invitation->accepted_at);
        $this->assertSame($user->id, $invitation->accepted_user_id);
    }

    public function test_public_artist_pages_exclude_external_catalog_artists(): void
    {
        Artist::query()->create([
            'name' => 'Artista Publico',
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);

        $externalArtist = Artist::query()->create([
            'name' => 'Artista Externo',
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $this->get(route('public.artists.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Artists/Index')
                ->has('artists.data', 1)
                ->where('artists.data.0.name', 'Artista Publico')
            );

        $this->get(route('public.artists.show', $externalArtist->slug))
            ->assertNotFound();
    }

    public function test_release_and_track_forms_include_external_users_synced_to_catalog(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $externalUser = User::factory()->create([
            'name' => 'Nombre Legal Externo',
            'stage_name' => 'Alias Externo',
            'email' => 'legacy.external@example.com',
        ]);
        $externalUser->assignRole($this->role('external_artist'));

        $this->actingAs($admin)
            ->get(route('admin.releases.create'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Releases/Create')
                ->has('artists', fn ($artists) => $artists
                    ->where('0.name', 'Alias Externo')
                    ->where('0.artist_origin', 'external')
                    ->etc()
                )
            );

        $this->actingAs($admin)
            ->get(route('admin.tracks.create'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Tracks/Create')
                ->has('artists', fn ($artists) => $artists
                    ->where('0.name', 'Alias Externo')
                    ->where('0.artist_origin', 'external')
                    ->etc()
                )
            );

        $artist = Artist::query()->where('user_id', $externalUser->id)->first();
        $this->assertNotNull($artist);
        $this->assertSame('external', $artist->artist_origin);
        $this->assertFalse((bool) $artist->has_public_profile);
    }

    private function role(string $name): Role
    {
        return Role::findOrCreate($name, 'web');
    }
}
