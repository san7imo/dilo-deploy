<?php

use Illuminate\Support\Facades\Route;

// ===============================================
// CONTROLADORES PÚBLICOS (solo visualización)
// ===============================================

use App\Http\Controllers\Web\Public\{
    ArtistController as PublicArtistController,
    EventController as PublicEventController,
    GenreController as PublicGenreController,
    ReleaseController as PublicReleaseController,
    TrackController as PublicTrackController,
    HomeController as PublicHomeController,
    SitemapController as PublicSitemapController
};

// Página principal (landing pública)
Route::get('/', [PublicHomeController::class, 'index'])
    ->name('public.home');

// --- Artistas ---
Route::prefix('artistas')->name('public.artists.')->group(function () {
    Route::get('/', [PublicArtistController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicArtistController::class, 'show'])->name('show');
});

// --- Eventos ---
Route::prefix('eventos')->name('public.events.')->group(function () {
    Route::get('/', [PublicEventController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicEventController::class, 'show'])->name('show');
});

// --- Géneros ---
Route::prefix('generos')->name('public.genres.')->group(function () {
    Route::get('/', [PublicGenreController::class, 'index'])->name('index');
    Route::get('/{id}', [PublicGenreController::class, 'show'])->name('show');
});

// --- Lanzamientos (Releases) ---
Route::prefix('releases')->name('public.releases.')->group(function () {
    Route::get('/', [PublicReleaseController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicReleaseController::class, 'show'])->name('show');
});

// --- Pistas (Tracks) ---
Route::prefix('tracks')->name('public.tracks.')->group(function () {
    Route::get('/', [PublicTrackController::class, 'index'])->name('index');
    Route::get('/{id}', [PublicTrackController::class, 'show'])->name('show');
});

// --- Sitemaps (SEO) ---
Route::get('/sitemap.xml', [PublicSitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-artists.xml', [PublicSitemapController::class, 'artists'])->name('sitemap.artists');
Route::get('/sitemap-releases.xml', [PublicSitemapController::class, 'releases'])->name('sitemap.releases');
Route::get('/sitemap-events.xml', [PublicSitemapController::class, 'events'])->name('sitemap.events');

// ===============================================
// PANEL DE ADMINISTRACIÓN
// ===============================================

use App\Http\Controllers\Web\Admin\{
    DashboardController,
    ArtistController as AdminArtistController,
    EventController as AdminEventController,
    GenreController as AdminGenreController,
    ReleaseController as AdminReleaseController,
    TrackController as AdminTrackController
};

// --- Dashboard principal (punto de entrada del panel) ---
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])
    ->get('/dashboard', function () {
        return inertia('Dashboard'); // resources/js/Pages/Dashboard.vue
    })
    ->name('admin.dashboard');

// --- Endpoint de datos del Dashboard ---
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])
    ->get('/admin/dashboard/data', [DashboardController::class, 'index'])
    ->name('admin.dashboard.data');

// --- Grupo de rutas protegidas (CRUDs del panel) ---
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // --- Artistas ---
        Route::resource('artists', AdminArtistController::class)->except(['show']);
        
        // Ruta especial para eliminar una imagen del artista
        Route::delete('artists/{artist}/image', [AdminArtistController::class, 'deleteImage'])
            ->name('artists.deleteImage');

        // --- Eventos ---
        Route::resource('events', AdminEventController::class)->except(['show']);

        // --- Géneros ---
        Route::resource('genres', AdminGenreController::class)->except(['show']);

        // --- Lanzamientos ---
        Route::resource('releases', AdminReleaseController::class)->except(['show']);

        // --- Pistas ---
        Route::resource('tracks', AdminTrackController::class)->except(['show']);
    });

// ===============================================
// AUTENTICACIÓN (Jetstream / Fortify)
// ===============================================
// Las rutas de autenticación son registradas automáticamente por Fortify
// Verifica que config/fortify.php tenga 'views' => true