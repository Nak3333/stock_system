<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    public $timestamps = false; // we use created_at + sale_datetime but not Laravel's default pair

    protected $fillable = [
        'receipt_number',
        'cashier_id',
        'sale_datetime',
        'total_amount',
        'total_discount',
        'total_tax',
        'net_amount',
        'payment_status',
        'created_at',
    ];

    protected $casts = [
        'sale_datetime'  => 'datetime',
        'total_amount'   => 'float',
        'total_discount' => 'float',
        'total_tax'      => 'float',
        'net_amount'     => 'float',
        'created_at'     => 'datetime',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
