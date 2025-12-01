<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'barcode',
        'unit',
        'cost_price',
        'selling_price',
        'vat_rate',
        'stock_qty',
        'is_active',
    ];

    protected $casts = [
        'cost_price'    => 'decimal:2',
        'selling_price' => 'decimal:2',
        'vat_rate'      => 'decimal:2',
        'stock_qty' => 'float',
        'is_active'     => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Items in purchase orders that include this product
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // Items in sales that include this product
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
