<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\PaymentStoreRequest;
use App\Http\Requests\Payment\PaymentUpdateRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index() {}

    public function store(PaymentStoreRequest $request)
    {
        $payment = Payment::create($request->validated());

        return new PaymentResource($payment->load('project'));
    }

    public function show(Payment $payment)
    {
        return new PaymentResource($payment->load('project'));
    }

    public function update(PaymentUpdateRequest $request, Payment $payment)
    {
        $payment->update($request->validated());

        return new PaymentResource($payment->load('project'));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json([
            'message' => 'Pagamento removido com sucesso.',
        ]);
    }
}
