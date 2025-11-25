<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Models\{Artist, Release, Event};
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $ttl = now()->addMinutes(10); // cache 10 min

        // 🎵 Banner principal (solo artistas con banner_home_url)
        $banners = Cache::remember('home.banners', $ttl, function () {
            return Artist::query()
                ->whereNotNull('banner_home_url')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'name', 'slug', 'banner_home_url'])
                ->map(fn($a) => [
                    'id'    => $a->id,
                    'name'  => $a->name,
                    'slug'  => $a->slug,
                    'image' => $a->banner_home_url . '?tr=w-1600,h-700,fo-auto,q-85',
                ]);
        });

// 💿 Últimos lanzamientos (con artista)
$latestReleases = Cache::remember('home.releases', $ttl, function () {
    return Release::query()
        ->with('artist:id,name,slug')
        ->orderByDesc('release_date')
        ->limit(8)
        ->get([
            'id', 'title', 'slug', 'cover_url', 'artist_id',
            'spotify_url', 'youtube_url', 'apple_music_url',
            'deezer_url', 'amazon_music_url', 'soundcloud_url', 'tidal_url'
        ])
        ->map(fn($r) => [
            'id'     => $r->id,
            'title'  => $r->title,
            'slug'   => $r->slug,
            'cover'  => $r->cover_url
                ? $r->cover_url . '?tr=w-600,h-600,fo-auto,q-85'
                : null,
            'artist' => [
                'name' => $r->artist?->name,
                'slug' => $r->artist?->slug,
            ],
            'platforms' => collect([
                'spotify'       => $r->spotify_url,
                'youtube'       => $r->youtube_url,
                'apple_music'   => $r->apple_music_url,
                'deezer'        => $r->deezer_url,
                'amazon_music'  => $r->amazon_music_url,
                'soundcloud'    => $r->soundcloud_url,
                'tidal'         => $r->tidal_url,
            ])
                ->filter()
                ->map(fn($url, $key) => [
                    'name' => ucfirst(str_replace('_', ' ', $key)),
                    'key'  => $key,
                    'url'  => $url,
                ])
                ->values()
                ->toArray(),
        ]);
});

        // 👩‍🎤 Carrusel de artistas
        $artists = Cache::remember('home.artists', $ttl, function () {
            return Artist::query()
                ->whereNotNull('carousel_home_url')
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'slug', 'carousel_home_url'])
                ->map(fn($a) => [
                    'id'    => $a->id,
                    'name'  => $a->name,
                    'slug'  => $a->slug,
                    'image' => $a->carousel_home_url . '?tr=w-500,h-500,fo-auto,q-85',
                ]);
        });

        // 🎤 Eventos (próximos eventos)
        $events = Cache::remember('home.events', $ttl, function () {
            return Event::query()
                ->with('artists')
                ->whereDate('event_date', '>=', now())
                ->orderBy('event_date', 'asc')
                ->limit(6)
                ->get(['id', 'title', 'slug', 'poster_url', 'event_date', 'location'])
                ->map(fn($e) => [
                    'id'       => $e->id,
                    'title'    => $e->title,
                    'slug'     => $e->slug,
                    'poster'   => $e->poster_url ? $e->poster_url . '?tr=w-800,h-600,fo-auto,q-85' : null,
                    'event_date' => $e->event_date,
                    'location' => $e->location,
                    'artists'  => $e->artists,
                ]);
        });

        // 📞 Contacto (desde config/site.php)
        $contact = [
            'email'   => config('site.email', 'info@dilorecords.com'),
            'phone'   => config('site.phone', '+34 608 529 493'),
            'phone_2' => config('site.phone_2', '+34 622 957 414'),
            'address' => config('site.address', 'España'),
            'social'  => [
                'facebook'  => config('site.social.facebook', null),
                'instagram' => config('site.social.instagram', null),
                'tiktok'    => config('site.social.tiktok', null),
                'youtube'   => config('site.social.youtube', null),
            ],
        ];

        return Inertia::render('Public/Home', [
            'banners'        => $banners,
            'latestReleases' => $latestReleases,
            'artists'        => $artists,
            'events'         => $events,
            'contact'        => $contact,
        ]);
    }
}
