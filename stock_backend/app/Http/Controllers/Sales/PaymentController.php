<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('sale')->orderBy('paid_at', 'desc')->paginate(50);
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id'        => 'required|exists:sales,id',
            'payment_method' => 'required|string|max:30',
            'amount'         => 'required|numeric|min:0',
            'reference_no'   => 'nullable|string|max:100',
        ]);

        $payment = Payment::create($data);

        // optional: update sale payment_status / check total paid
        $this->updateSalePaymentStatus($payment->sale);

        return response()->json($payment->load('sale'), 201);
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load('sale'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'payment_method' => 'sometimes|string|max:30',
            'amount'         => 'sometimes|numeric|min:0',
            'reference_no'   => 'nullable|string|max:100',
        ]);

        $payment->update($data);

        $this->updateSalePaymentStatus($payment->sale);

        return response()->json($payment->load('sale'));
    }

    public function destroy(Payment $payment)
    {
        $sale = $payment->sale;
        $payment->delete();

        if ($sale) {
            $this->updateSalePaymentStatus($sale);
        }

        return response()->json(['message' => 'Payment deleted']);
    }

    protected function updateSalePaymentStatus(Sale $sale)
    {
        $paid = $sale->payments()->sum('amount');

        if ($paid >= $sale->net_amount) {
            $sale->payment_status = 'PAID';
        } elseif ($paid > 0) {
            $sale->payment_status = 'PARTIAL';
        } else {
            $sale->payment_status = 'UNPAID';
        }

        $sale->save();
    }
}
