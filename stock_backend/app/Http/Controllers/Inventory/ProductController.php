<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku'           => 'required|string|max:50|unique:products,sku',
            'name'          => 'required|string|max:150',
            'category_id'   => 'nullable|exists:categories,id',
            'barcode'       => 'nullable|string|max:50',
            'unit'          => 'nullable|string|max:20',
            'cost_price'    => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'vat_rate'      => 'nullable|numeric|min:0',
            'stock_qty' => 'nullable|numeric|min:0',
            'is_active'     => 'boolean',
        ]);

        $product = Product::create([
            'sku'           => $data['sku'],
            'name'          => $data['name'],
            'category_id'   => $data['category_id'] ?? null,
            'barcode'       => $data['barcode'] ?? null,
            'unit'          => $data['unit'] ?? 'pcs',
            'cost_price'    => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'vat_rate'      => $data['vat_rate'] ?? 0,
            'stock_qty' => $data['stock_qty'] ?? 0,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return response()->json($product->load('category'), 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('category'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku'           => 'sometimes|string|max:50|unique:products,sku,' . $product->id,
            'name'          => 'sometimes|string|max:150',
            'category_id'   => 'nullable|exists:categories,id',
            'barcode'       => 'nullable|string|max:50',
            'unit'          => 'nullable|string|max:20',
            'cost_price'    => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'vat_rate'      => 'nullable|numeric|min:0',
            'stock_qty' => 'nullable|numeric|min:0',
            'is_active'     => 'boolean',
        ]);

        $product->update($data);

        return response()->json($product->load('category'));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
