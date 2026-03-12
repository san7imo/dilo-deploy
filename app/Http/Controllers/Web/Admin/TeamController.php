<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collaborator;
use App\Models\EventPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $periodEnd = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $threeMonthsStart = now()->startOfMonth()->subMonths(2)->toDateString();
        $sixMonthsStart = now()->startOfMonth()->subMonths(5)->toDateString();
        $yearStart = now()->startOfYear()->toDateString();

        $roadManagerReceivedSubquery = function (string $fromDate) use ($periodEnd) {
            return EventPayment::query()
                ->selectRaw('COALESCE(SUM(amount_base), 0)')
                ->whereColumn('event_payments.created_by', 'users.id')
                ->whereDate('event_payments.payment_date', '>=', $fromDate)
                ->whereDate('event_payments.payment_date', '<=', $periodEnd);
        };

        $collaboratorReceivedSubquery = function (string $fromDate) use ($periodEnd) {
            return EventPayment::query()
                ->selectRaw('COALESCE(SUM(amount_base), 0)')
                ->whereColumn('event_payments.collaborator_id', 'collaborators.id')
                ->whereDate('event_payments.payment_date', '>=', $fromDate)
                ->whereDate('event_payments.payment_date', '<=', $periodEnd);
        };

        $roadManagers = User::role('roadmanager')
            ->select('id', 'name', 'email', 'phone', 'email_verified_at')
            ->selectSub($roadManagerReceivedSubquery($monthStart), 'received_month_usd')
            ->selectSub($roadManagerReceivedSubquery($threeMonthsStart), 'received_three_months_usd')
            ->selectSub($roadManagerReceivedSubquery($sixMonthsStart), 'received_six_months_usd')
            ->selectSub($roadManagerReceivedSubquery($yearStart), 'received_year_usd')
            ->orderBy('name')
            ->paginate(10, ['*'], 'road_page')
            ->withQueryString();

        $contentManagers = User::role('contentmanager')
            ->select('id', 'name', 'email', 'phone', 'email_verified_at')
            ->orderBy('name')
            ->paginate(10, ['id', 'name', 'email', 'phone', 'email_verified_at'], 'content_page')
            ->withQueryString();

        $collaborators = Collaborator::query()
            ->select('collaborators.*')
            ->selectSub($collaboratorReceivedSubquery($monthStart), 'received_month_usd')
            ->selectSub($collaboratorReceivedSubquery($threeMonthsStart), 'received_three_months_usd')
            ->selectSub($collaboratorReceivedSubquery($sixMonthsStart), 'received_six_months_usd')
            ->selectSub($collaboratorReceivedSubquery($yearStart), 'received_year_usd')
            ->orderBy('account_holder')
            ->paginate(10, ['*'], 'collaborator_page')
            ->withQueryString();

        return Inertia::render('Admin/Team/Index', [
            'roadManagers' => $roadManagers,
            'contentManagers' => $contentManagers,
            'collaborators' => $collaborators,
            'canManageRoadManagers' => (bool) $user?->hasRole('admin') || (bool) $user?->hasRole('contentmanager'),
            'canManageContentManagers' => (bool) $user?->hasRole('admin'),
            'canManageCollaborators' => (bool) $user?->hasRole('admin'),
        ]);
    }
}
