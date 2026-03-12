<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollPaymentRequest;
use App\Http\Requests\UpdatePayrollPaymentRequest;
use App\Models\PayrollPayment;
use App\Models\Worker;
use App\Services\PayrollPaymentService;

class PayrollPaymentController extends Controller
{
    public function store(
        StorePayrollPaymentRequest $request,
        Worker $worker,
        PayrollPaymentService $service
    )
    {
        $data = [
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ];

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        $service->create($worker, $data);

        return redirect()
            ->route('admin.workers.payroll', $worker)
            ->with('success', 'Pago de nómina registrado correctamente.');
    }

    public function update(
        UpdatePayrollPaymentRequest $request,
        PayrollPayment $payrollPayment,
        PayrollPaymentService $service
    )
    {
        $data = $request->validated();
        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        $service->update($payrollPayment, $data);

        return redirect()
            ->route('admin.workers.payroll', $payrollPayment->worker_id)
            ->with('success', 'Pago de nómina actualizado correctamente.');
    }

    public function destroy(PayrollPayment $payrollPayment, PayrollPaymentService $service)
    {
        $workerId = $payrollPayment->worker_id;
        $service->delete($payrollPayment);

        return redirect()
            ->route('admin.workers.payroll', $workerId)
            ->with('success', 'Pago de nómina enviado a papelera.');
    }
}
