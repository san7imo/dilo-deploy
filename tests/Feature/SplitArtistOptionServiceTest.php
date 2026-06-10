<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use App\Services\SplitArtistOptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitArtistOptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_active_artists_for_each_split_category(): void
    {
        $internalArtist = Artist::query()->create([
            'name' => 'Internal Active',
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);

        $externalArtist = Artist::query()->create([
            'name' => 'External Active',
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $deletedInternal = Artist::query()->create([
            'name' => 'Internal Deleted',
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);
        $deletedInternal->delete();

        $deletedExternal = Artist::query()->create([
            'name' => 'External Deleted',
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);
        $deletedExternal->delete();

        $options = app(SplitArtistOptionService::class)->grouped();

        $internalIds = $options['internalArtists']->pluck('artist_id')->all();
        $externalIds = $options['externalArtists']->pluck('artist_id')->all();

        $this->assertSame([$internalArtist->id], $internalIds);
        $this->assertSame([$externalArtist->id], $externalIds);
        $this->assertNotContains($deletedInternal->id, $internalIds);
        $this->assertNotContains($deletedExternal->id, $externalIds);
    }

    public function test_artist_changed_category_only_appears_in_current_category(): void
    {
        $artist = Artist::query()->create([
            'name' => 'Changed Category',
            'artist_origin' => 'internal',
            'has_public_profile' => true,
        ]);

        $artist->update([
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $service = app(SplitArtistOptionService::class);

        $this->assertNotContains($artist->id, $service->internalArtists()->pluck('artist_id')->all());
        $this->assertContains($artist->id, $service->externalArtists()->pluck('artist_id')->all());
    }

    public function test_external_artist_options_include_account_status_and_user_details_when_available(): void
    {
        $user = User::factory()->create([
            'name' => 'Legal External',
            'stage_name' => 'Stage External',
            'email' => 'external@example.com',
        ]);

        $activeExternal = Artist::query()->create([
            'name' => 'External With Account',
            'user_id' => $user->id,
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $pendingExternal = Artist::query()->create([
            'name' => 'External Pending',
            'artist_origin' => 'external',
            'has_public_profile' => false,
        ]);

        $options = app(SplitArtistOptionService::class)
            ->externalArtists()
            ->keyBy('artist_id');

        $this->assertSame($user->id, $options[$activeExternal->id]['user_id']);
        $this->assertSame('external@example.com', $options[$activeExternal->id]['email']);
        $this->assertSame('Stage External', $options[$activeExternal->id]['stage_name']);
        $this->assertSame('active', $options[$activeExternal->id]['account_status']);

        $this->assertNull($options[$pendingExternal->id]['user_id']);
        $this->assertNull($options[$pendingExternal->id]['email']);
        $this->assertSame('pending', $options[$pendingExternal->id]['account_status']);
    }
}
