<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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
Route::get('/', [PublicHomeController::class, 'index'])->name('public.home');

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

// --- Lanzamientos ---
Route::prefix('releases')->name('public.releases.')->group(function () {
    Route::get('/', [PublicReleaseController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicReleaseController::class, 'show'])->name('show');
});

// --- Pistas ---
Route::prefix('tracks')->name('public.tracks.')->group(function () {
    Route::get('/', [PublicTrackController::class, 'index'])->name('index');
    Route::get('/{id}', [PublicTrackController::class, 'show'])->name('show');
});

// --- Sitemaps ---
Route::get('/sitemap.xml', [PublicSitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-artists.xml', [PublicSitemapController::class, 'artists'])->name('sitemap.artists');
Route::get('/sitemap-releases.xml', [PublicSitemapController::class, 'releases'])->name('sitemap.releases');
Route::get('/sitemap-events.xml', [PublicSitemapController::class, 'events'])->name('sitemap.events');


// ===============================================
// PANEL PRIVADO (Dashboard por rol)
// ===============================================
Route::middleware(['auth:sanctum', 'verified'])
    ->get('/dashboard', function (Request $request) {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return inertia('Dashboard');
        }

        if ($user->hasRole('artist')) {
            return inertia('Artist/Dashboard');
        }

        if ($user->hasRole('roadmanager')) {
            return redirect()->route('admin.events.index');
        }

        abort(403);
    })
    ->name('admin.dashboard');


// ===============================================
// PANEL DE ADMINISTRACIÓN
// ===============================================
use App\Http\Controllers\Web\Admin\{
    DashboardController,
    ArtistController as AdminArtistController,
    EventController as AdminEventController,
    GenreController as AdminGenreController,
    ReleaseController as AdminReleaseController,
    TrackController as AdminTrackController,
    EventFinanceController,
    RoadManagerController
};

use App\Http\Controllers\Web\EventPaymentController;
use App\Http\Controllers\Web\EventExpenseController;

// Endpoint de datos del Dashboard Admin
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])
    ->get('/admin/dashboard/data', [DashboardController::class, 'index'])
    ->name('admin.dashboard.data');

Route::middleware(['auth:sanctum', 'verified', 'role:admin|roadmanager'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // ✅ FINANZAS POR EVENTO (ADMIN)  -> admin.events.finance
        Route::get('events', [AdminEventController::class, 'index'])
            ->name('events.index');

        Route::get('events/{event}/finance', [EventFinanceController::class, 'show'])
            ->name('events.finance');

        Route::patch('events/{event}/roadmanager-payment', [EventFinanceController::class, 'confirmRoadManagerPayment'])
            ->name('events.roadmanager-payment.update');

        // ✅ PAGOS (solo crear)
        Route::post('events/{event}/payments', [EventPaymentController::class, 'store'])
            ->name('events.payments.store');

        // ✅ GASTOS (solo crear)
        Route::post('events/{event}/expenses', [EventExpenseController::class, 'store'])
            ->name('events.expenses.store');
    });

Route::middleware(['auth:sanctum', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // --- Artistas ---
        Route::resource('artists', AdminArtistController::class)->except(['show']);

        Route::delete('artists/{artist}/image', [AdminArtistController::class, 'deleteImage'])
            ->name('artists.deleteImage');

        // --- Road managers ---
        Route::resource('roadmanagers', RoadManagerController::class)->except(['show']);

        // --- Eventos ---
        Route::resource('events', AdminEventController::class)->except(['show', 'index']);

        Route::patch('events/{event}/payment-status', [EventFinanceController::class, 'updatePaymentStatus'])
            ->name('events.payment-status.update');

        Route::patch('events/{event}/details', [EventFinanceController::class, 'updateEventDetails'])
            ->name('events.details.update');

        Route::put('event-payments/{payment}', [EventPaymentController::class, 'update'])
            ->name('events.payments.update');

        Route::delete('event-payments/{payment}', [EventPaymentController::class, 'destroy'])
            ->name('events.payments.destroy');

        Route::put('event-expenses/{expense}', [EventExpenseController::class, 'update'])
            ->name('events.expenses.update');

        Route::delete('event-expenses/{expense}', [EventExpenseController::class, 'destroy'])
            ->name('events.expenses.destroy');

        Route::put('events/{event}/expenses-sync', [EventFinanceController::class, 'syncExpenses'])
            ->name('events.expenses.sync');

        // --- Géneros ---
        Route::resource('genres', AdminGenreController::class)->except(['show']);

        // --- Lanzamientos ---
        Route::resource('releases', AdminReleaseController::class)->except(['show']);

        // --- Pistas ---
        Route::resource('tracks', AdminTrackController::class)->except(['show']);
    });


// ===============================================
// RUTAS DE ARTISTAS (privadas)
// ===============================================
use App\Http\Controllers\Web\Artist\ProfileController as ArtistProfileController;
use App\Http\Controllers\Web\Artist\EventController as ArtistEventController;
use App\Http\Controllers\Web\Artist\DashboardController as ArtistDashboardApiController;
use App\Http\Controllers\Web\Artist\FinanceController as ArtistFinanceController;

Route::middleware(['auth:sanctum', 'verified', 'role:artist'])
    ->prefix('artist')
    ->name('artist.')
    ->group(function () {

        Route::get('/dashboard/data', [ArtistDashboardApiController::class, 'index'])
            ->name('dashboard.data');

        // Perfil
    Route::get('/profile/data', [ArtistProfileController::class, 'showData'])->name('profile.data');
    Route::get('/profile', [ArtistProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ArtistProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ArtistProfileController::class, 'update'])->name('profile.update');

        // Finanzas
        Route::get('/finances', [ArtistFinanceController::class, 'index'])->name('finances.index');

        // Eventos
        Route::get('/events', [ArtistEventController::class, 'index'])->name('events.index');
        Route::get('/events/{id}', [ArtistEventController::class, 'show'])->name('events.show');
    });


// ===============================================
// AUTENTICACIÓN (Jetstream / Fortify)
// ===============================================
// Las rutas de autenticación son registradas automáticamente por Fortify
