<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Models\{Artist, Release, Event};
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generar sitemap.xml principal
     */
    public function index(): Response
    {
        $urls = [
            [
                'url' => route('public.home'),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'url' => route('public.artists.index'),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'url' => route('public.releases.index'),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'url' => route('public.events.index'),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'url' => route('public.genres.index'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
        ];

        return response()->view('sitemaps.sitemap-index', [
            'urls' => $urls,
        ], Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Generar sitemap de artistas
     */
    public function artists(): Response
    {
        $artists = Artist::query()
            ->select('id', 'slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $urls = $artists->map(function (Artist $artist) {
            return [
                'url' => route('public.artists.show', $artist->slug),
                'lastmod' => $artist->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        })->toArray();

        return response()->view('sitemaps.sitemap', [
            'urls' => $urls,
        ], Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Generar sitemap de releases
     */
    public function releases(): Response
    {
        $releases = Release::query()
            ->select('id', 'slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $urls = $releases->map(function (Release $release) {
            return [
                'url' => route('public.releases.show', $release->slug),
                'lastmod' => $release->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        })->toArray();

        return response()->view('sitemaps.sitemap', [
            'urls' => $urls,
        ], Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Generar sitemap de eventos
     */
    public function events(): Response
    {
        $events = Event::query()
            ->select('id', 'slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $urls = $events->map(function (Event $event) {
            return [
                'url' => route('public.events.show', $event->slug),
                'lastmod' => $event->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        })->toArray();

        return response()->view('sitemaps.sitemap', [
            'urls' => $urls,
        ], Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
