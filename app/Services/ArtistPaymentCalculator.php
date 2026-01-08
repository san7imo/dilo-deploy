<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ArtistEventExpense;

class ArtistPaymentCalculator
{
  
    const ARTIST_PERCENTAGE = 0.70;
    const ORGANIZATION_PERCENTAGE = 0.30;

    // Calcular el pago neto del artista
    public function calculateNetPayment(Event $event, int $artistId): array
    {

        // Obtener el valor total del evento
        $totalEventValue = $event->total_amount ?? 0;

        // Calcular el 70% del artista
        $artistGrossAmount = $totalEventValue * self::ARTIST_PERCENTAGE;

        // sumar los gastos personales aprobados del artista
        $personalExpenses = ArtistEventExpense::byEvent($event->id)
            ->byArtist($artistId)
            ->approved()
            ->sum('amount_base');


        // Calcular el pago neto del artista
        $netPayment = $artistGrossAmount - $personalExpenses;


        return [
            'total_event_value' => $totalEventValue,
            'organization_amount' => $totalEventValue * self::ORGANIZATION_PERCENTAGE,
            'artist_gross_amount' => $artistGrossAmount,
            'personal_expenses' => $personalExpenses,
            'net_payment' => $netPayment,
            'artist_percentage' => self::ARTIST_PERCENTAGE * 100,
        ];
    }

    //Obtener el resumen de gastos personales por categoría
    public function getExpensesByCategory(Event $event, int $artistId): array
    {
        return ArtistEventExpense::byEvent($event->id)
            ->byArtist($artistId)
            ->approved()
            ->selectRaw('category, SUM(amount_base) as total')
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->toArray();
    }

}
