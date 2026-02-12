<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Inertia\Inertia;

class EventController extends Controller
{
    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Mostrar lista de eventos (público)
     */
    public function index()
    {
        $upcomingEvents = $this->eventService->getUpcoming(10, 'upcoming_page');
        $pastEvents = $this->eventService->getPast(10, 'past_page');

        return Inertia::render('Public/Events/Index', [
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'banner' => [
                'title' => 'Únete a nuestros',
                'highlight' => 'EVENTOS',
                'cta' => 'Descubre más',
                'image' => asset('images/events-banner.webp'),
            ],
        ]);
    }

    /**
     * Mostrar detalle de un evento (por slug)
     */
    public function show(string $slug)
    {
        $event = $this->eventService->getBySlug($slug);

        if (!$event) {
            abort(404);
        }

        return Inertia::render('Public/Events/Show', [
            'event' => $event,
        ]);
    }
}
