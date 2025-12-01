<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('cashier')->orderBy('sale_datetime', 'desc')->paginate(20);
        return response()->json($sales);
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'receipt_number'       => 'required|string|max:50|unique:sales,receipt_number',
        'cashier_id'           => 'required|exists:users,id',
        'payment_status'       => 'nullable|string|in:PAID,PARTIAL,UNPAID',
        'items'                => 'required|array|min:1',
        'items.*.product_id'   => 'required|exists:products,id',
        'items.*.quantity'     => 'required|numeric|min:0.001',
        'items.*.unit_price'   => 'required|numeric|min:0',
        'items.*.discount_amt' => 'nullable|numeric|min:0',
    ]);

    return DB::transaction(function () use ($data) {
        // Create the sale header
        $sale = new Sale();
        $sale->receipt_number = $data['receipt_number'];
        $sale->cashier_id     = $data['cashier_id'];
        $sale->payment_status = $data['payment_status'] ?? 'PAID';
        $sale->total_amount   = 0;
        $sale->total_discount = 0;
        $sale->total_tax      = 0;  // you can compute VAT later
        $sale->net_amount     = 0;
        $sale->save();

        $total         = 0;
        $discountTotal = 0;

        foreach ($data['items'] as $itemData) {
            $discount  = $itemData['discount_amt'] ?? 0;
            $lineTotal = $itemData['quantity'] * $itemData['unit_price'] - $discount;

            // Create sale item row
            SaleItem::create([
                'sale_id'      => $sale->id,
                'product_id'   => $itemData['product_id'],
                'quantity'     => $itemData['quantity'],
                'unit_price'   => $itemData['unit_price'],
                'discount_amt' => $discount,
                'line_total'   => $lineTotal,
            ]);

            // 🔽 Decrease stock for that product
            // If you want to be extra-safe with concurrent requests, use lockForUpdate():
            // $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);
            $product = Product::findOrFail($itemData['product_id']);

            // Optional: block if not enough stock
            if ($product->stock_qty < $itemData['quantity']) {
                // This will rollback the transaction automatically
                abort(422, "Not enough stock for product: {$product->name}");
            }

            $product->stock_qty -= $itemData['quantity'];
            $product->save();  // ✅ important: actually persist change

            $total         += $itemData['quantity'] * $itemData['unit_price'];
            $discountTotal += $discount;
        }

        // Update sale totals
        $sale->total_amount   = $total;
        $sale->total_discount = $discountTotal;
        $sale->net_amount     = $total - $discountTotal + $sale->total_tax;
        $sale->save();

        return response()->json(
            $sale->load(['cashier', 'items.product']),
            201
        );
    });
}


    public function show(Sale $sale)
    {
        return response()->json($sale->load(['cashier', 'items.product', 'payments']));
    }

    public function update(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'payment_status' => 'sometimes|string|in:PAID,PARTIAL,UNPAID',
            'items'          => 'array',
            'items.*.product_id'   => 'required_with:items|exists:products,id',
            'items.*.quantity'     => 'required_with:items|numeric|min:0.001',
            'items.*.unit_price'   => 'required_with:items|numeric|min:0',
            'items.*.discount_amt' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $sale) {
            if (isset($data['payment_status'])) {
                $sale->payment_status = $data['payment_status'];
                $sale->save();
            }

            if (isset($data['items'])) {
                $sale->items()->delete();

                $total = 0;
                $discountTotal = 0;

                foreach ($data['items'] as $itemData) {
                    $discount = $itemData['discount_amt'] ?? 0;
                    $lineTotal = $itemData['quantity'] * $itemData['unit_price'] - $discount;

                    SaleItem::create([
                        'sale_id'      => $sale->id,
                        'product_id'   => $itemData['product_id'],
                        'quantity'     => $itemData['quantity'],
                        'unit_price'   => $itemData['unit_price'],
                        'discount_amt' => $discount,
                        'line_total'   => $lineTotal,
                    ]);

                    $total         += $itemData['quantity'] * $itemData['unit_price'];
                    $discountTotal += $discount;
                }

                $sale->total_amount   = $total;
                $sale->total_discount = $discountTotal;
                $sale->net_amount     = $total - $discountTotal + $sale->total_tax;
                $sale->save();
            }

            return response()->json($sale->load(['cashier', 'items.product', 'payments']));
        });
    }

    public function destroy(Sale $sale)
    {
        $sale->items()->delete();
        $sale->payments()->delete();
        $sale->delete();

        return response()->json(['message' => 'Sale deleted']);
    }
}
