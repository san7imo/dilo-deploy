<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArtistCatalogConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_convert_internal_artist_to_external_without_deleting_catalog(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $user = User::factory()->create([
            'name' => 'Legal Name',
            'stage_name' => 'Internal Artist',
        ]);
        $user->assignRole($this->role('artist'));

        $artist = Artist::create([
            'name' => 'Internal Artist',
            'user_id' => $user->id,
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);

        $release = Release::create([
            'artist_id' => $artist->id,
            'title' => 'Release Still Exists',
        ]);

        $track = Track::create([
            'release_id' => $release->id,
            'title' => 'Track Still Exists',
        ]);
        $track->artists()->sync([$artist->id]);

        $this->actingAs($admin)
            ->patch(route('admin.artists.convert-to-external', $artist))
            ->assertRedirect(route('admin.artists.index'));

        $artist->refresh();
        $user->refresh();

        $this->assertSame('external', $artist->artist_origin);
        $this->assertFalse($artist->has_public_profile);
        $this->assertTrue($user->hasRole('external_artist'));
        $this->assertFalse($user->hasRole('artist'));
        $this->assertDatabaseHas('releases', ['id' => $release->id]);
        $this->assertDatabaseHas('tracks', ['id' => $track->id]);
    }

    public function test_admin_can_convert_external_artist_to_internal(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole($this->role('admin'));

        $user = User::factory()->create([
            'name' => 'Legal Name',
            'stage_name' => 'External Artist',
        ]);
        $user->assignRole($this->role('external_artist'));

        $artist = Artist::create([
            'name' => 'External Artist',
            'user_id' => $user->id,
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.artists.convert-to-internal', $artist))
            ->assertRedirect(route('admin.artists.index'));

        $artist->refresh();
        $user->refresh();

        $this->assertSame('internal', $artist->artist_origin);
        $this->assertTrue($artist->has_public_profile);
        $this->assertTrue($user->hasRole('artist'));
        $this->assertFalse($user->hasRole('external_artist'));
    }

    private function role(string $name): Role
    {
        return Role::findOrCreate($name, 'web');
    }
}
