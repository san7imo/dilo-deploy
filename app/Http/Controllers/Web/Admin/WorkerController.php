<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Models\PayrollPayment;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkerController extends Controller
{
    private const POSITION_OPTIONS = [
        'Diseñador',
        'Jefe de prensa',
        'Desarrollador web',
        'Filmmaker',
        'Productor',
        'Dpto. Legal',
        'Social media',
        'Booking',
    ];

    public function index()
    {
        $periodEnd = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $threeMonthsStart = now()->startOfMonth()->subMonths(2)->toDateString();
        $sixMonthsStart = now()->startOfMonth()->subMonths(5)->toDateString();
        $yearStart = now()->startOfYear()->toDateString();

        $sumFromDate = function (string $fromDate) use ($periodEnd) {
            return PayrollPayment::query()
                ->selectRaw('COALESCE(SUM(amount_usd), 0)')
                ->whereColumn('payroll_payments.worker_id', 'workers.id')
                ->whereDate('payroll_payments.payment_date', '>=', $fromDate)
                ->whereDate('payroll_payments.payment_date', '<=', $periodEnd);
        };

        $workers = Worker::query()
            ->select('workers.*')
            ->withCount('payrollPayments')
            ->selectSub($sumFromDate($monthStart), 'received_month_usd')
            ->selectSub($sumFromDate($threeMonthsStart), 'received_three_months_usd')
            ->selectSub($sumFromDate($sixMonthsStart), 'received_six_months_usd')
            ->selectSub($sumFromDate($yearStart), 'received_year_usd')
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Workers/Index', [
            'workers' => $workers,
            'positionOptions' => self::POSITION_OPTIONS,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Workers/Create', [
            'positionOptions' => self::POSITION_OPTIONS,
        ]);
    }

    public function store(StoreWorkerRequest $request)
    {
        Worker::create($request->validated());

        return redirect()
            ->route('admin.workers.index')
            ->with('success', 'Trabajador creado correctamente.');
    }

    public function edit(Worker $worker)
    {
        return Inertia::render('Admin/Workers/Edit', [
            'worker' => $worker,
            'positionOptions' => self::POSITION_OPTIONS,
        ]);
    }

    public function update(UpdateWorkerRequest $request, Worker $worker)
    {
        $worker->update($request->validated());

        return redirect()
            ->route('admin.workers.index')
            ->with('success', 'Trabajador actualizado correctamente.');
    }

    public function destroy(Worker $worker)
    {
        $worker->delete();

        return redirect()
            ->route('admin.workers.index')
            ->with('success', 'Trabajador enviado a papelera.');
    }

    public function payroll(Worker $worker)
    {
        $periodEnd = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $threeMonthsStart = now()->startOfMonth()->subMonths(2)->toDateString();
        $sixMonthsStart = now()->startOfMonth()->subMonths(5)->toDateString();
        $yearStart = now()->startOfYear()->toDateString();

        $payments = PayrollPayment::query()
            ->where('worker_id', $worker->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'month_usd' => (float) PayrollPayment::query()
                ->where('worker_id', $worker->id)
                ->whereDate('payment_date', '>=', $monthStart)
                ->whereDate('payment_date', '<=', $periodEnd)
                ->sum('amount_usd'),
            'three_months_usd' => (float) PayrollPayment::query()
                ->where('worker_id', $worker->id)
                ->whereDate('payment_date', '>=', $threeMonthsStart)
                ->whereDate('payment_date', '<=', $periodEnd)
                ->sum('amount_usd'),
            'six_months_usd' => (float) PayrollPayment::query()
                ->where('worker_id', $worker->id)
                ->whereDate('payment_date', '>=', $sixMonthsStart)
                ->whereDate('payment_date', '<=', $periodEnd)
                ->sum('amount_usd'),
            'year_usd' => (float) PayrollPayment::query()
                ->where('worker_id', $worker->id)
                ->whereDate('payment_date', '>=', $yearStart)
                ->whereDate('payment_date', '<=', $periodEnd)
                ->sum('amount_usd'),
        ];

        return Inertia::render('Admin/Workers/Payroll', [
            'worker' => $worker,
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }

    public function trash(Request $request)
    {
        $workers = Worker::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Worker $worker): array {
                $activePayments = PayrollPayment::query()
                    ->where('worker_id', $worker->id)
                    ->count();

                return [
                    'id' => $worker->id,
                    'primary' => $worker->full_name,
                    'secondary' => trim(($worker->position ?? '-') . ' · ' . ($worker->document_number ?? '-'), ' ·'),
                    'deleted_at' => $worker->deleted_at,
                    'can_force_delete' => $activePayments === 0,
                    'force_delete_blocked_reason' => $activePayments > 0
                        ? "Tiene {$activePayments} pagos activos asociados."
                        : null,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($workers);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Trabajadores',
            'items' => $workers,
            'restoreRoute' => 'admin.workers.restore',
            'forceDeleteRoute' => 'admin.workers.force-delete',
            'backRoute' => 'admin.workers.index',
        ]);
    }

    public function restore(int $workerId)
    {
        $worker = Worker::onlyTrashed()->findOrFail($workerId);

        DB::transaction(function () use ($worker): void {
            $worker->restore();
        });

        return redirect()
            ->route('admin.workers.index')
            ->with('success', 'Trabajador restaurado correctamente.');
    }

    public function forceDelete(int $workerId)
    {
        $worker = Worker::withTrashed()->findOrFail($workerId);

        if (!$worker->trashed()) {
            return back()->withErrors([
                'worker' => 'Solo puedes eliminar permanentemente trabajadores que estén en papelera.',
            ]);
        }

        $activePayments = PayrollPayment::query()
            ->where('worker_id', $worker->id)
            ->count();

        if ($activePayments > 0) {
            return back()->withErrors([
                'worker' => "No se puede eliminar permanentemente: tiene {$activePayments} pagos activos asociados.",
            ]);
        }

        DB::transaction(function () use ($worker): void {
            $worker->forceDelete();
        });

        return redirect()
            ->route('admin.workers.index')
            ->with('success', 'Trabajador eliminado permanentemente.');
    }
}
