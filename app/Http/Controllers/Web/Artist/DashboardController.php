<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Datos de ejemplo; aquí luego puedes filtrar por el artista logueado
        return response()->json([
            'releases' => 0,
            'tracks' => 0,
            'events' => [],
        ]);
    }
}
