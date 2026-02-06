<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Event;
use App\Models\Genre;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class DevSampleSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        $genreNames = [
            'Reggaeton', 'Trap', 'Pop', 'Rock', 'Hip Hop', 'Afrobeats', 'Electrónica', 'Salsa', 'Bachata', 'R&B'
        ];

        $genres = collect($genreNames)->map(function ($name) {
            return Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        });

        $artists = collect();
        for ($i = 0; $i < 25; $i++) {
            $name = $faker->name();
            $attempts = 0;
            while (Artist::where('name', $name)->exists() && $attempts < 5) {
                $name = $faker->name();
                $attempts++;
            }
            if (Artist::where('name', $name)->exists()) {
                $name = $name . ' ' . $faker->unique()->numberBetween(1000, 9999);
            }
            $artists->push(Artist::create([
                'name' => $name,
                'slug' => Str::slug($name . '-' . $faker->unique()->numberBetween(1, 9999)),
                'bio' => $faker->sentence(18),
                'country' => $faker->country(),
                'genre_id' => $genres->random()->id,
            ]));
        }

        $releaseTypes = ['single', 'ep', 'album', 'mixtape'];
        $releases = collect();
        $trackTitles = collect();

        for ($i = 0; $i < 40; $i++) {
            $artist = $artists->random();
            $type = $faker->randomElement($releaseTypes);
            $title = $faker->unique()->sentence(3);
            $release = Release::create([
                'artist_id' => $artist->id,
                'title' => $title,
                'slug' => Str::slug($title . '-' . $faker->unique()->numberBetween(1, 9999)),
                'release_date' => $faker->dateTimeBetween('-18 months', '+3 months')->format('Y-m-d'),
                'type' => $type,
                'description' => $faker->sentence(12),
                'spotify_url' => $faker->url(),
                'youtube_url' => $faker->url(),
                'apple_music_url' => $faker->url(),
                'deezer_url' => $faker->url(),
                'amazon_music_url' => $faker->url(),
                'soundcloud_url' => $faker->url(),
                'tidal_url' => $faker->url(),
            ]);

            $releases->push($release);

            $trackCount = $type === 'single' ? 1 : $faker->numberBetween(3, 8);
            for ($t = 1; $t <= $trackCount; $t++) {
                $trackTitle = $type === 'single'
                    ? $release->title
                    : $faker->unique()->sentence(2);

                while ($trackTitles->contains($trackTitle)) {
                    $trackTitle = $faker->unique()->sentence(2);
                }
                $trackTitles->push($trackTitle);

                $track = Track::create([
                    'release_id' => $release->id,
                    'title' => $trackTitle,
                    'track_number' => $t,
                    'duration' => $faker->randomElement(['2:45', '3:12', '3:40', '4:05']),
                    'spotify_url' => $type === 'single' ? $release->spotify_url : $faker->url(),
                    'youtube_url' => $type === 'single' ? $release->youtube_url : $faker->url(),
                    'apple_music_url' => $type === 'single' ? $release->apple_music_url : $faker->url(),
                    'deezer_url' => $type === 'single' ? $release->deezer_url : $faker->url(),
                    'amazon_music_url' => $type === 'single' ? $release->amazon_music_url : $faker->url(),
                    'soundcloud_url' => $type === 'single' ? $release->soundcloud_url : $faker->url(),
                    'tidal_url' => $type === 'single' ? $release->tidal_url : $faker->url(),
                ]);

                $collabs = $artists->random($faker->numberBetween(0, 2))->pluck('id')->all();
                $track->artists()->sync(array_values(array_unique(array_merge([$artist->id], $collabs))));
            }
        }

        for ($i = 0; $i < 30; $i++) {
            $mainArtist = $artists->random();
            $status = $faker->randomElement(['pendiente', 'pagado']);
            $event = Event::create([
                'title' => $faker->sentence(3),
                'description' => $faker->sentence(15),
                'location' => $faker->city() . ', ' . $faker->country(),
                'event_date' => $faker->dateTimeBetween('-2 months', '+6 months')->format('Y-m-d'),
                'main_artist_id' => $mainArtist->id,
                'status' => $status,
                'is_paid' => $status === 'pagado',
                'event_type' => $faker->randomElement(['show', 'festival', 'gira']),
                'city' => $faker->city(),
                'country' => $faker->country(),
            ]);

            $guestArtists = $artists->random($faker->numberBetween(0, 2))->pluck('id')->all();
            $event->artists()->sync(array_values(array_unique(array_merge([$mainArtist->id], $guestArtists))));
        }
    }
}
