<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with('supplier')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'po_number'    => 'required|string|max:50|unique:purchase_orders,po_number',
            'supplier_id'  => 'required|exists:suppliers,id',
            'status'       => 'nullable|string|in:DRAFT,SENT,RECEIVED,CANCELLED',
            'order_date'   => 'nullable|date',
            'expected_date'=> 'nullable|date',
            'created_by'   => 'nullable|exists:users,id',
            'items'        => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_cost'  => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $po = new PurchaseOrder();
            $po->po_number     = $data['po_number'];
            $po->supplier_id   = $data['supplier_id'];
            $po->status        = $data['status'] ?? 'DRAFT';
            if (!empty($data['order_date']))    $po->order_date = $data['order_date'];
            if (!empty($data['expected_date'])) $po->expected_date = $data['expected_date'];
            $po->created_by    = $data['created_by'] ?? null;
            $po->total_amount  = 0;
            $po->save();

            $total = 0;
            foreach ($data['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['unit_cost'];

                $poItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $itemData['product_id'],
                    'quantity'          => $itemData['quantity'],
                    'unit_cost'         => $itemData['unit_cost'],
                    'line_total'        => $lineTotal,
                ]);

                $total += $poItem->lineTotal;
            }

            $po->total_amount = $total;
            $po->save();

            return response()->json(
                $po->load(['supplier', 'items.product']),
                201
            );
        });
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return response()->json(
            $purchaseOrder->load(['supplier', 'items.product', 'creator'])
        );
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'status'       => 'sometimes|string|in:DRAFT,SENT,RECEIVED,CANCELLED',
            'order_date'   => 'nullable|date',
            'expected_date'=> 'nullable|date',
            'items'        => 'array',
            'items.*.id'         => 'nullable|exists:purchase_order_items,id',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity'   => 'required_with:items|numeric|min:0.001',
            'items.*.unit_cost'  => 'required_with:items|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $purchaseOrder) {
            if (isset($data['status']))        $purchaseOrder->status = $data['status'];
            if (isset($data['order_date']))    $purchaseOrder->order_date = $data['order_date'];
            if (isset($data['expected_date'])) $purchaseOrder->expected_date = $data['expected_date'];
            $purchaseOrder->save();

            if (isset($data['items'])) {
                // simple way: delete all and re-insert (or implement smart diff)
                $purchaseOrder->items()->delete();

                $total = 0;
                foreach ($data['items'] as $itemData) {
                    $lineTotal = $itemData['quantity'] * $itemData['unit_cost'];

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id'        => $itemData['product_id'],
                        'quantity'          => $itemData['quantity'],
                        'unit_cost'         => $itemData['unit_cost'],
                        'line_total'        => $lineTotal,
                    ]);

                    $total += $lineTotal;
                }

                $purchaseOrder->total_amount = $total;
                $purchaseOrder->save();
            }

            return response()->json(
                $purchaseOrder->load(['supplier', 'items.product', 'creator'])
            );
        });
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return response()->json(['message' => 'Purchase order deleted']);
    }
}
