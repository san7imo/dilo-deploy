<?php

namespace App\Services;

use App\Models\PayrollPayment;
use App\Models\Worker;

class PayrollPaymentService
{
    public function create(Worker $worker, array $data): PayrollPayment
    {
        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            $result = $imageKit->upload($data['receipt_file'], '/payroll-payments');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

        return $worker->payrollPayments()->create($data);
    }

    public function update(PayrollPayment $payment, array $data): PayrollPayment
    {
        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            if (!empty($payment->receipt_id)) {
                $imageKit->delete($payment->receipt_id);
            }

            $result = $imageKit->upload($data['receipt_file'], '/payroll-payments');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

        $payment->update($data);

        return $payment;
    }

    public function delete(PayrollPayment $payment): void
    {
        if (!empty($payment->receipt_id)) {
            $imageKit = app(ImageKitService::class);
            $imageKit->delete($payment->receipt_id);
        }

        $payment->delete();
    }
}

