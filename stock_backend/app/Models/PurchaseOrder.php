<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    // use HasFactory;

    public $timestamps = false; // we manage created_at manually in migration

    protected $fillable = [
        'po_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_date',
        'total_amount',
        'created_by',
        'created_at',
        'received_at',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
        'total_amount'  => 'decimal:2',
        'created_at'    => 'datetime',
        'received_at'   => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // user who created the PO
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
