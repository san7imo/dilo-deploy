<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Web\ExternalArtistInvitationController;

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
    SitemapController as PublicSitemapController,
    ContactController as PublicContactController
};

// Página principal (landing pública)
Route::get('/', [PublicHomeController::class, 'index'])->name('public.home');

// Contacto
Route::get('/contacto', [PublicContactController::class, 'show'])->name('public.contact');
Route::post('/contacto', [PublicContactController::class, 'submit'])->name('public.contact.submit');

// Secciones institucionales (públicas)
Route::get('/estudio', fn() => inertia('Public/SectionPage', [
    'title' => 'Estudio',
    'description' => 'Descubre los servicios, espacios y procesos creativos de Dilo Records Studio.',
]))->name('public.studio');

Route::get('/editorial', fn() => inertia('Public/SectionPage', [
    'title' => 'Editorial',
    'description' => 'Conoce nuestra línea editorial, representación y gestión de derechos.',
]))->name('public.editorial');

Route::get('/tienda', fn() => inertia('Public/SectionPage', [
    'title' => 'Tienda',
    'description' => 'Muy pronto encontrarás merchandising y productos oficiales de Dilo Records.',
]))->name('public.store');

Route::get('/noticias', fn() => inertia('Public/SectionPage', [
    'title' => 'Noticias',
    'description' => 'Entérate de lanzamientos, anuncios y novedades de nuestra comunidad.',
]))->name('public.news');

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

Route::prefix('invitaciones/artistas')->name('external-artists.invitations.')->group(function () {
    Route::get('/{token}', [ExternalArtistInvitationController::class, 'show'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('show');
    Route::post('/{token}', [ExternalArtistInvitationController::class, 'accept'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('accept');
});


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

        if ($user->hasRole('external_artist')) {
            return redirect()->route('artist.tracks.index');
        }

        if ($user->hasRole('contentmanager')) {
            return inertia('Dashboard');
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
    AuditLogController,
    DashboardController,
    ArtistController as AdminArtistController,
    EventController as AdminEventController,
    GenreController as AdminGenreController,
    ReleaseController as AdminReleaseController,
    CompositionController,
    TrackCompositionController,
    CompositionRoyaltyStatementController,
    CompositionSplitAgreementController,
    RoyaltyDashboardController,
    RoyaltyPayoutRequestController as AdminRoyaltyPayoutRequestController,
    RoyaltyStatementController,
    TrackSplitAgreementController,
    TrackController as AdminTrackController,
    EventFinanceController,
    OrganizerController,
    PayrollPaymentController,
    RoadManagerController,
    ContentManagerController,
    CollaboratorController,
    TeamController,
    WorkerController
};

use App\Http\Controllers\Web\EventPaymentController;
use App\Http\Controllers\Web\EventExpenseController;
use App\Http\Controllers\Web\EventPersonalExpenseController;

// Endpoint de datos del Dashboard Admin
Route::middleware(['auth:sanctum', 'verified', 'role:admin|contentmanager'])
    ->get('/admin/dashboard/data', [DashboardController::class, 'index'])
    ->name('admin.dashboard.data');

Route::middleware(['auth:sanctum', 'verified', 'role:admin|roadmanager|contentmanager'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('events', [AdminEventController::class, 'index'])
            ->name('events.index');
    });

Route::middleware(['auth:sanctum', 'verified', 'role:admin|roadmanager'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // ✅ FINANZAS POR EVENTO (ADMIN/ROADMANAGER)
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

Route::middleware(['auth:sanctum', 'verified', 'role:admin|contentmanager'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Equipo de trabajo
        Route::get('team', [TeamController::class, 'index'])
            ->name('team.index');

        // Trabajadores / Nómina
        Route::get('workers/trash', [WorkerController::class, 'trash'])
            ->name('workers.trash');
        Route::patch('workers/{workerId}/restore', [WorkerController::class, 'restore'])
            ->whereNumber('workerId')
            ->name('workers.restore');
        Route::delete('workers/{workerId}/force', [WorkerController::class, 'forceDelete'])
            ->whereNumber('workerId')
            ->name('workers.force-delete');
        Route::get('workers/{worker}/payroll', [WorkerController::class, 'payroll'])
            ->name('workers.payroll');
        Route::post('workers/{worker}/payroll-payments', [PayrollPaymentController::class, 'store'])
            ->name('workers.payroll-payments.store');
        Route::put('payroll-payments/{payrollPayment}', [PayrollPaymentController::class, 'update'])
            ->name('payroll-payments.update');
        Route::delete('payroll-payments/{payrollPayment}', [PayrollPaymentController::class, 'destroy'])
            ->name('payroll-payments.destroy');
        Route::resource('workers', WorkerController::class)->except(['show']);

        // --- Artistas ---
        Route::get('artists/trash', [AdminArtistController::class, 'trash'])
            ->name('artists.trash');
        Route::patch('artists/{artistId}/restore', [AdminArtistController::class, 'restore'])
            ->whereNumber('artistId')
            ->name('artists.restore');
        Route::delete('artists/{artistId}/force', [AdminArtistController::class, 'forceDelete'])
            ->whereNumber('artistId')
            ->name('artists.force-delete');
        Route::post('artists/external-invitations', [AdminArtistController::class, 'inviteExternalArtist'])
            ->name('artists.external-invitations.store');
        Route::resource('artists', AdminArtistController::class)->except(['show']);

        Route::delete('artists/{artist}/image', [AdminArtistController::class, 'deleteImage'])
            ->name('artists.deleteImage');

        // --- Road managers ---
        Route::get('roadmanagers/trash', [RoadManagerController::class, 'trash'])
            ->name('roadmanagers.trash');
        Route::patch('roadmanagers/{roadmanagerId}/restore', [RoadManagerController::class, 'restore'])
            ->whereNumber('roadmanagerId')
            ->name('roadmanagers.restore');
        Route::delete('roadmanagers/{roadmanagerId}/force', [RoadManagerController::class, 'forceDelete'])
            ->whereNumber('roadmanagerId')
            ->name('roadmanagers.force-delete');
        Route::resource('roadmanagers', RoadManagerController::class)->except(['show']);

        // --- Géneros ---
        Route::get('genres/trash', [AdminGenreController::class, 'trash'])
            ->name('genres.trash');
        Route::patch('genres/{genreId}/restore', [AdminGenreController::class, 'restore'])
            ->whereNumber('genreId')
            ->name('genres.restore');
        Route::delete('genres/{genreId}/force', [AdminGenreController::class, 'forceDelete'])
            ->whereNumber('genreId')
            ->name('genres.force-delete');
        Route::resource('genres', AdminGenreController::class)->except(['show']);

        // --- Lanzamientos ---
        Route::get('releases/trash', [AdminReleaseController::class, 'trash'])
            ->name('releases.trash');
        Route::patch('releases/{releaseId}/restore', [AdminReleaseController::class, 'restore'])
            ->whereNumber('releaseId')
            ->name('releases.restore');
        Route::delete('releases/{releaseId}/force', [AdminReleaseController::class, 'forceDelete'])
            ->whereNumber('releaseId')
            ->name('releases.force-delete');
        Route::resource('releases', AdminReleaseController::class)->except(['show']);

        // --- Pistas ---
        Route::get('tracks/trash', [AdminTrackController::class, 'trash'])
            ->name('tracks.trash');
        Route::patch('tracks/{trackId}/restore', [AdminTrackController::class, 'restore'])
            ->whereNumber('trackId')
            ->name('tracks.restore');
        Route::delete('tracks/{trackId}/force', [AdminTrackController::class, 'forceDelete'])
            ->whereNumber('trackId')
            ->name('tracks.force-delete');
        Route::resource('tracks', AdminTrackController::class)->except(['show']);

        // --- Composiciones ---
        Route::get('compositions/trash', [CompositionController::class, 'trash'])
            ->name('compositions.trash');
        Route::patch('compositions/{compositionId}/restore', [CompositionController::class, 'restore'])
            ->whereNumber('compositionId')
            ->name('compositions.restore');
        Route::delete('compositions/{compositionId}/force', [CompositionController::class, 'forceDelete'])
            ->whereNumber('compositionId')
            ->name('compositions.force-delete');
        Route::resource('compositions', CompositionController::class)->except(['show']);
        Route::get('compositions/{composition}/splits', [CompositionSplitAgreementController::class, 'index'])
            ->name('compositions.splits.index');
        Route::get('compositions/{composition}/splits/create', [CompositionSplitAgreementController::class, 'create'])
            ->name('compositions.splits.create');
        Route::post('compositions/{composition}/splits', [CompositionSplitAgreementController::class, 'store'])
            ->name('compositions.splits.store');
        Route::get('compositions/{composition}/splits/{agreement}/download', [CompositionSplitAgreementController::class, 'download'])
            ->name('compositions.splits.download');

        // --- Empresarios / Organizadores ---
        Route::get('organizers/trash', [OrganizerController::class, 'trash'])
            ->name('organizers.trash');
        Route::patch('organizers/{organizerId}/restore', [OrganizerController::class, 'restore'])
            ->whereNumber('organizerId')
            ->name('organizers.restore');
        Route::delete('organizers/{organizerId}/force', [OrganizerController::class, 'forceDelete'])
            ->whereNumber('organizerId')
            ->name('organizers.force-delete');
        Route::resource('organizers', OrganizerController::class)->except(['show']);
    });

Route::middleware(['auth:sanctum', 'verified', 'role:admin|contentmanager|roadmanager'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // --- Eventos ---
        Route::get('events/trash', [AdminEventController::class, 'trash'])
            ->middleware('role:admin')
            ->name('events.trash');
        Route::patch('events/{eventId}/restore', [AdminEventController::class, 'restore'])
            ->whereNumber('eventId')
            ->middleware('role:admin')
            ->name('events.restore');
        Route::delete('events/{eventId}/force', [AdminEventController::class, 'forceDelete'])
            ->whereNumber('eventId')
            ->middleware('role:admin')
            ->name('events.force-delete');
        Route::resource('events', AdminEventController::class)->except(['show', 'index']);
    });

Route::middleware(['auth:sanctum', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('royalties', [RoyaltyDashboardController::class, 'index'])
            ->name('royalties.dashboard');
        Route::get('royalties/payout-requests', [AdminRoyaltyPayoutRequestController::class, 'index'])
            ->name('royalties.payout-requests.index');
        Route::get('royalties/payout-requests/{payoutRequest}', [AdminRoyaltyPayoutRequestController::class, 'show'])
            ->whereNumber('payoutRequest')
            ->name('royalties.payout-requests.show');
        Route::post('royalties/payout-requests/{payoutRequest}/payments', [AdminRoyaltyPayoutRequestController::class, 'storePayment'])
            ->whereNumber('payoutRequest')
            ->name('royalties.payout-requests.payments.store');

        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->whereNumber('auditLog')
            ->name('audit-logs.show');

        // --- Royalties / Statements ---
        Route::get('royalties/statements', [RoyaltyStatementController::class, 'index'])
            ->name('royalties.statements.index');
        Route::get('royalties/statements/create', [RoyaltyStatementController::class, 'create'])
            ->name('royalties.statements.create');
        Route::get('royalties/statements/trash', [RoyaltyStatementController::class, 'trash'])
            ->name('royalties.statements.trash');
        Route::patch('royalties/statements/{statement}/lines/{line}/match', [RoyaltyStatementController::class, 'updateLineMatch'])
            ->whereNumber('statement')
            ->whereNumber('line')
            ->name('royalties.statements.lines.match');
        Route::get('royalties/statements/{statement}/download', [RoyaltyStatementController::class, 'download'])
            ->whereNumber('statement')
            ->name('royalties.statements.download');
        Route::get('royalties/statements/{statement}', [RoyaltyStatementController::class, 'show'])
            ->whereNumber('statement')
            ->name('royalties.statements.show');
        Route::post('royalties/statements', [RoyaltyStatementController::class, 'store'])
            ->name('royalties.statements.store');
        Route::delete('royalties/statements/{statement}', [RoyaltyStatementController::class, 'destroy'])
            ->whereNumber('statement')
            ->name('royalties.statements.destroy');
        Route::post('royalties/statements/{statement}/process', [RoyaltyStatementController::class, 'process'])
            ->whereNumber('statement')
            ->name('royalties.statements.process');
        Route::patch('royalties/statements/{statementId}/restore', [RoyaltyStatementController::class, 'restore'])
            ->whereNumber('statementId')
            ->name('royalties.statements.restore');
        Route::delete('royalties/statements/{statementId}/force', [RoyaltyStatementController::class, 'forceDelete'])
            ->whereNumber('statementId')
            ->name('royalties.statements.force-delete');

        // --- Composition Royalties / Statements ---
        Route::get('royalties/composition-statements', [CompositionRoyaltyStatementController::class, 'index'])
            ->name('royalties.composition-statements.index');
        Route::get('royalties/composition-statements/create', [CompositionRoyaltyStatementController::class, 'create'])
            ->name('royalties.composition-statements.create');
        Route::get('royalties/composition-statements/template', [CompositionRoyaltyStatementController::class, 'template'])
            ->name('royalties.composition-statements.template');
        Route::post('royalties/composition-statements', [CompositionRoyaltyStatementController::class, 'store'])
            ->name('royalties.composition-statements.store');
        Route::post('royalties/composition-statements/{statement}/process', [CompositionRoyaltyStatementController::class, 'process'])
            ->whereNumber('statement')
            ->name('royalties.composition-statements.process');
        Route::get('royalties/composition-statements/{statement}/download', [CompositionRoyaltyStatementController::class, 'download'])
            ->whereNumber('statement')
            ->name('royalties.composition-statements.download');
        Route::get('royalties/composition-statements/{statement}', [CompositionRoyaltyStatementController::class, 'show'])
            ->whereNumber('statement')
            ->name('royalties.composition-statements.show');

        // --- Tracks / Splits ---
        Route::get('tracks/{track}/splits', [TrackSplitAgreementController::class, 'index'])
            ->name('tracks.splits.index');
        Route::get('tracks/{track}/splits/create', [TrackSplitAgreementController::class, 'create'])
            ->name('tracks.splits.create');
        Route::post('tracks/{track}/splits', [TrackSplitAgreementController::class, 'store'])
            ->name('tracks.splits.store');
        Route::get('tracks/{track}/splits/{agreement}/download', [TrackSplitAgreementController::class, 'download'])
            ->name('tracks.splits.download');

        // --- Tracks / Composiciones + Splits de composición ---
        Route::get('tracks/{track}/compositions', [TrackCompositionController::class, 'index'])
            ->name('tracks.compositions.index');
        Route::post('tracks/{track}/compositions', [TrackCompositionController::class, 'store'])
            ->name('tracks.compositions.store');
        Route::post('tracks/{track}/compositions/attach', [TrackCompositionController::class, 'attach'])
            ->name('tracks.compositions.attach');
        Route::delete('tracks/{track}/compositions/{composition}', [TrackCompositionController::class, 'detach'])
            ->name('tracks.compositions.detach');

        Route::get('content-managers/trash', [ContentManagerController::class, 'trash'])
            ->name('content-managers.trash');
        Route::patch('content-managers/{contentManagerId}/restore', [ContentManagerController::class, 'restore'])
            ->whereNumber('contentManagerId')
            ->name('content-managers.restore');
        Route::delete('content-managers/{contentManagerId}/force', [ContentManagerController::class, 'forceDelete'])
            ->whereNumber('contentManagerId')
            ->name('content-managers.force-delete');
        Route::resource('content-managers', ContentManagerController::class)->except(['show']);

        Route::get('collaborators/trash', [CollaboratorController::class, 'trash'])
            ->name('collaborators.trash');
        Route::patch('collaborators/{collaboratorId}/restore', [CollaboratorController::class, 'restore'])
            ->whereNumber('collaboratorId')
            ->name('collaborators.restore');
        Route::delete('collaborators/{collaboratorId}/force', [CollaboratorController::class, 'forceDelete'])
            ->whereNumber('collaboratorId')
            ->name('collaborators.force-delete');
        Route::resource('collaborators', CollaboratorController::class)->except(['show', 'index']);

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

        Route::post('events/{event}/personal-expenses', [EventPersonalExpenseController::class, 'store'])
            ->name('events.personal-expenses.store');

        Route::put('event-personal-expenses/{personalExpense}', [EventPersonalExpenseController::class, 'update'])
            ->name('events.personal-expenses.update');

        Route::delete('event-personal-expenses/{personalExpense}', [EventPersonalExpenseController::class, 'destroy'])
            ->name('events.personal-expenses.destroy');

        Route::put('events/{event}/expenses-sync', [EventFinanceController::class, 'syncExpenses'])
            ->name('events.expenses.sync');
    });


// ===============================================
// RUTAS DE ARTISTAS (privadas)
// ===============================================
use App\Http\Controllers\Web\Artist\ProfileController as ArtistProfileController;
use App\Http\Controllers\Web\Artist\EventController as ArtistEventController;
use App\Http\Controllers\Web\Artist\DashboardController as ArtistDashboardApiController;
use App\Http\Controllers\Web\Artist\FinanceController as ArtistFinanceController;
use App\Http\Controllers\Web\Artist\CompositionController as ArtistCompositionController;
use App\Http\Controllers\Web\Artist\RoyaltyPayoutRequestController as ArtistRoyaltyPayoutRequestController;
use App\Http\Controllers\Web\Artist\TrackController as ArtistTrackController;

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

Route::middleware(['auth:sanctum', 'verified', 'role:artist|external_artist'])
    ->prefix('artist')
    ->name('artist.')
    ->group(function () {
        Route::post('/royalties/payout-requests', [ArtistRoyaltyPayoutRequestController::class, 'store'])
            ->name('royalties.payout-requests.store');

        Route::get('/compositions', [ArtistCompositionController::class, 'index'])
            ->name('compositions.index');
        Route::get('/compositions/{composition}/royalties', [ArtistCompositionController::class, 'royalties'])
            ->name('compositions.royalties.index');
        Route::get('/compositions/{composition}/royalties/{statement}', [ArtistCompositionController::class, 'royaltyDetail'])
            ->name('compositions.royalties.detail');

        Route::get('/tracks', [ArtistTrackController::class, 'index'])->name('tracks.index');
        Route::get('/tracks/{track}/royalties', [ArtistTrackController::class, 'royalties'])
            ->name('tracks.royalties.index');
        Route::get('/tracks/{track}/royalties/{statement}', [ArtistTrackController::class, 'royaltyDetail'])
            ->name('tracks.royalties.detail');
    });


// ===============================================
// AUTENTICACIÓN (Jetstream / Fortify)
// ===============================================
// Las rutas de autenticación son registradas automáticamente por Fortify
