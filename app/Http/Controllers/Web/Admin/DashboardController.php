<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Release;
use App\Models\Track;
use App\Models\Genre;
use App\Models\Event;

class DashboardController extends Controller
{
    /**
     * Retorna los datos principales del panel administrativo.
     * Estos datos alimentan el Dashboard de Dilo Records.
     */
    public function index()
    {
        return response()->json([
            'artists'  => Artist::count(),
            'releases' => Release::count(),
            'tracks'   => Track::count(),
            'genres'   => Genre::count(),
            'events'   => Event::latest()->take(5)->get(['id', 'title', 'event_date', 'location']),
        ]);
    }
}
